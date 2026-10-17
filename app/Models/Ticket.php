<?php

namespace App\Models;

use App\Enums\TicketKind;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class Ticket extends Model
{
    use BelongsToBuilding, SoftDeletes, Auditable;

    protected $table = 'tickets';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kind' => TicketKind::class,
            'priority' => TicketPriority::class,
            'deadline' => 'datetime',
            'status' => TicketStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function targetRole() { return $this->belongsTo(Role::class, 'target_role_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function acceptedBy() { return $this->belongsTo(User::class, 'accepted_by'); }

    public function assignees() { return $this->hasMany(TicketAssignee::class); }
    public function comments() { return $this->hasMany(TicketComment::class); }
    public function statusLogs() { return $this->hasMany(TicketStatusLog::class); }
    public function events() { return $this->hasMany(TicketEvent::class); }
    public function ratings() { return $this->hasMany(TicketRating::class); }
    public function attachments() { return $this->hasMany(TicketAttachment::class); }
}
