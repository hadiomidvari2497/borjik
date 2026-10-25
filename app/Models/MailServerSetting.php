<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailServerSetting extends Model
{
    protected $guarded = ['id'];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'imap_host' => 'mail.sabkhoneh.ir',
            'imap_port' => 993,
            'smtp_host' => 'mail.sabkhoneh.ir',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ]);
    }
}
