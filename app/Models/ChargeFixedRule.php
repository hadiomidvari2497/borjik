<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class ChargeFixedRule extends Model
{
    use BelongsToBuilding, Auditable;

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
