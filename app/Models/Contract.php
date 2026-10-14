<?php

namespace App\Models;

use App\Enums\ChargePayer;
use App\Enums\ContractType;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use BelongsToBuilding, SoftDeletes;

    protected $table = 'contracts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'signed_at' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'charge_payer' => ChargePayer::class,
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
