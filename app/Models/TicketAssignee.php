<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class TicketAssignee extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'ticket_assignees';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
