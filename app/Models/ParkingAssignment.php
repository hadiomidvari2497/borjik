<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class ParkingAssignment extends Model
{
    use BelongsToBuilding;

    protected $table = 'parking_assignments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function parking() { return $this->belongsTo(Parking::class, 'parking_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
