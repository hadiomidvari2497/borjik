<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationsHealthCheck extends Model
{
    protected $table = 'operations_health_checks';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checks' => 'array'];
    }
}
