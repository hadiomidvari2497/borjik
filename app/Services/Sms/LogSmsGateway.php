<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function label(): string
    {
        return 'log (بدون ارسال واقعی)';
    }

    public function send(string $mobile, string $text, ?string $templateKey = null, array $templateVars = []): string
    {
        // درایور log ارسال واقعی انجام نمی‌دهد؛ فقط متادیتا را ثبت می‌کند و هرگز متن/OTP را لاگ نمی‌کند.
        Log::warning('SMS was not sent because no real provider is configured.', [
            'mobile' => $mobile,
            'message_length' => mb_strlen($text),
        ]);

        throw new SmsException('هیچ پنل پیامکی فعال نیست؛ درایور log پیامک واقعی ارسال نمی‌کند.');
    }
}
