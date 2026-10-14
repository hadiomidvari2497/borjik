<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileLink extends Model
{
    protected $table = 'file_links';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function file() { return $this->belongsTo(File::class, 'file_id'); }
}
