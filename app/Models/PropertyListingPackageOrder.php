<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class PropertyListingPackageOrder extends Model
{
    protected $fillable = [
        'user_id', 'quantity', 'amount', 'gateway', 'gateway_id', 'authority',
        'id_get', 'ref_id', 'status', 'paid_at', 'raw_response',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'raw_response' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}