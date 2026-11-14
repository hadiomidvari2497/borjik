<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Auth\OtpService;
use App\Support\MobileNumber;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    private function me(): AdminUser
    {
        return Auth::guard('admin')->user();
    }

    public function show()
    {
        return view('admin.account.show', ['me' => $this->me()]);
    }

    public function password(Request $request)
    {
        $me = $this->me();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ], ['password.confirmed' => 'تکرار رمز عبور یکسان نیست.', 'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.']);

        if (! Hash::check($data['current_password'], $me->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی نادرست است.']);
        }

        $me->update(['password' => $data['password']]);

        return redirect()->route('admin.account')->with('status', 'رمز عبور تغییر کرد.');
    }

    public function saveMobile(Request $request)
    {
        $mobile = MobileNumber::normalize($request->input('mobile'));
        if (! $mobile) {
            return back()->withErrors(['mobile' => 'شماره موبایل معتبر ایران را وارد کنید.'])->withInput();
        }

        $me = $this->me();
        if ($me->mobile !== $mobile) {
            $me->forceFill(['mobile' => $mobile, 'mobile_verified_at' => null, 'two_factor_sms_enabled' => false])->save();
        }

        return redirect()->route('admin.account')->with('status', 'شماره ذخیره شد؛ برای استفاده باید تأیید شود.');
    }

    public function requestMobileVerification(OtpService $otp)
    {
        $me = $this->me();
        if (! $me->mobile || $me->mobile_verified_at) {
            return back()->withErrors(['mobile_code' => 'ابتدا شماره تأییدنشده را ثبت کنید.']);
        }

        try {
            $otp->issue($me->mobile, 'admin_mobile_verify');
        } catch (OtpException $e) {
            return back()->withErrors(['mobile_code' => $e->getMessage()]);
        }

        return back()->with('status', 'کد تأیید پیامکی ارسال شد.');
    }

    public function verifyMobile(Request $request, OtpService $otp)
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $me = $this->me();
        if (! $me->mobile || ! $otp->verify($me->mobile, MobileNumber::toLatinDigits($data['code']), 'admin_mobile_verify')) {
            return back()->withErrors(['mobile_code' => 'کد نادرست یا منقضی شده است.']);
        }

        $me->forceFill(['mobile_verified_at' => now()])->save();

        return redirect()->route('admin.account')->with('status', 'شماره موبایل تأیید شد.');
    }

    public function toggleSmsTwoFactor(Request $request)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $me = $this->me();
        if ($data['enabled'] && (! $me->two_factor_enabled || ! $me->two_factor_secret || ! $me->mobile_verified_at)) {
            return back()->withErrors(['sms_two_factor' => 'ابتدا Google Authenticator و شماره موبایل را تأیید کنید.']);
        }

        $me->forceFill(['two_factor_sms_enabled' => (bool) $data['enabled']])->save();

        return redirect()->route('admin.account')->with('status', 'تنظیم ورود پیامکی ذخیره شد.');
    }

    public function twoFactorSetup(Request $request)
    {
        $me = $this->me();
        if ($me->two_factor_enabled) {
            return redirect()->route('admin.account');
        }

        $secret = $request->session()->get('admin_2fa_setup_secret')
            ?? tap(Totp::generateSecret(), fn ($s) => $request->session()->put('admin_2fa_setup_secret', $s));

        return view('admin.account.two-factor', ['secret' => $secret, 'uri' => Totp::uri($secret, $me->username)]);
    }

    public function twoFactorConfirm(Request $request)
    {
        $secret = $request->session()->get('admin_2fa_setup_secret');
        $code = MobileNumber::toLatinDigits((string) $request->input('code'));
        if (! $secret || ! Totp::verify($secret, $code)) {
            return back()->withErrors(['code' => 'کد تأیید نادرست است.']);
        }

        $this->me()->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => true])->save();
        $request->session()->forget('admin_2fa_setup_secret');

        return redirect()->route('admin.account')->with('status', 'تأیید دومرحله‌ای فعال شد.');
    }


    public function generateRecoveryCodes(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'authenticator_code' => ['required', 'string', 'size:6'],
        ]);
        $admin = $this->me();

        if (! $admin->two_factor_enabled || ! $admin->two_factor_secret) {
            return back()->withErrors(['recovery_codes' => 'ابتدا Google Authenticator را فعال کنید.']);
        }
        if (! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['password' => 'رمز عبور نادرست است.']);
        }
        if (! Totp::verify($admin->two_factor_secret, MobileNumber::toLatinDigits($data['authenticator_code']))) {
            return back()->withErrors(['authenticator_code' => 'کد Google Authenticator نادرست است.']);
        }

        $plainCodes = [];
        $hashedCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $compact = strtoupper(bin2hex(random_bytes(8)));
            $plainCodes[] = substr($compact, 0, 4).'-'.substr($compact, 4, 4).'-'.substr($compact, 8, 4).'-'.substr($compact, 12, 4);
            $hashedCodes[] = Hash::make($compact);
        }

        $admin->forceFill(['two_factor_recovery_codes' => $hashedCodes])->save();

        return redirect()->route('admin.account')->with('recovery_codes', $plainCodes)
            ->with('status', '۱۰ کد بازیابی جدید ساخته شد. این کدها فقط همین یک بار نمایش داده می‌شوند.');
    }

    public function twoFactorDisable(Request $request)
    {
        $me = $this->me();
        if (! Hash::check((string) $request->input('password'), $me->password)) {
            return back()->withErrors(['password' => 'رمز عبور نادرست است.']);
        }

        $me->forceFill(['two_factor_secret' => null, 'two_factor_enabled' => false, 'two_factor_sms_enabled' => false, 'two_factor_recovery_codes' => null])->save();

        return redirect()->route('admin.account')->with('status', 'تأیید دومرحله‌ای غیرفعال شد.');
    }
}
