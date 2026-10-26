<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCoupon extends Model
{
    protected $table = 'discount_coupons';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'plan_ids' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id');
    }

    public function isAvailableFor(int $userId, int $planId, int $baseAmount): bool
    {
        $now = now();

        if (! $this->is_active
            || ($this->starts_at && $this->starts_at->isFuture())
            || ($this->ends_at && $this->ends_at->isPast())
            || $baseAmount < (int) $this->min_order_amount
            || ($this->plan_ids && ! in_array($planId, $this->plan_ids))) {
            return false;
        }

        $activeRedemptions = $this->redemptions()->whereIn('status', ['reserved', 'paid']);

        if ($this->max_uses !== null && (clone $activeRedemptions)->count() >= $this->max_uses) {
            return false;
        }

        return (clone $activeRedemptions)->where('user_id', $userId)->count() < max(1, (int) $this->max_uses_per_user);
    }

    public function discountFor(int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $discount = $this->discount_type === 'percent'
            ? (int) floor($amount * min(100, (int) $this->discount_value) / 100)
            : (int) $this->discount_value;

        return min($amount, max(0, $discount));
    }
}
