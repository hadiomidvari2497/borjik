<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Block extends Model
{
    use BelongsToBuilding, SoftDeletes;

    protected $table = 'blocks';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }

    public function units() { return $this->hasMany(Unit::class); }
    public function parkings() { return $this->hasMany(Parking::class); }
    public function storages() { return $this->hasMany(Storage::class); }
}
