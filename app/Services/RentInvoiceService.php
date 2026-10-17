<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Building;
use App\Models\ChargeSetting;
use App\Models\Contract;
use App\Models\Invoice;
use App\Services\Sms\SmsSender;
use App\Support\Fa;
use App\Support\RentSchedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * صدور خودکار صورت‌حساب اجاره‌ی ماهانه (بند ۴.۱۵.۲).
 * - هر ماه شمسی، در همان روز ماهِ تاریخ عقد؛ تا قبل از تاریخ پایان قرارداد.
 * - فقط برای روزهایی که از ثبت قرارداد در سامانه به بعد هستند (قرارداد با تاریخ عقدِ گذشته، صورت‌حساب عقب‌افتاده نمی‌سازد).
 * - تکرارناپذیر: شماره‌ی صورت‌حساب (RT-{قرارداد}-{سال‌ماه}) یکتاست، پس اجرای دوباره چیزی نمی‌سازد.
 * - باید داخل CurrentBuilding اجرا شود (دستور rent:issue-invoices این کار را می‌کند).
 */
class RentInvoiceService
{
    public function __construct(private readonly PaymentService $payments, private readonly SmsSender $sms, private readonly Notifier $notifier)
    {
    }

    /** صورت‌حساب‌های سررسیدشده‌ی یک قرارداد تا امروز را می‌سازد؛ تعداد صورت‌حساب‌های تازه را برمی‌گرداند. */
    public function issueFor(Contract $contract, ?CarbonInterface $today = null): int
    {
        if (! $contract->isActiveRent() || (int) $contract->monthly_rent <= 0 || ! $contract->signed_at) {
            return 0;
        }

        $today = ($today ?? today())->copy()->startOfDay();
        $registered = $contract->created_at->copy()->startOfDay();
        $slots = RentSchedule::issueDates($contract->signed_at->toDateString(), $today->toDateString(), $contract->end_date?->toDateString());

        $count = 0;
        foreach ($slots as $slot) {
            $date = Carbon::parse($slot['date'])->startOfDay();
            if ($date->lt($registered)) {
                continue;
            }
            if ($this->issueOne($contract, $slot, $date)) {
                $count++;
            }
        }

        return $count;
    }

    private function issueOne(Contract $contract, array $slot, Carbon $date): bool
    {
        $number = sprintf('RT-%d-%d%02d', $contract->id, $slot['jy'], $slot['jm']);

        $invoice = DB::transaction(function () use ($contract, $slot, $date, $number) {
            $locked = Contract::lockForUpdate()->find($contract->id);
            if (! $locked || ! $locked->isActiveRent() || Invoice::where('number', $number)->exists()) {
                return null;
            }

            $grace = (int) (ChargeSetting::value('due_days') ?? 10);

            $invoice = Invoice::create([
                'unit_id' => $locked->unit_id,
                'contract_id' => $locked->id,
                'payer_user_id' => $locked->user_id,
                'type' => InvoiceType::Rent,
                'number' => $number,
                'amount' => (int) $locked->monthly_rent,
                'paid_amount' => 0,
                'issued_at' => $date,
                'due_date' => $date->copy()->addDays($grace),
                'status' => InvoiceStatus::Unpaid,
                'breakdown' => ['rent' => ['sequence' => $slot['seq'], 'month' => sprintf('%d/%02d', $slot['jy'], $slot['jm'])]],
            ]);
            $this->payments->reallocate($locked->unit_id); // اعتبار پرداخت‌های قبلیِ واحد از همین صورت‌حساب کم می‌شود

            return $invoice;
        });

        if (! $invoice) {
            return false;
        }

        $this->notify($contract, $invoice, $slot);

        return true;
    }

    private function notify(Contract $contract, Invoice $invoice, array $slot): void
    {
        try {
            $tenant = $contract->user;
            $unit = $contract->unit()->with('block')->first();
            if (! $tenant || ! $unit) {
                return;
            }
            $unitLabel = $unit->block->name.' / '.Fa::digits($unit->number);
            $month = Fa::MONTHS[$slot['jm'] - 1];

            $this->notifier->send($tenant, (int) $invoice->building_id, 'rent_invoice_issued', 'صورت‌حساب اجاره صادر شد',
                "صورت‌حساب اجاره‌ی {$month} واحد {$unitLabel} به مبلغ ".Fa::num($invoice->amount).' تومان صادر شد؛ مهلت پرداخت '.Fa::date($invoice->due_date, 'j F Y').'.',
                route('my.invoices'),
                ['category' => 'invoice', 'key' => 'invoice:'.$invoice->id, 'label' => 'مشاهده صورت‌حساب‌ها']);

            if (! $tenant->mobile) {
                return;
            }
            $this->sms->sendTemplate('rent_invoice_issued', $tenant->mobile, [
                'month' => $month,
                'amount' => Fa::num($invoice->amount),
                'unit' => $unitLabel,
                'building' => Building::find($invoice->building_id)?->name,
                'date' => Fa::date($invoice->due_date, 'j F Y'),
            ], $tenant->id, $invoice->building_id);
        } catch (\Throwable $e) {
            report($e); // خطای پیامک/اعلان نباید صدور صورت‌حساب را خراب کند
        }
    }
}
