<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MobileNumber;
use App\Services\Auth\LoginDestinationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordLoginController extends Controller
{
    public function store(Request $request)
    {
        if (! SiteSetting::passwordLoginEnabled()) {
            return back()->withErrors(['mobile' => 'ورود با رمز عبور در حال حاضر غیرفعال است.'])->withInput();
        }

        $mobile = MobileNumber::normalize($request->input('mobile'));
        $password = (string) $request->input('password');

        $user = $mobile ? User::where('mobile', $mobile)->where('status', 'active')->first() : null;

        if (! $user || ! $user->password || ! password_verify($password, $user->password)) {
            return back()->withErrors(['mobile' => 'شماره یا رمز عبور نادرست است.'])->withInput();
        }

        if ($user->two_factor_enabled) {
            $request->session()->put('2fa_user_id', $user->id);

            return redirect()->route('two-factor.challenge');
        }

        return $this->finish($request, $user);
    }

    public static function finish(Request $request, User $user)
    {
        if ($user->status !== 'active') {
            return redirect()->route('login')->withErrors(['mobile' => 'حساب کاربری فعال نیست.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        app(LoginDestinationService::class)->applyAfterLogin($request, $user);

        return redirect()->route('dashboard');
    }
}
