<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\SmsLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate(['days' => ['nullable', Rule::in(['7', '30', '90'])]]);
        $days = (int) ($validated['days'] ?? 30);
        $now = now();
        $since = $now->copy()->subDays($days - 1)->startOfDay();

        // ۷ و ۳۰ روز به‌صورت روزانه و بازه ۹۰ روزه به‌صورت هفتگی نمایش داده می‌شود.
        $weekly = $days === 90;
        $bucketSize = $weekly ? 7 : 1;
        $bucketCount = $weekly ? 13 : $days;
        $trend = [];

        for ($i = 0; $i < $bucketCount; $i++) {
            $start = $since->copy()->addDays($i * $bucketSize)->startOfDay();
            $end = $start->copy()->addDays($bucketSize)->subSecond();
            if ($end->greaterThan($now)) {
                $end = $now->copy();
            }

            $trend[] = [
                'label' => $start->format('m/d'),
                'users' => 0,
                'buildings' => 0,
            ];
        }

        $userDates = User::query()
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $now)
            ->pluck('created_at');
        $buildingDates = Building::query()
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $now)
            ->pluck('created_at');

        foreach ($userDates as $createdAt) {
            $index = min($bucketCount - 1, max(0, (int) floor($since->diffInDays($createdAt) / $bucketSize)));
            $trend[$index]['users']++;
        }

        foreach ($buildingDates as $createdAt) {
            $index = min($bucketCount - 1, max(0, (int) floor($since->diffInDays($createdAt) / $bucketSize)));
            $trend[$index]['buildings']++;
        }

        $trendMax = max(1, ...array_map(fn ($item) => max($item['users'], $item['buildings']), $trend));
        $trend = array_map(fn ($item) => [
            ...$item,
            'users_height' => max(3, (int) round($item['users'] / $trendMax * 100)),
            'buildings_height' => max(3, (int) round($item['buildings'] / $trendMax * 100)),
        ], $trend);

        $activeSubs = Subscription::query()->where('status', 'active')->where('expires_at', '>', $now);

        // فقط آخرین اشتراک هر کاربر را بررسی کن تا اشتراک‌های تاریخی هشدار تکراری نسازند.
        $latestSubscriptionIds = Subscription::query()->selectRaw('MAX(id)')->groupBy('user_id');
        $expiredSubscriptions = Subscription::query()
            ->whereIn('id', $latestSubscriptionIds)
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
            'newUsers' => User::where('created_at', '>=', $since)->where('created_at', '<=', $now)->count(),
            'buildingsCount' => Building::count(),
            'newBuildings' => Building::where('created_at', '>=', $since)->where('created_at', '<=', $now)->count(),
            'activeSubs' => (clone $activeSubs)->count(),
            'expiringSoon' => (clone $activeSubs)->where('expires_at', '<=', $now->copy()->addDays(7))->count(),
            'plansCount' => SubscriptionPlan::count(),
            'activePlans' => SubscriptionPlan::where('is_active', true)->count(),
            'expiredSubscriptions' => $expiredSubscriptions,
            'buildingsWithoutValidSubscription' => $buildingsWithoutValidSubscription,
            'failedSmsLast24Hours' => $failedSmsLast24Hours,
            'trendDays' => $days,
            'trend' => $trend,
            'trendMax' => $trendMax,
            'trendIsWeekly' => $weekly,
        ]);
    }
}
