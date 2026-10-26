<?php

namespace App\Services\Sms;

use App\Models\SmsProvider;
use Illuminate\Database\QueryException;

/** درایور پیامک جاری: اتصال پیش‌فرض فعال از پنل مدیریت؛ در غیر این صورت درایور config/sms.php. */
class SmsGatewayFactory
{
    public function make(): SmsGateway
    {
        $provider = $this->currentProvider();

        if ($provider) {
            if (str_contains(strtolower((string) $provider->settings()['url']), 'sendbybasenumber2')) {
                return new SmsRoutingGateway($provider);
            }

            return new HttpSmsGateway($provider);
        }

        return match (config('sms.driver')) {
            'log' => new LogSmsGateway(),
            default => throw new \RuntimeException('درایور پیامک «'.config('sms.driver').'» پیاده‌سازی نشده است.'),
        };
    }

    private function currentProvider(): ?SmsProvider
    {
        try {
            return SmsProvider::current();
        } catch (QueryException) {
            return null; // جدول هنوز مایگریت نشده
        }
    }
}
