<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'plan_id'); }

    public function buildings() { return $this->hasMany(Building::class); }
    public function isActive(): bool { return $this->status === 'active' && $this->expires_at->isFuture(); }
}
