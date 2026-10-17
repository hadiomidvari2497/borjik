<?php

namespace App\Providers;

use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\SmsGateway;
use App\Support\MobileNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, function () {
            return match (config('sms.driver')) {
                'log' => new LogSmsGateway(),
                default => throw new \RuntimeException('درایور پیامک «'.config('sms.driver').'» پیاده‌سازی نشده است.'),
            };
        });
    }

    public function boot(): void
    {
        // درخواست کد: ۵ بار در دقیقه برای هر IP و ۳ بار در دقیقه برای هر شماره
        RateLimiter::for('otp-send', fn (Request $r) => [
            Limit::perMinute(5)->by('ip:'.$r->ip()),
            Limit::perMinute(3)->by('m:'.(MobileNumber::normalize($r->input('mobile')) ?? $r->ip())),
        ]);

        // تأیید کد و ورود با رمز
        RateLimiter::for('otp-verify', fn (Request $r) => Limit::perMinute(10)->by('ip:'.$r->ip()));

        // ورود به پنل مدیریت (رمز و کد ۲FA): سخت‌گیرانه‌تر از کاربران عادی
        RateLimiter::for('admin-login', fn (Request $r) => Limit::perMinute(8)->by('admin-ip:'.$r->ip()));
    }
}
