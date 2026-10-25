<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /** پیامک را ارسال می‌کند و شناسه‌ی پیگیری ارائه‌دهنده را برمی‌گرداند. در خطا Exception بدهد. */
    public function send(string $mobile, string $text, ?string $templateKey = null, array $templateVars = []): string;
}
