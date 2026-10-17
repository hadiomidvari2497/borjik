<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function scopeForBuilding($query, int $buildingId)
    {
        return $query->where(function ($q) use ($buildingId) {
            $q->where(function ($global) { $global->whereNull('building_id')->whereIn('key', ['owner', 'manager', 'unit_owner', 'tenant']); })
              ->orWhere('building_id', $buildingId);
        });
    }


    public function permissions() { return $this->belongsToMany(Permission::class, 'role_permission'); }
}
