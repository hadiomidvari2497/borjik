<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use BelongsToBuilding, SoftDeletes, Auditable;

    protected $table = 'documents';
    protected $guarded = ['id'];

    public function building() { return $this->belongsTo(Building::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function file() { return $this->belongsTo(File::class, 'file_id'); }
    public function roles() { return $this->belongsToMany(Role::class, 'document_roles'); }
}
