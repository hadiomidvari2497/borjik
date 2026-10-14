<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
        ];
    }



    public function permissions() { return $this->belongsToMany(Permission::class, 'role_permission'); }
}
