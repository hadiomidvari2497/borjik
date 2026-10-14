<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'files';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function uploader() { return $this->belongsTo(User::class, 'uploader_id'); }
    public function building() { return $this->belongsTo(Building::class, 'building_id'); }
}
