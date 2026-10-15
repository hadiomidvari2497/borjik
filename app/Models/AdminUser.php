<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'two_factor_secret', 'remember_token'];

    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
