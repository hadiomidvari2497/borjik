<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    use BelongsToBuilding;

    protected $table = 'poll_votes';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function poll() { return $this->belongsTo(Poll::class, 'poll_id'); }
    public function option() { return $this->belongsTo(PollOption::class, 'option_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
}
