<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_logs';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
