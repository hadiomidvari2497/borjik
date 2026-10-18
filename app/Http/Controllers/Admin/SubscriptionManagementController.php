<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $latinQ = MobileNumber::toLatinDigits($q);
        $like = '%'.addcslashes($latinQ, '%_\').'%';

        $users = User::query()
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($like) {
                $where->where('mobile', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('company_name', 'like', $like);
            }))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $latestSubscriptions = $users->isEmpty()
            ? collect()
            : Subscription::query()
                ->with('plan')
                ->whereIn('user_id', $users->getCollection()->pluck('id'))
                ->orderByDesc('id')
                ->get()
                ->groupBy('user_id')
                ->map(fn ($items) => $items->first());

        return view('admin.subscriptions.index', compact('users', 'q', 'latestSubscriptions'));
    }

    public function create(Request $request)
    {
        $user = User::findOrFail((int) $request->query('user_id'));
        $plans = SubscriptionPlan::query()
            ->orderByDesc('is_active')
            ->orderBy('price')
            ->orderBy('units_limit')
            ->get();
        $currentSubscription = Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        return view('admin.subscriptions.form', compact('user', 'plans', 'currentSubscription'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        $subscription = DB::transaction(function () use ($user, $plan) {
            // قفل کاربر و اشتراک‌های قبلی برای جلوگیری از ثبت دو اشتراک فعال هم‌زمان در درخواست‌های موازی.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $previousIds = Subscription::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->pluck('id');

            Subscription::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $newSubscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'units_limit' => $plan->units_limit,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                'status' => 'active',
            ]);

            // ساختمان‌های ساخته‌شده با اشتراک‌های قبلی کاربر، زیر پوشش اشتراک جدید باقی می‌مانند.
            if ($previousIds->isNotEmpty()) {
                Building::query()
                    ->whereIn('subscription_id', $previousIds)
                    ->update(['subscription_id' => $newSubscription->id]);
            }

            return $newSubscription;
        });

        return redirect()
            ->route('admin.subscriptions.index', array_filter(['q' => $data['q'] ?? null], fn ($value) => $value !== null && $value !== ''))
            ->with('status', 'اشتراک «'.$plan->name.'» برای '.$user->display_name.' فعال شد و تا '.$subscription->expires_at->format('Y-m-d').' اعتبار دارد.');
    }
}
