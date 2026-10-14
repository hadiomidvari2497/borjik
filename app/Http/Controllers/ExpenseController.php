<?php

namespace App\Http\Controllers;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\ChargePeriod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\Charge\ChargePeriodService;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class ExpenseController extends Controller
{
    use NormalizesNumbers;

    public function index(Request $request)
    {
        $this->normalizeNumbers($request, ['year', 'month']);
        $now = Jalalian::now();
        $year = (int) $request->input('year', $now->getYear());
        $month = (int) $request->input('month', $now->getMonth());
        abort_unless($year >= 1380 && $year <= 1500 && $month >= 1 && $month <= 12, 404);

        $period = ChargePeriod::where('jalali_year', $year)->where('jalali_month', $month)->first();
        $expenses = $period ? Expense::where('period_id', $period->id)->with('category')->orderBy('id')->get() : collect();

        $prev = $month === 1 ? [$year - 1, 12] : [$year, $month - 1];
        $next = $month === 12 ? [$year + 1, 1] : [$year, $month + 1];

        return view('expenses.index', [
            'year' => $year, 'month' => $month, 'period' => $period, 'expenses' => $expenses,
            'total' => (int) $expenses->sum('amount'),
            'categories' => ExpenseCategory::orderBy('id')->get(),
            'locked' => $period?->status === PeriodStatus::Published,
            'prev' => $prev, 'next' => $next,
        ]);
    }

    public function store(Request $request, ChargePeriodService $service)
    {
        $this->normalizeNumbers($request, ['year', 'month', 'amount']);
        $v = $request->validate([
            'year' => ['required', 'integer', 'between:1380,1500'],
            'month' => ['required', 'integer', 'between:1,12'],
            'category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('building_id', app(CurrentBuilding::class)->id())],
            'title' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'integer', 'min:1', 'max:99999999999'],
        ]);

        $period = $service->ensure((int) $v['year'], (int) $v['month']);
        if ($period->status === PeriodStatus::Published) {
            return back()->withErrors(['general' => 'شارژ این ماه منتشر شده است و هزینه‌ها قابل تغییر نیست.']);
        }

        Expense::create([
            'period_id' => $period->id, 'category_id' => $v['category_id'] ?? null,
            'title' => $v['title'], 'amount' => (int) $v['amount'], 'entered_by' => $request->user()->id,
        ]);
        $service->invalidate($period);

        return redirect()->route('expenses.index', ['year' => $v['year'], 'month' => $v['month']])->with('status', 'هزینه ثبت شد.');
    }

    public function destroy(int $id, ChargePeriodService $service)
    {
        $expense = Expense::findOrFail($id);
        $period = ChargePeriod::findOrFail($expense->period_id);

        if ($period->status === PeriodStatus::Published) {
            return back()->withErrors(['general' => 'شارژ این ماه منتشر شده است و هزینه‌ها قابل تغییر نیست.']);
        }

        $expense->delete();
        $service->invalidate($period);

        return back()->with('status', 'هزینه حذف شد.');
    }
}
