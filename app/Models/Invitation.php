<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class Invitation extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'invitations';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function role() { return $this->belongsTo(Role::class, 'role_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
