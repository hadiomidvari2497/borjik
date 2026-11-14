<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyListingTourRoom extends Model
{
    protected $fillable = ['property_listing_id', 'name', 'image_path', 'sort_order'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }
}
