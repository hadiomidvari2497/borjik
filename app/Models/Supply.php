<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use BelongsToBuilding;

    protected $table = 'supplies';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
}
