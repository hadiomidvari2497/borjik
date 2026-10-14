<?php

namespace App\Models;

use App\Enums\ChargeMethod;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class ChargeSetting extends Model
{
    use BelongsToBuilding;

    protected $table = 'charge_settings';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'method' => ChargeMethod::class,
            'late_fee_enabled' => 'boolean',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
}
