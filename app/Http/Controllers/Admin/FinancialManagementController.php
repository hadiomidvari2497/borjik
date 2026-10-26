<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialPriceAdjustment;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialManagementController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $stats = [
            'revenue_today' => SubscriptionPayment::where('status', 'paid')->where('paid_at', '>=', $today)->sum('amount'),
            'revenue_month' => SubscriptionPayment::where('status', 'paid')->where('paid_at', '>=', $month)->sum('amount'),
            'paid_count' => SubscriptionPayment::where('status', 'paid')->count(),
            'pending_count' => SubscriptionPayment::where('status', 'pending')->count(),
            'failed_count' => SubscriptionPayment::where('status', 'failed')->count(),
            'active_plans' => SubscriptionPlan::where('is_active', true)->count(),
        ];

        $recentAdjustments = FinancialPriceAdjustment::query()->latest('id')->limit(10)->get();

        return view('admin.finances.index', compact('stats', 'recentAdjustments'));
    }

    public function bulkEdit()
    {
        return view('admin.finances.bulk-edit');
    }

    public function bulkAdjust(Request $request)
    {
        $request->merge([
            'value' => \App\Support\MobileNumber::toLatinDigits((string) $request->input('value', '')),
        ]);

        $data = $request->validate([
            'adjustment_type' => ['required', 'in:increase_percent,decrease_percent,increase_fixed,decrease_fixed'],
            'value' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
            'scope' => ['required', 'in:all,active'],
        ]);

        if (str_ends_with($data['adjustment_type'], '_percent') && (float) $data['value'] > 100) {
            return back()->withErrors(['value' => 'درصد تغییر باید حداکثر ۱۰۰ باشد.'])->withInput();
        }

        $before = [];
        $after = [];
        DB::transaction(function () use ($data, &$before, &$after) {
            $query = SubscriptionPlan::query()->where('price', '>', 0);
            if ($data['scope'] === 'active') {
                $query->where('is_active', true);
            }
            $plans = $query->lockForUpdate()->get();
            if ($plans->isEmpty()) {
                return;
            }

            foreach ($plans as $plan) {
                $old = (int) $plan->price;
                $value = (float) $data['value'];
                $new = match ($data['adjustment_type']) {
                    'increase_percent' => (int) round($old * (1 + $value / 100)),
                    'decrease_percent' => (int) round($old * (1 - $value / 100)),
                    'increase_fixed' => (int) round($old + $value),
                    'decrease_fixed' => (int) max(0, round($old - $value)),
                };
                $before[$plan->id] = $old;
                $after[$plan->id] = $new;
                $plan->update(['price' => $new]);
            }

            FinancialPriceAdjustment::create([
                'adjustment_type' => $data['adjustment_type'],
                'adjustment_value' => $data['value'],
                'affected_plans' => count($before),
                'details' => ['scope' => $data['scope'], 'before' => $before, 'after' => $after],
                'admin_user_id' => auth('admin')->id(),
            ]);
        });

        if ($after === []) {
            return back()->with('warning', 'پلنی با قیمت مثبت در محدوده انتخاب‌شده وجود ندارد.');
        }

        return redirect()->route('admin.finances.index')->with('status', 'قیمت پایه '.count($after).' پلن با موفقیت به‌روزرسانی شد. تخفیف‌های فعال هر پلن جداگانه محاسبه می‌شوند.');
    }
}
