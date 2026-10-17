<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\MobileNumber;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private const CHALLENGE_TTL = 300;      // ثانیه
    private const CHALLENGE_MAX_TRIES = 5;

    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
        ]);

        $guard = Auth::guard('admin');
        $provider = $guard->getProvider();
        $credentials = ['username' => $data['username'], 'password' => $data['password'], 'status' => 'active'];

        /** @var AdminUser|null $user */
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
            return back()->withErrors(['username' => 'نام کاربری یا رمز عبور نادرست است.'])->onlyInput('username');
        }

        // با فعال بودن ۲FA هنوز وارد نمی‌شویم؛ فقط هویت رمز تأیید شده و کد دومرحله‌ای لازم است.
        if ($user->two_factor_enabled && $user->two_factor_secret) {
            $request->session()->put('admin_2fa', [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
                'expires' => now()->timestamp + self::CHALLENGE_TTL,
                'tries' => 0,
            ]);

            return redirect()->route('admin.two-factor.challenge');
        }

        return $this->finish($request, $user, $request->boolean('remember'));
    }

    public function challenge(Request $request)
    {
        if (! $this->pendingChallenge($request)) {
            return redirect()->route('admin.login')->with('error', 'نشست تأیید دومرحله‌ای منقضی شده است؛ دوباره وارد شوید.');
        }

        return view('admin.two-factor');
    }

    public function verifyChallenge(Request $request)
    {
        $pending = $this->pendingChallenge($request);
        if (! $pending) {
            return redirect()->route('admin.login')->with('error', 'نشست تأیید دومرحله‌ای منقضی شده است؛ دوباره وارد شوید.');
        }

        $user = AdminUser::find($pending['id']);
        if (! $user || ! $user->isActive() || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            $request->session()->forget('admin_2fa');

            return redirect()->route('admin.login')->withErrors(['username' => 'ورود ممکن نیست.']);
        }

        $code = MobileNumber::toLatinDigits((string) $request->input('code'));

        if (! Totp::verify($user->two_factor_secret, $code)) {
            $pending['tries']++;
            if ($pending['tries'] >= self::CHALLENGE_MAX_TRIES) {
                $request->session()->forget('admin_2fa');

                return redirect()->route('admin.login')->with('error', 'تعداد تلاش‌های ناموفق زیاد بود؛ دوباره وارد شوید.');
            }
            $request->session()->put('admin_2fa', $pending);

            return back()->withErrors(['code' => 'کد تأیید نادرست است.']);
        }

        $request->session()->forget('admin_2fa');

        return $this->finish($request, $user, (bool) $pending['remember']);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'از پنل مدیریت خارج شدید.');
    }

    private function finish(Request $request, AdminUser $user, bool $remember)
    {
        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->to($this->landingUrl($request));
    }

    /**
     * مقصد بعد از ورود: فقط اگر آدرس ذخیره‌شده (url.intended) داخل خود پنل مدیریت باشد.
     * در غیر این صورت — مثلاً وقتی کاربر قبلاً صفحه‌ی پنل ساختمان را بدون ورود باز کرده و آدرسش در
     * نشست مانده — ادمین به‌اشتباه به پنل ساختمان‌ها هدایت می‌شد.
     */
    private function landingUrl(Request $request): string
    {
        $home = route('admin.dashboard');
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended)) {
            return $home;
        }

        $base = rtrim((string) parse_url($home, PHP_URL_PATH), '/');
        $path = (string) parse_url($intended, PHP_URL_PATH);
        $sameHost = parse_url($intended, PHP_URL_HOST) === parse_url($home, PHP_URL_HOST);

        return $sameHost && ($path === $base || str_starts_with($path, $base.'/')) ? $intended : $home;
    }

    /** @return array{id:int, remember:bool, expires:int, tries:int}|null */
    private function pendingChallenge(Request $request): ?array
    {
        $pending = $request->session()->get('admin_2fa');

        if (! is_array($pending) || ($pending['expires'] ?? 0) < now()->timestamp) {
            $request->session()->forget('admin_2fa');

            return null;
        }

        return $pending;
    }
}
