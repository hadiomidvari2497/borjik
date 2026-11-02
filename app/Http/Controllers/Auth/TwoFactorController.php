<?php

namespace App\\Http\\Controllers\\Auth;

use App\\Exceptions\\OtpException;
use App\\Http\\Controllers\\Controller;
use App\\Models\\User;
use App\\Services\\Auth\\OtpService;
use App\\Support\\MobileNumber;
use App\\Support\\Totp;
use Illuminate\\Http\\Request;

class TwoFactorController extends Controller
{
    // ---- مرحله‌ی دوم ورود با رمز عبور
    public function challenge(Request $request)
    {
        abort_unless($request->session()->has('2fa_user_id'), 403);

        return view('auth.two-factor', ['mode' => 'challenge']);
    }

    public function verifyChallenge(Request $request)
    {
        $user = $this->challengeUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['mobile' => 'حساب کاربری فعال نیست.']);
        }

        $code = MobileNumber::toLatinDigits((string) $request->input('code'));

        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $code)) {
            return back()->withErrors(['code' => 'کد تأیید نادرست است.']);
        }

        $request->session()->forget(['2fa_user_id', '2fa_sms_requested']);

        return PasswordLoginController::finish($request, $user);
    }

    /**
     * ارسال کد جایگزین از طریق همان قالب OTP ورود عادی؛ بنابراین متن پیامک دقیقاً
     * از همان قالب سیستمی/مدیریتی «کد ورود» خوانده می‌شود.
     */
    public function requestSmsCode(Request $request, OtpService $otp)
    {
        $user = $this->challengeUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['mobile' => 'حساب کاربری فعال نیست.']);
        }

        try {
            $otp->issue($user->mobile, 'two_factor', $user->id);
        } catch (OtpException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $request->session()->put('2fa_sms_requested', true);

        return back()->with('status', 'کد تأیید دومرحله‌ای از طریق پیامک ارسال شد.');
    }

    public function verifySmsCode(Request $request, OtpService $otp)
    {
        $user = $this->challengeUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['mobile' => 'حساب کاربری فعال نیست.']);
        }

        if (! $request->session()->boolean('2fa_sms_requested')) {
            return back()->withErrors(['code' => 'ابتدا درخواست ارسال کد پیامکی را ثبت کنید.']);
        }

        $code = MobileNumber::toLatinDigits((string) $request->input('code'));

        if (! preg_match('/^\\d{6}$/', $code) || ! $otp->verify($user->mobile, $code, 'two_factor')) {
            return back()->withErrors(['code' => 'کد پیامکی نادرست یا منقضی است.']);
        }

        $request->session()->forget(['2fa_user_id', '2fa_sms_requested']);

        return PasswordLoginController::finish($request, $user);
    }

    private function challengeUser(Request $request): ?User
    {
        $userId = $request->session()->get('2fa_user_id');
        if (! $userId) {
            abort(403);
        }

        $user = User::find($userId);
        if (! $user || $user->status !== 'active') {
            $request->session()->forget(['2fa_user_id', '2fa_sms_requested']);

            return null;
        }

        return $user;
    }

    // ---- فعال‌سازی و غیرفعال‌سازی (کاربر واردشده)
    public function setup(Request $request)
    {
        $secret = $request->session()->get('2fa_setup_secret') ?? tap(Totp::generateSecret(), fn ($s) => $request->session()->put('2fa_setup_secret', $s));

        return view('auth.two-factor', [
            'mode' => 'setup',
            'secret' => $secret,
            'uri' => Totp::uri($secret, $request->user()->mobile),
        ]);
    }

    public function confirm(Request $request)
    {
        $secret = $request->session()->get('2fa_setup_secret');
        $code = MobileNumber::toLatinDigits((string) $request->input('code'));

        if (! $secret || ! Totp::verify($secret, $code)) {
            return back()->withErrors(['code' => 'کد تأیید نادرست است.']);
        }

        $request->user()->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => true])->save();
        $request->session()->forget('2fa_setup_secret');

        return redirect()->route('profile.edit')->with('status', 'تأیید دومرحله‌ای فعال شد.');
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        if (! $user->password || ! password_verify((string) $request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'رمز عبور نادرست است.']);
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_enabled' => false])->save();

        return redirect()->route('profile.edit')->with('status', 'تأیید دومرحله‌ای غیرفعال شد.');
    }
}
