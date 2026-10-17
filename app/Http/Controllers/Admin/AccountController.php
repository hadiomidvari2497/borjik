<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\MobileNumber;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** حساب خود ادمین: تغییر رمز عبور و تأیید دومرحله‌ای (برای همهٔ ادمین‌ها، بدون نیاز به مجوز خاص). */
class AccountController extends Controller
{
    private function me(): AdminUser
    {
        /** @var AdminUser */
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
        ], [
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ]);

        if (! Hash::check($data['current_password'], $me->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی نادرست است.']);
        }

        $me->update(['password' => $data['password']]);

        return redirect()->route('admin.account')->with('status', 'رمز عبور تغییر کرد.');
    }

    public function twoFactorSetup(Request $request)
    {
        $me = $this->me();
        if ($me->two_factor_enabled) {
            return redirect()->route('admin.account');
        }

        $secret = $request->session()->get('admin_2fa_setup_secret')
            ?? tap(Totp::generateSecret(), fn ($s) => $request->session()->put('admin_2fa_setup_secret', $s));

        return view('admin.account.two-factor', [
            'secret' => $secret,
            'uri' => Totp::uri($secret, $me->username),
        ]);
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

    public function twoFactorDisable(Request $request)
    {
        $me = $this->me();

        if (! Hash::check((string) $request->input('password'), $me->password)) {
            return back()->withErrors(['password' => 'رمز عبور نادرست است.']);
        }

        $me->forceFill(['two_factor_secret' => null, 'two_factor_enabled' => false])->save();

        return redirect()->route('admin.account')->with('status', 'تأیید دومرحله‌ای غیرفعال شد.');
    }
}
