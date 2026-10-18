<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSmsGateway implements SmsGateway
{
    public function label(): string
    {
        return 'log (شبیه‌سازی؛ بدون ارسال واقعی)';
    }

    public function send(string $mobile, string $text): string
    {
        // این درایور فقط برای توسعه است؛ متن و کدهای یک‌بارمصرف را وارد فایل لاگ نمی‌کنیم.
        Log::info('SMS simulated; no provider configured.', [
            'mobile' => $mobile,
            'message_length' => mb_strlen($text),
        ]);

        return 'log-'.Str::lower(Str::random(16));
    }
}
