<?php

namespace App\Http\Controllers;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\ChargePeriod;
use App\Models\ChargeSetting;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Unit;
use App\Services\Charge\ChargeException;
use App\Services\Charge\ChargePeriodService;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ChargeController extends Controller
{
    use NormalizesNumbers;

    public function index()
    {
        $now = Jalalian::now();

        return view('charges.index', [
            'periods' => ChargePeriod::orderByDesc('jalali_year')->orderByDesc('jalali_month')->limit(36)->get(),
            'year' => $now->getYear(), 'month' => $now->getMonth(),
            'configured' => ChargeSetting::exists(),
        ]);
    }

    /** باز کردن (یا ساختن) دوره‌ی یک ماه و رفتن به صفحه‌ی آن. */
    public function open(Request $request, ChargePeriodService $service)
    {
        $this->normalizeNumbers($request, ['year', 'month']);
        $v = $request->validate(['year' => ['required', 'integer', 'between:1380,1500'], 'month' => ['required', 'integer', 'between:1,12']]);

        return redirect()->route('charges.show', $service->ensure((int) $v['year'], (int) $v['month'])->id);
    }

    public function show(int $id)
    {
        $period = ChargePeriod::findOrFail($id);
        $snapshot = $period->settings_snapshot ?? [];
        $amounts = $snapshot['amounts'] ?? [];

        $unitIds = array_unique(array_merge(array_keys($amounts), array_keys($snapshot['skipped'] ?? [])));
        $units = Unit::with('block')->whereIn('id', $unitIds)->get()->keyBy('id');

        return view('charges.show', [
            'period' => $period,
            'setting' => ChargeSetting::first(),
            'expenses' => Expense::where('period_id', $period->id)->with('category')->orderBy('id')->get(),
            'amounts' => $amounts,
            'skipped' => $snapshot['skipped'] ?? [],
            'warnings' => $snapshot['warnings'] ?? [],
            'units' => $units,
            'invoices' => $period->status === PeriodStatus::Published
                ? Invoice::where('period_id', $period->id)->selectRaw('count(*) as c, coalesce(sum(amount), 0) as s')->first()
                : null,
        ]);
    }

    public function calculate(int $id, ChargePeriodService $service)
    {
        $period = ChargePeriod::findOrFail($id);

        try {
            $service->calculate($period);
        } catch (ChargeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return back()->withErrors(['general' => 'ابتدا تنظیمات شارژ را انجام دهید.']);
        }

        return redirect()->route('charges.show', $period->id)->with('status', 'شارژ محاسبه شد. نتیجه را بررسی کنید و سپس منتشر کنید.');
    }

    public function publish(Request $request, int $id, ChargePeriodService $service)
    {
        $period = ChargePeriod::findOrFail($id);
        $skipped = $period->settings_snapshot['skipped'] ?? [];

        if ($skipped && ! $request->boolean('confirm_skipped')) {
            return back()->withErrors(['general' => 'برخی واحدها محاسبه نشده‌اند. یا تعداد ساکنین را ثبت و دوباره محاسبه کنید، یا تأیید کنید که بدون آن‌ها منتشر شود.']);
        }

        try {
            $count = $service->publish($period);
        } catch (ChargeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('charges.show', $period->id)->with('status', "{$count} صورت‌حساب شارژ صادر شد.");
    }
}
