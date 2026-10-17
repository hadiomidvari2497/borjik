<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class Document extends Model
{
    use BelongsToBuilding, SoftDeletes, Auditable;

    protected $table = 'documents';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
