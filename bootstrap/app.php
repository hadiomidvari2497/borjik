<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        ]);

        // برای ریدایرکت مهمان ادمین به صفحهٔ ورود ادمین
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
