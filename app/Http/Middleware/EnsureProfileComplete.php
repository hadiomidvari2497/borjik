<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** تا تکمیل پروفایل (عکس اختیاری است) کاربر به صفحه‌ی پروفایل هدایت می‌شود. */
class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->profile_completed_at && ! $request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
