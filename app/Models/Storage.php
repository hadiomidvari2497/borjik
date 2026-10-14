<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    use BelongsToBuilding;

    protected $table = 'storages';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function block() { return $this->belongsTo(Block::class, 'block_id'); }

    public function assignments() { return $this->hasMany(StorageAssignment::class); }

    /** اختصاص باز (آزادنشده)؛ حداکثر یکی. */
    public function openAssignment() { return $this->hasOne(StorageAssignment::class)->whereNull('released_at'); }
}
