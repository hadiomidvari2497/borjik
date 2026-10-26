<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Support\MobileNumber;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->orderByDesc('is_active')
            ->orderBy('units_limit')
            ->orderBy('duration_months')
            ->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.form', ['plan' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        SubscriptionPlan::create($data);

        return redirect()->route('admin.plans.index')->with('status', 'پلن جدید ذخیره شد.');
    }

    public function edit(int $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, int $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($this->validated($request, $plan->id));

        return redirect()->route('admin.plans.index')->with('status', 'پلن به‌روز شد.');
    }

    public function destroy(int $id)
    {
        $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            // به‌جای حذف سخت، غیرفعال می‌کنیم تا تاریخچه حفظ شود
            $plan->update(['is_active' => false]);

            return redirect()->route('admin.plans.index')
                ->with('warning', 'این پلن قبلاً استفاده شده؛ به‌جای حذف، غیرفعال شد.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('status', 'پلن حذف شد.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'price' => MobileNumber::toLatinDigits((string) $request->input('price', '0')),
            'units_limit' => MobileNumber::toLatinDigits((string) $request->input('units_limit', '0')),
            'duration_months' => MobileNumber::toLatinDigits((string) $request->input('duration_months', '1')),
            'discount_value' => MobileNumber::toLatinDigits((string) $request->input('discount_value', '0')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('subscription_plans', 'name')->ignore($ignoreId)],
            'units_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'price' => ['required', 'integer', 'min:0'], // 0 = رایگان
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'discount_starts_at' => ['nullable', 'string', 'max:20'],
            'discount_ends_at' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $discountStart = JalaliDate::parse($request->input('discount_starts_at'));
        $discountEnd = JalaliDate::parse($request->input('discount_ends_at'));
        if ($request->filled('discount_starts_at') && ! $discountStart) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_starts_at' => 'تاریخ شروع شمسی معتبر نیست.']);
        }
        if ($request->filled('discount_ends_at') && ! $discountEnd) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_ends_at' => 'تاریخ پایان شمسی معتبر نیست.']);
        }
        if ($discountStart && $discountEnd && $discountEnd->lt($discountStart)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_ends_at' => 'تاریخ پایان نباید قبل از تاریخ شروع باشد.']);
        }
        $data['discount_starts_at'] = $discountStart?->startOfDay();
        $data['discount_ends_at'] = $discountEnd?->endOfDay();

        if (($data['discount_type'] ?? null) === 'percent' && (int) ($data['discount_value'] ?? 0) > 100) {
            throw \\Illuminate\\Validation\\ValidationException::withMessages(['discount_value' => 'تخفیف درصدی نمی‌تواند بیشتر از ۱۰۰ باشد.']);
        }
        $data['discount_value'] = (int) ($data['discount_value'] ?? 0);
        if (! $data['discount_type'] || $data['discount_value'] === 0) {
            $data['discount_type'] = null;
            $data['discount_value'] = 0;
            $data['discount_starts_at'] = null;
            $data['discount_ends_at'] = null;
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['price'] = (int) $data['price'];
        $data['units_limit'] = (int) $data['units_limit'];
        $data['duration_months'] = (int) $data['duration_months'];

        return $data;
    }
}
