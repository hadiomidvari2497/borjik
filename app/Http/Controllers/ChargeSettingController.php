<?php

namespace App\Http\Controllers;

use App\Enums\ChargeMethod;
use App\Models\ChargePeriod;
use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\ChargeFixedRule;
use App\Models\ChargeSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChargeSettingController extends Controller
{
    use NormalizesNumbers;

    public function edit()
    {
        return view('charge-settings.edit', [
            'setting' => ChargeSetting::first() ?? ChargeSetting::create([]),
            'methods' => ChargeMethod::cases(),
            'rules' => ChargeFixedRule::orderBy('id')->get(),
            'categories' => ExpenseCategory::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $this->normalizeNumbers($request, ['fixed_amount', 'area_rate', 'person_rate', 'person_ratio_pct', 'calc_day', 'warn_days_before', 'due_days', 'late_fee_value', 'late_fee_cap', 'late_fee_start_day', 'emergency_phone']);

        $v = $request->validate([
            'method' => ['required', Rule::enum(ChargeMethod::class)],
            'mode' => ['required', Rule::in(['rate', 'ratio'])],
            'fixed_amount' => ['nullable', 'integer', 'min:0'],
            'area_rate' => ['nullable', 'integer', 'min:0'],
            'person_rate' => ['nullable', 'integer', 'min:0'],
            'person_ratio_pct' => ['required', 'integer', 'between:0,100'],
            'calc_day' => ['required', 'integer', 'between:1,29'],
            'warn_days_before' => ['required', 'integer', 'between:0,28'],
            'due_days' => ['required', 'integer', 'between:1,90'],
            'emergency_phone' => ['nullable', 'digits_between:5,15'],
            'late_fee_enabled' => ['nullable', 'boolean'],
            'late_fee_type' => ['nullable', Rule::in(['percent', 'fixed']), 'required_if:late_fee_enabled,1'],
            'late_fee_value' => ['nullable', 'integer', 'min:0'],
            'late_fee_cap' => ['nullable', 'integer', 'min:0'],
            'late_fee_start_day' => ['nullable', 'integer', 'between:1,60'],
        ]);

        $setting = ChargeSetting::first() ?? ChargeSetting::create([]);
        $setting->update([
            'method' => (int) $v['method'],
            'mode' => $v['mode'],
            'fixed_amount' => (int) ($v['fixed_amount'] ?? 0),
            'area_rate' => (int) ($v['area_rate'] ?? 0),
            'person_rate' => (int) ($v['person_rate'] ?? 0),
            'person_ratio_pct' => (int) $v['person_ratio_pct'],
            'area_ratio_pct' => 100 - (int) $v['person_ratio_pct'],
            'calc_day' => (int) $v['calc_day'],
            'warn_days_before' => (int) $v['warn_days_before'],
            'due_days' => (int) $v['due_days'],
            'emergency_phone' => $v['emergency_phone'] ?? null,
            'late_fee_enabled' => $request->boolean('late_fee_enabled'),
            'late_fee_type' => $request->boolean('late_fee_enabled') ? $v['late_fee_type'] : null,
            'late_fee_value' => (int) ($v['late_fee_value'] ?? 0),
            'late_fee_cap' => isset($v['late_fee_cap']) ? (int) $v['late_fee_cap'] : null,
            'late_fee_start_day' => (int) ($v['late_fee_start_day'] ?? 1),
        ]);

        $this->invalidateCalculatedPeriods();

        return redirect()->route('charge-settings.edit')->with('status', 'تنظیمات شارژ ذخیره شد. دوره‌های محاسبه‌شده نیازمند محاسبهٔ مجدد هستند.');
    }

    /** قاعده‌ی شارژ ثابت (روش ۱). هر سه معیار اختیاری است؛ «مهم نیست» یعنی ستون خالی. */
    public function storeRule(Request $request)
    {
        $this->normalizeNumbers($request, ['bedrooms', 'amount']);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'between:0,20'],
            'has_parking' => ['required', Rule::in(['any', 'yes', 'no'])],
            'has_storage' => ['required', Rule::in(['any', 'yes', 'no'])],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $tri = fn (string $x) => $x === 'any' ? null : $x === 'yes';
        ChargeFixedRule::create([
            'name' => $v['name'], 'bedrooms' => $v['bedrooms'] ?? null,
            'has_parking' => $tri($v['has_parking']), 'has_storage' => $tri($v['has_storage']), 'amount' => (int) $v['amount'],
        ]);

        $this->invalidateCalculatedPeriods();

        return redirect()->route('charge-settings.edit')->with('status', 'قاعده اضافه شد؛ دوره‌های محاسبه‌شده نیازمند محاسبهٔ مجدد هستند.');
    }

    public function destroyRule(int $id)
    {
        ChargeFixedRule::findOrFail($id)->delete();
        $this->invalidateCalculatedPeriods();

        return redirect()->route('charge-settings.edit')->with('status', 'قاعده حذف شد؛ دوره‌های محاسبه‌شده نیازمند محاسبهٔ مجدد هستند.');
    }

    private function invalidateCalculatedPeriods(): void
    {
        ChargePeriod::query()->where('status', 'calculated')->update(['status' => 'draft']);
    }

    public function storeCategory(Request $request)
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in(['fixed', 'variable'])],
            // «custom» بعداً با رابط وزن‌دهی هر واحد اضافه می‌شود؛ موتور بدون وزن آن را رد می‌کند.
            'split_basis' => ['required', Rule::in(['equal', 'area', 'persons'])],
        ]);
        ExpenseCategory::create($v);

        return redirect()->route('charge-settings.edit')->with('status', 'دسته‌ی هزینه اضافه شد.');
    }

    public function destroyCategory(int $id)
    {
        $category = ExpenseCategory::findOrFail($id);
        if (Expense::withTrashed()->where('category_id', $category->id)->exists()) {
            return back()->withErrors(['general' => 'برای این دسته هزینه ثبت شده است و قابل حذف نیست.']);
        }
        $category->delete();

        return redirect()->route('charge-settings.edit')->with('status', 'دسته‌ی هزینه حذف شد.');
    }
}
