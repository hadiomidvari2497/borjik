<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class TicketStatusLog extends Model
{
    use BelongsToBuilding;

    protected $table = 'ticket_status_logs';
    protected $guarded = ['id'];
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
