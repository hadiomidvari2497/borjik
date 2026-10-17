<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class SmsTemplate extends Model
{
    use Auditable;

    protected $table = 'sms_templates';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
}
