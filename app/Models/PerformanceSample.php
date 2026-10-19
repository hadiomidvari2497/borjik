<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceSample extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'sampled_at' => 'datetime',
        ];
    }
}
