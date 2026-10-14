<?php

namespace App\Services\Charge;

use App\Enums\ChargePayer;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PeriodStatus;
use App\Enums\UnitStatus;
use App\Models\ChargeFixedRule;
use App\Models\ChargePeriod;
use App\Models\ChargeSetting;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\ParkingAssignment;
use App\Models\StorageAssignment;
use App\Models\Unit;
use App\Models\UnitParty;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * جریان یک دوره‌ی شارژ: draft ← calculated ← published.
 * همه‌ی کوئری‌ها به ساختمان فعال محدودند (BuildingScope)، پس باید داخل context یک ساختمان اجرا شود.
 */
class ChargePeriodService
{
    public function __construct(private readonly ChargeCalculator $calculator, private readonly \App\Services\PaymentService $payments)
    {
    }

    /** دوره‌ی شارژ یک ماه شمسی را می‌گیرد یا (با تاریخ محاسبه‌ی طبق تنظیمات) می‌سازد. */
    public function ensure(int $year, int $month): ChargePeriod
    {
        $day = min(ChargeSetting::first()?->calc_day ?? 25, 29);
        $calc = (new Jalalian($year, $month, $day))->toCarbon()->startOfDay();

        return ChargePeriod::firstOrCreate(
            ['jalali_year' => $year, 'jalali_month' => $month],
            ['calc_date' => $calc, 'status' => PeriodStatus::Draft, 'total_expenses' => 0],
        );
    }

    /** با تغییر هزینه‌ها، نتیجه‌ی محاسبه‌شده بی‌اعتبار است و دوباره باید محاسبه شود. منتشرشده دست‌نخورده می‌ماند. */
    public function invalidate(ChargePeriod $period): void
    {
        if ($period->status === PeriodStatus::Calculated) {
            $period->update(['status' => PeriodStatus::Draft]);
        }
    }

    /** محاسبه بدون ذخیره (برای پیش‌نمایش و هشدار واحدهای بدون ساکن). */
    public function preview(ChargePeriod $period): ChargeResult
    {
        $settings = ChargeSetting::firstOrFail();

        $parking = ParkingAssignment::whereNull('released_at')->pluck('unit_id')->flip();
        $storage = StorageAssignment::whereNull('released_at')->pluck('unit_id')->flip();
        $inputs = Unit::all()
            ->map(fn (Unit $u) => UnitInput::fromModel($u, $parking->has($u->id), $storage->has($u->id)))
            ->all();

        $expenses = Expense::where('period_id', $period->id)->with('category')->get()
            ->map(fn (Expense $e) => new ExpenseLine((int) $e->category_id, (int) $e->amount, $e->category?->split_basis ?? 'equal'))
            ->all();

        $rules = ChargeFixedRule::all()
            ->map(fn (ChargeFixedRule $r) => new FixedRule($r->bedrooms, $r->has_parking, $r->has_storage, (int) $r->amount))
            ->all();

        return $this->calculator->calculate(ChargeConfig::fromSettings($settings, $rules, $expenses), $inputs);
    }

    /** محاسبه و ذخیره‌ی نتیجه در settings_snapshot (برای بازتولید و ممیزی). */
    public function calculate(ChargePeriod $period): ChargeResult
    {
        if ($period->status === PeriodStatus::Published) {
            throw new ChargeException('دوره‌ی منتشرشده قابل محاسبه‌ی مجدد نیست.');
        }

        $result = $this->preview($period);
        $settings = ChargeSetting::firstOrFail();

        $period->update([
            'status' => PeriodStatus::Calculated,
            'total_expenses' => $result->expensesTotal,
            'settings_snapshot' => [
                'settings' => $settings->only(['method', 'mode', 'fixed_amount', 'area_rate', 'person_rate', 'person_ratio_pct', 'area_ratio_pct', 'due_days']),
                'amounts' => $result->amounts,
                'skipped' => $result->skipped,
                'warnings' => $result->warnings,
            ],
        ]);

        return $result;
    }

    /** صدور صورت‌حساب شارژ برای هر واحد (مبلغ صفر رد می‌شود) و منتشر کردن دوره. تعداد صورت‌حساب‌ها برمی‌گردد. */
    public function publish(ChargePeriod $period): int
    {
        if ($period->status !== PeriodStatus::Calculated) {
            throw new ChargeException('ابتدا شارژ این دوره را محاسبه کنید.');
        }

        $amounts = $period->settings_snapshot['amounts'] ?? [];
        $settings = ChargeSetting::firstOrFail();
        $units = Unit::whereIn('id', array_keys($amounts))->get()->keyBy('id');
        $parties = UnitParty::whereNull('to_date')->get()->groupBy('unit_id');
        $rents = Contract::where('type', 'rent')->where('status', 'active')->get()->keyBy('unit_id');
        $issued = today();
        $due = today()->addDays((int) $settings->due_days);

        return DB::transaction(function () use ($amounts, $units, $parties, $rents, $period, $issued, $due) {
            $count = 0;
            foreach ($amounts as $unitId => $amount) {
                if ($amount <= 0 || ! $units->has($unitId)) {
                    continue;
                }
                $unit = $units[$unitId];
                $contract = $unit->status === UnitStatus::Rented ? $rents->get($unit->id) : null;

                Invoice::create([
                    'unit_id' => $unit->id,
                    'period_id' => $period->id,
                    'contract_id' => $contract?->id,
                    'payer_user_id' => $this->payerFor($unit, $contract, $parties),
                    'type' => InvoiceType::Charge,
                    'number' => sprintf('CH-%d%02d-%d', $period->jalali_year, $period->jalali_month, $unit->id),
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'issued_at' => $issued,
                    'due_date' => $due,
                    'status' => InvoiceStatus::Unpaid,
                    'breakdown' => ['method' => $period->settings_snapshot['settings']['method'] ?? null],
                ]);
                $count++;
                $this->payments->reallocate($unit->id); // مازاد پرداخت‌های قبلی از همین صورت‌حساب کم می‌شود
            }

            $period->update(['status' => PeriodStatus::Published]);

            return $count;
        });
    }

    /** مسئول پرداخت شارژ (بند ۴.۱۵.۱): ساکن فعلی؛ در واحد اجاره‌ای طبق «شارژ بر عهده» قرارداد؛ در غیر این صورت مالک. */
    private function payerFor(Unit $unit, ?Contract $contract, Collection $parties): ?int
    {
        $list = $parties->get($unit->id, collect());
        $owner = $list->firstWhere('relation', 'owner');
        $tenant = $list->firstWhere('relation', 'tenant');

        if ($contract) {
            $payer = $contract->charge_payer === ChargePayer::Tenant ? $tenant : $owner;

            return ($payer ?? $owner ?? $tenant)?->user_id;
        }

        return ($owner ?? $tenant)?->user_id;
    }
}
