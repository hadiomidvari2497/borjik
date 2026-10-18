<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class PhonebookEntry extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'phonebook_entries';

    protected $guarded = ['id'];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
