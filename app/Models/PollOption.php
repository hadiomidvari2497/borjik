<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    use BelongsToBuilding;

    protected $table = 'poll_options';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function poll() { return $this->belongsTo(Poll::class, 'poll_id'); }
}
