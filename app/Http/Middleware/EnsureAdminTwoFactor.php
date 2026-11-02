<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * تمام کاربران فعال پنل مدیریت در محیط عملیاتی باید پیش از دسترسی به بخش‌ها 2FA را فعال کنند.
 * مسیر راه‌اندازی، تأیید و خروج همیشه در دسترس می‌مانند تا حساب قفل نشود.
 */
class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'testing') {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin || $admin->two_factor_enabled) {
            return $next($request);
        }

        $allowedRoutes = [
            'admin.account.two-factor',
            'admin.account.two-factor.confirm',
            'admin.logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('admin.account.two-factor')
            ->with('warning', 'برای حفظ امنیت پنل مدیریت، ابتدا تأیید دومرحله‌ای حساب خود را فعال کن.');
    }
}
