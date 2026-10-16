<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class TicketEvent extends Model
{
    use BelongsToBuilding;

    protected $table = 'ticket_events';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
