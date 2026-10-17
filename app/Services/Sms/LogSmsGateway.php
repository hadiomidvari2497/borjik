<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function label(): string
    {
        return 'log';
    }

    public function send(string $mobile, string $text): string
    {
        Log::info("SMS → {$mobile}: {$text}");

        return 'log-'.uniqid();
    }
}
