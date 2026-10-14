<?php

namespace App\Models;

use App\Enums\Direction;
use App\Enums\UnitStatus;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use BelongsToBuilding, SoftDeletes;

    protected $table = 'units';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'area_m2' => 'decimal:2',
            'direction' => Direction::class,
            'status' => UnitStatus::class,
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function block() { return $this->belongsTo(Block::class, 'block_id'); }

    public function parties() { return $this->hasMany(UnitParty::class); }
    public function activeParties() { return $this->hasMany(UnitParty::class)->whereNull('to_date'); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function parkingAssignments() { return $this->hasMany(ParkingAssignment::class); }
    public function storageAssignments() { return $this->hasMany(StorageAssignment::class); }
}
