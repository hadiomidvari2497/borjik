<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/** سقف اشتراک روی مجموع واحدهای همه‌ی ساختمان‌های همان اشتراک اعمال می‌شود، نه تعداد ساختمان. */
class SubscriptionLimit
{
    public function used(Subscription $subscription): int
    {
        return DB::table('units')
            ->join('buildings', 'buildings.id', '=', 'units.building_id')
            ->where('buildings.subscription_id', $subscription->id)
            ->whereNull('units.deleted_at')
            ->count();
    }

    public function remaining(Subscription $subscription): int
    {
        return max(0, $subscription->units_limit - $this->used($subscription));
    }

    public function canAddUnits(Subscription $subscription, int $count): bool
    {
        return $subscription->isActive() && $this->remaining($subscription) >= $count;
    }
}
