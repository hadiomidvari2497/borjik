<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiActionRequest extends Model
{
    protected $table = 'ai_action_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'approved_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
