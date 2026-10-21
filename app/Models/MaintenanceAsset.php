<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAsset extends Model
{
    use BelongsToBuilding, Auditable;
    protected $guarded = ['id'];
    protected function casts(): array { return ['installed_at' => 'date', 'warranty_until' => 'date', 'is_active' => 'boolean']; }
}
