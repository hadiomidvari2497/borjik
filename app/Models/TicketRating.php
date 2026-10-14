<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class TicketRating extends Model
{
    use BelongsToBuilding;

    protected $table = 'ticket_ratings';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function ratedBy() { return $this->belongsTo(User::class, 'rated_by'); }
    public function ratedUser() { return $this->belongsTo(User::class, 'rated_user_id'); }
}
