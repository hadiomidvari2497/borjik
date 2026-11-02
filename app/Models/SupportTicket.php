<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'first_response_warning_sent_at' => 'datetime',
            'first_response_breached_at' => 'datetime',
            'resolution_warning_sent_at' => 'datetime',
            'resolution_breached_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function assignedAdmin(): BelongsTo { return $this->belongsTo(AdminUser::class, 'assigned_admin_id'); }
    public function messages(): HasMany { return $this->hasMany(SupportMessage::class, 'support_ticket_id'); }
}
