<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketEvent extends Model
{
    protected $table = 'support_ticket_events';
    protected $guarded = ['id'];

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function actorAdmin(): BelongsTo { return $this->belongsTo(AdminUser::class, 'actor_admin_id'); }
    public function fromAdmin(): BelongsTo { return $this->belongsTo(AdminUser::class, 'from_admin_id'); }
    public function toAdmin(): BelongsTo { return $this->belongsTo(AdminUser::class, 'to_admin_id'); }
}
