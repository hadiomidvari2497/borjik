<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportMessage extends Model
{
    protected $table = 'support_messages';
    protected $guarded = ['id'];

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }

    public function attachments(): HasMany { return $this->hasMany(SupportAttachment::class, 'support_message_id'); }

    public function attachments(): HasMany { return $this->hasMany(SupportAttachment::class, 'support_message_id'); }
}
