<?php

use App\Models\SystemErrorLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'building' => \App\Http\Middleware\SetCurrentBuilding::class,
            'subscription.active' => \App\Http\Middleware\RequireActiveSubscription::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
            'admin' => \App\Http\Middleware\EnsureAdminAuth::class,
            'admin.can' => \App\Http\Middleware\EnsureAdminPermission::class,
        ]);

        // برای ریدایرکت مهمان ادمین به صفحهٔ ورود ادمین
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            try {
                if (app()->runningInConsole() || ! app()->bound('request')) {
                    return;
                }

                $request = app(Request::class);
                $message = trim($exception->getMessage());
                // پیام SQL ممکن است شامل مقادیر حساس باشد؛ متن پرس‌وجو را ثبت نمی‌کنیم.
                $message = preg_replace('/SQL:\\s.*$/is', 'SQL: [جزئیات پرس‌وجو حذف شد]', $message) ?? $message;
                $message = preg_replace('/((?:password|passwd|token|secret|api[_-]?key|authorization|cookie)\\s*[=:]\\s*)[^,\\s;]+/i', '$1[REDACTED]', $message) ?? $message;
                $message = mb_substr($message !== '' ? $message : 'خطای بدون پیام', 0, 4000);

                $admin = $request->user('admin');
                $user = $request->user();
                $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

                SystemErrorLog::query()->create([
                    'exception_class' => mb_substr($exception::class, 0, 255),
                    'message' => $message,
                    'file' => mb_substr($exception->getFile(), 0, 1000),
                    'line' => $exception->getLine(),
                    'method' => mb_substr($request->method(), 0, 10),
                    'path' => mb_substr('/'.$request->path(), 0, 500),
                    'route_name' => $request->route()?->getName() ? mb_substr($request->route()->getName(), 0, 190) : null,
                    'status_code' => $status,
                    'actor_type' => $admin ? 'admin' : ($user ? 'user' : 'guest'),
                    'user_id' => $user?->getAuthIdentifier(),
                    'admin_user_id' => $admin?->getAuthIdentifier(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $loggingException) {
                // اگر دیتابیس یا جدول لاگ در دسترس نباشد، از ایجاد حلقهٔ گزارش خطا جلوگیری کن.
                error_log('System error log persistence failed: '.$loggingException::class);
            }
        });
    })->create();
