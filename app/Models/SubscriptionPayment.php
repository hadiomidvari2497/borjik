<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $table = 'subscription_payments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function subscription() { return $this->belongsTo(Subscription::class, 'subscription_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
