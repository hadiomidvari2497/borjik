<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class Poll extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'polls';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function options() { return $this->hasMany(PollOption::class); }
    public function votes() { return $this->hasMany(PollVote::class); }
}
