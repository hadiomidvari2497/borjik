<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }



    public function subscriptions() { return $this->hasMany(Subscription::class, 'plan_id'); }

    public function effectivePrice(): int
    {
        $price = max(0, (int) $this->price);
        $now = now();

        if (! $this->discount_type || (int) $this->discount_value <= 0
            || ($this->discount_starts_at && $now->lt($this->discount_starts_at))
            || ($this->discount_ends_at && $now->gt($this->discount_ends_at))) {
            return $price;
        }

        $discount = $this->discount_type === 'percent'
            ? (int) floor($price * min(100, (int) $this->discount_value) / 100)
            : (int) $this->discount_value;

        return max(0, $price - min($price, max(0, $discount)));
    }

    public function discountIsActive(): bool
    {
        return $this->effectivePrice() < (int) $this->price;
    }
}
