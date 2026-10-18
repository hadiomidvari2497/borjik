<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Building;
use App\\Models\\SmsLog;
use App\\Models\\Subscription;
use App\\Models\\SubscriptionPlan;
use App\\Models\\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $now = now();
        $since = $now->copy()->subDays(30);
        $activeSubs = Subscription::query()->where('status', 'active')->where('expires_at', '>', $now);

        $expiredSubscriptions = Subscription::query()
            ->where(function ($query) use ($now) {
                $query->where('status', '!=', 'active')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '<=', $now);
            })->count();

        $buildingsWithoutValidSubscription = Building::query()
            ->where(function ($query) use ($now) {
                $query->whereNull('subscription_id')
                    ->orWhereDoesntHave('subscription', fn ($subscription) => $subscription
                        ->where('status', 'active')
                        ->where('expires_at', '>', $now));
            })->count();

        $failedSmsLast24Hours = SmsLog::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', $now->copy()->subDay())
            ->count();

        return view('admin.dashboard', [
            'usersCount' => User::count(),
            'newUsers' => User::where('created_at', '>=', $since)->count(),
            'buildingsCount' => Building::count(),
            'newBuildings' => Building::where('created_at', '>=', $since)->count(),
            'activeSubs' => (clone $activeSubs)->count(),
            'expiringSoon' => (clone $activeSubs)->where('expires_at', '<=', $now->copy()->addDays(7))->count(),
            'plansCount' => SubscriptionPlan::count(),
            'activePlans' => SubscriptionPlan::where('is_active', true)->count(),
            'expiredSubscriptions' => $expiredSubscriptions,
            'buildingsWithoutValidSubscription' => $buildingsWithoutValidSubscription,
            'failedSmsLast24Hours' => $failedSmsLast24Hours,
        ]);
    }
}
