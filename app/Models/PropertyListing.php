<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyListing extends Model
{
    protected $fillable = [
        'user_id', 'unit_id', 'listing_type', 'property_type', 'title', 'description',
        'province', 'city', 'neighborhood', 'address', 'hide_address', 'area',
        'bedrooms', 'bathrooms', 'floor', 'total_floors', 'built_year',
        'document_type', 'amenities', 'price', 'deposit', 'monthly_rent',
        'allow_exchange', 'photos', 'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'hide_address' => 'boolean',
            'allow_exchange' => 'boolean',
            'amenities' => 'array',
            'photos' => 'array',
            'expires_at' => 'datetime',
            'area' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
