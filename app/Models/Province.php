<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'provinces';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return [

        ];
    }


}
