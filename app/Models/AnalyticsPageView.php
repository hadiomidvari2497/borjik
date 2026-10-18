<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsPageView extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'is_engaged' => 'boolean',
            'duration_seconds' => 'integer',
            'active_seconds' => 'integer',
        ];
    }
}
