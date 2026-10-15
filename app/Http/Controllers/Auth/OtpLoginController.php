<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvitationException;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\InvitationService;
use App\Services\Auth\LoginDestinationService;
use App\Services\Auth\OtpService;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpLoginController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.login', [
            'step' => $request->session()->has('otp_mobile') ? 'code' : 'mobile',
            'mobile' => $request->session()->get('otp_mobile'),
        ]);
    }

    public function send(Request $request, OtpService $otp)
    {
        $mobile = MobileNumber::normalize($request->input('mobile'));
        if (! $mobile) {
            return back()->withErrors(['mobile' => 'شماره‌ی موبایل معتبر نیست.'])->withInput();
        }

        $user = User::where('mobile', $mobile)->first();

        if ($user && $user->status === 'inactive') {
            return back()->withErrors(['mobile' => 'حساب این شماره غیرفعال است.']);
        }
        // با فعال بودن 2FA، ورود صرفاً با پیامک بسته است تا تأیید دومرحله‌ای دور زده نشود.
        if ($user?->two_factor_enabled) {
            return back()->withErrors(['mobile' => 'برای این حساب ورود با رمز عبور و کد تأیید دومرحله‌ای لازم است.']);
        }

        try {
            $otp->issue($mobile, 'login', $user?->id);
        } catch (OtpException $e) {
            return back()->withErrors(['mobile' => $e->getMessage()])->withInput();
        }

        $request->session()->put('otp_mobile', $mobile);
        if ($request->filled('invite')) {
            $request->session()->put('invite_token', $request->string('invite')->toString());
        }

        return redirect()->route('login');
    }

    public function verify(Request $request, OtpService $otp, InvitationService $invitations)
    {
        $mobile = $request->session()->get('otp_mobile');
        $code = MobileNumber::toLatinDigits((string) $request->input('code'));

        if (! $mobile || ! preg_match('/^\d{6}$/', $code) || ! $otp->verify($mobile, $code, 'login')) {
            return back()->withErrors(['code' => 'کد واردشده نادرست یا منقضی است.']);
        }

        $user = User::firstOrCreate(['mobile' => $mobile], ['status' => 'active']);
        $user->forceFill([
            'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            'status' => $user->status === 'invited' ? 'active' : $user->status,
            'last_login_at' => now(),
        ])->save();

        $inviteToken = $request->session()->pull('invite_token');
        $request->session()->forget('otp_mobile');

        Auth::login($user);
        $request->session()->regenerate();
        app(LoginDestinationService::class)->applyAfterLogin($request, $user);

        $flash = null;
        if ($inviteToken) {
            try {
                $invitation = $invitations->accept($inviteToken, $user);
                $membershipId = \Illuminate\Support\Facades\DB::table('building_user_roles')
                    ->where('user_id', $user->id)->where('building_id', $invitation->building_id)
                    ->where('role_id', $invitation->role_id)->where('status', 'active')->value('id');
                if ($membershipId) {
                    $request->session()->put('current_building_user_role_id', (int) $membershipId);
                    $request->session()->put('current_building_id', (int) $invitation->building_id);
                }
            } catch (InvitationException $e) {
                $flash = $e->getMessage();
            }
        }

        $redirect = redirect()->route('dashboard');

        return $flash ? $redirect->with('warning', $flash) : $redirect;
    }

    /** برگشت به مرحله‌ی ورود شماره (ویرایش موبایل). */
    public function reset(Request $request)
    {
        $request->session()->forget('otp_mobile');

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            app(LoginDestinationService::class)->rememberOnLogout($request, $request->user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
