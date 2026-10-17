<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class Parking extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'parkings';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function block() { return $this->belongsTo(Block::class, 'block_id'); }

    public function assignments() { return $this->hasMany(ParkingAssignment::class); }

    /** اختصاص باز (آزادنشده)؛ حداکثر یکی. */
    public function openAssignment() { return $this->hasOne(ParkingAssignment::class)->whereNull('released_at'); }
}
