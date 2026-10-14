<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class UnitParty extends Model
{
    use BelongsToBuilding;

    protected $table = 'unit_parties';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function contract() { return $this->belongsTo(Contract::class, 'contract_id'); }
}
