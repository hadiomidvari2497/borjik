<?php

namespace App\Services\Auth;

use App\Exceptions\OtpException;
use App\Models\LoginOtp;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\DB;

/**
 * کد یکبارمصرف ۶ رقمی روی جدول login_otps.
 * - فقط هش (HMAC با APP_KEY و شماره) ذخیره می‌شود، نه خود کد.
 * - اعتبار ۲ دقیقه، حداکثر ۵ تلاش برای هر کد، فاصله‌ی ۶۰ ثانیه بین دو درخواست، حداکثر ۵ درخواست در ساعت.
 */
class OtpService
{
    public const TTL_SECONDS = 120;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;
    public const HOURLY_LIMIT = 5;

    public function __construct(private readonly SmsSender $sms)
    {
    }

    public function issue(string $mobile, string $purpose = 'login', ?int $userId = null): void
    {
        $last = LoginOtp::where('mobile', $mobile)->where('purpose', $purpose)->latest('id')->first();
        if ($last && $last->created_at->diffInSeconds(now()) < self::RESEND_SECONDS) {
            throw new OtpException('لطفاً کمی صبر کنید و دوباره درخواست دهید.', self::RESEND_SECONDS - (int) $last->created_at->diffInSeconds(now()));
        }

        $hourly = LoginOtp::where('mobile', $mobile)->where('created_at', '>=', now()->subHour())->count();
        if ($hourly >= self::HOURLY_LIMIT) {
            throw new OtpException('تعداد درخواست کد برای این شماره زیاد است؛ بعداً تلاش کنید.', 3600);
        }

        $code = (string) random_int(100000, 999999);

        DB::transaction(function () use ($mobile, $purpose, $code) {
            LoginOtp::where('mobile', $mobile)->where('purpose', $purpose)->whereNull('used_at')->update(['used_at' => now()]);
            LoginOtp::create([
                'mobile' => $mobile,
                'purpose' => $purpose,
                'code_hash' => $this->hash($mobile, $purpose, $code),
                'attempts' => 0,
                'expires_at' => now()->addSeconds(self::TTL_SECONDS),
            ]);
        });

        $this->sms->sendTemplate('otp', $mobile, ['code' => $code], $userId);
    }

    public function verify(string $mobile, string $code, string $purpose = 'login'): bool
    {
        return DB::transaction(function () use ($mobile, $code, $purpose) {
            $otp = LoginOtp::where('mobile', $mobile)->where('purpose', $purpose)
                ->whereNull('used_at')->where('expires_at', '>', now())
                ->latest('id')->lockForUpdate()->first();

            if (! $otp) {
                return false;
            }

            $otp->attempts++;

            if ($otp->attempts > self::MAX_ATTEMPTS) {
                $otp->used_at = now();
                $otp->save();

                return false;
            }

            $ok = hash_equals($otp->code_hash, $this->hash($mobile, $purpose, $code));
            if ($ok) {
                $otp->used_at = now();
            }
            $otp->save();

            return $ok;
        });
    }

    private function hash(string $mobile, string $purpose, string $code): string
    {
        return hash_hmac('sha256', "{$mobile}|{$purpose}|{$code}", (string) config('app.key'));
    }
}
