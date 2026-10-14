<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use BelongsToBuilding;

    protected $table = 'events';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
