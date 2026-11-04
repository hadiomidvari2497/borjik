<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class PropertyListingCreditTransaction extends Model
{
    protected $fillable = [
        'user_id', 'package_order_id', 'property_listing_id', 'quantity', 'type', 'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}