<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MobileNumber;
use App\Support\Totp;
use Illuminate\Http\Request;

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
        $user = User::findOrFail((int) $request->session()->get('2fa_user_id'));

        if ($user->status !== 'active') {
            $request->session()->forget('2fa_user_id');

            return redirect()->route('login')->withErrors(['mobile' => 'حساب کاربری فعال نیست.']);
        }

        $code = MobileNumber::toLatinDigits((string) $request->input('code'));

        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $code)) {
            return back()->withErrors(['code' => 'کد تأیید نادرست است.']);
        }

        $request->session()->forget('2fa_user_id');

        return PasswordLoginController::finish($request, $user);
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
