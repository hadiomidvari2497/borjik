<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    protected $table = 'coupon_redemptions';
    protected $guarded = ['id'];

    public function coupon()
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
