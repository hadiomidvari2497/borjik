<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCoupon;
use App\Models\SubscriptionPlan;
use App\Support\MobileNumber;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountCouponController extends Controller
{
    public function index()
    {
        $coupons = DiscountCoupon::query()->withCount([
            'redemptions as used_count' => fn ($query) => $query->where('status', 'paid'),
            'redemptions as reserved_count' => fn ($query) => $query->where('status', 'reserved'),
        ])->latest('id')->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();

        return view('admin.coupons.form', ['coupon' => null, 'plans' => $plans]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DiscountCoupon::create($data);

        return redirect()->route('admin.coupons.index')->with('status', 'کد تخفیف ایجاد شد.');
    }

    public function edit(int $id)
    {
        $coupon = DiscountCoupon::findOrFail($id);
        $plans = SubscriptionPlan::orderBy('price')->get();

        return view('admin.coupons.form', compact('coupon', 'plans'));
    }

    public function update(Request $request, int $id)
    {
        $coupon = DiscountCoupon::findOrFail($id);
        $coupon->update($this->validated($request, $coupon->id));

        return redirect()->route('admin.coupons.index')->with('status', 'کد تخفیف به‌روزرسانی شد.');
    }

    public function toggle(int $id)
    {
        $coupon = DiscountCoupon::findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('status', $coupon->is_active ? 'کد تخفیف فعال شد.' : 'کد تخفیف غیرفعال شد.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
            'discount_value' => MobileNumber::toLatinDigits((string) $request->input('discount_value', '')),
            'min_order_amount' => trim((string) $request->input('min_order_amount', '0')) === '' ? '0' : MobileNumber::toLatinDigits((string) $request->input('min_order_amount', '0')),
            'max_uses' => trim((string) $request->input('max_uses', '')) === '' ? null : MobileNumber::toLatinDigits((string) $request->input('max_uses', '')),
            'max_uses_per_user' => MobileNumber::toLatinDigits((string) $request->input('max_uses_per_user', '1')),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('discount_coupons', 'code')->ignore($ignoreId)],
            'name' => ['nullable', 'string', 'max:120'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'min_order_amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'max_uses_per_user' => ['required', 'integer', 'min:1', 'max:100000'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['integer', 'exists:subscription_plans,id'],
            'starts_at' => ['nullable', 'string', 'max:20'],
            'ends_at' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_value' => 'تخفیف درصدی نمی‌تواند بیشتر از ۱۰۰ باشد.']);
        }

        $startDate = JalaliDate::parse($request->input('starts_at'));
        $endDate = JalaliDate::parse($request->input('ends_at'));
        if ($request->filled('starts_at') && ! $startDate) {
            throw \Illuminate\Validation\ValidationException::withMessages(['starts_at' => 'تاریخ شروع شمسی معتبر نیست.']);
        }
        if ($request->filled('ends_at') && ! $endDate) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ends_at' => 'تاریخ پایان شمسی معتبر نیست.']);
        }
        if ($startDate && $endDate && $endDate->lt($startDate)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ends_at' => 'تاریخ پایان نباید قبل از تاریخ شروع باشد.']);
        }

        $data['starts_at'] = $startDate?->startOfDay();
        $data['ends_at'] = $endDate?->endOfDay();
        $data['min_order_amount'] = (int) ($data['min_order_amount'] ?? 0);
        $data['max_uses'] = ($data['max_uses'] ?? '') === '' ? null : (int) $data['max_uses'];
        $data['plan_ids'] = array_values(array_unique(array_map('intval', $data['plan_ids'] ?? []))) ?: null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
