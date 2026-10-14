<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class SupplyMovement extends Model
{
    use BelongsToBuilding;

    protected $table = 'supply_movements';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'delta' => 'decimal:2',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function supply() { return $this->belongsTo(Supply::class, 'supply_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
