<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class SecurityShift extends Model
{
    use BelongsToBuilding;

    protected $table = 'security_shifts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
