<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class BuildingUserRole extends Model
{
    use Auditable;

    protected $table = 'building_user_roles';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function role() { return $this->belongsTo(Role::class, 'role_id'); }
    public function invitedBy() { return $this->belongsTo(User::class, 'invited_by'); }
}
