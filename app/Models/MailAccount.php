<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailAccount extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'is_active' => 'boolean'];
    }


    public function localPart(): string
    {
        return (string) strstr($this->email, '@', true);
    }
}
