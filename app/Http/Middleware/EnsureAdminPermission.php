<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * کنترل دسترسی بخش‌های پنل مدیریت. استفاده: ->middleware('admin.can:plans.manage')
 * اگر چند مجوز داده شود، داشتن یکی کافی است. پس از middleware «admin» اجرا می‌شود.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->hasPermission($permissions)) {
            abort(403, 'شما به این بخش از پنل مدیریت دسترسی ندارید.');
        }

        return $next($request);
    }
}
