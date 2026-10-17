<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use BelongsToBuilding;

    protected $table = 'ticket_attachments';
    protected $guarded = ['id'];

    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
