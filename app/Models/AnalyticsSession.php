<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsSession extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'ended_at' => 'datetime',
            'engaged' => 'boolean',
            'pageviews' => 'integer',
            'active_seconds' => 'integer',
            'screen_width' => 'integer',
            'screen_height' => 'integer',
            'viewport_width' => 'integer',
            'viewport_height' => 'integer',
            'pixel_ratio' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
