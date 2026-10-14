<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class ChargeFixedRule extends Model
{
    use BelongsToBuilding;

    protected $table = 'charge_fixed_rules';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'has_parking' => 'boolean',
            'has_storage' => 'boolean',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
}
