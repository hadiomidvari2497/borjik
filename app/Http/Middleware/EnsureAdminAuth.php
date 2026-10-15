<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** فقط ادمین سیستم (guard: admin) به پنل مدیریت دسترسی دارد. */
class EnsureAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || $admin->status !== 'active') {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')
                ->with('error', 'برای ورود به پنل مدیریت وارد شوید.');
        }

        return $next($request);
    }
}
