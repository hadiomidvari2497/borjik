<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    use BelongsToBuilding;

    protected $table = 'ticket_comments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function replyTo() { return $this->belongsTo(self::class, 'reply_to_id'); }
    public function replies() { return $this->hasMany(self::class, 'reply_to_id'); }
}
