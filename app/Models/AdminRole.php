<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    protected $table = 'admin_roles';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }


}
