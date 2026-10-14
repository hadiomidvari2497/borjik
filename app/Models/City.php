<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return [

        ];
    }

    public function province() { return $this->belongsTo(Province::class, 'province_id'); }
}
