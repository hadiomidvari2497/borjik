<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\MarketingContentController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\SystemDiagnosticsController;
use App\Http\Middleware\RecordAdminActivity;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BuildingUserController;
use App\Http\Controllers\Admin\BuildingDirectoryController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SmsLogController;
use App\Http\Controllers\Admin\SmsProviderController;
use App\Http\Controllers\Admin\SubscriptionManagementController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.submit');
        Route::get('/two-factor', [AuthController::class, 'challenge'])->name('two-factor.challenge');
        Route::post('/two-factor', [AuthController::class, 'verifyChallenge'])->middleware('throttle:admin-login')->name('two-factor.verify');
    });

    Route::middleware(['admin', RecordAdminActivity::class])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->middleware('admin.can:logs.view')->name('activity-logs.index');
        Route::get('/system-diagnostics', [SystemDiagnosticsController::class, 'index'])->middleware('admin.can:logs.view')->name('system-diagnostics.index');

        Route::prefix('account')->name('account')->controller(AccountController::class)->group(function () {
            Route::get('/', 'show');
            Route::post('/password', 'password')->name('.password');
            Route::get('/two-factor', 'twoFactorSetup')->name('.two-factor');
            Route::post('/two-factor', 'twoFactorConfirm')->name('.two-factor.confirm');
            Route::delete('/two-factor', 'twoFactorDisable')->name('.two-factor.disable');
        });

        Route::get('/payment-gateway', [PaymentGatewayController::class, 'edit'])->middleware('admin.can:gateway.manage')->name('payment-gateway.edit');
        Route::put('/payment-gateway', [PaymentGatewayController::class, 'update'])->middleware('admin.can:gateway.manage')->name('payment-gateway.update');
        Route::post('/payment-gateway/validate', [PaymentGatewayController::class, 'validateConfiguration'])->middleware('admin.can:gateway.manage')->name('payment-gateway.validate');

        Route::get('/marketing-content', [MarketingContentController::class, 'edit'])->middleware('admin.can:site.settings')->name('marketing-content.edit');
        Route::put('/marketing-content', [MarketingContentController::class, 'update'])->middleware('admin.can:site.settings')->name('marketing-content.update');

        Route::prefix('articles')->name('articles.')->middleware('admin.can:articles.manage')->controller(ArticleController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

        Route::get('/settings', [SiteSettingController::class, 'edit'])->middleware('admin.can:site.settings')->name('settings.edit');
        Route::put('/settings', [SiteSettingController::class, 'update'])->middleware('admin.can:site.settings')->name('settings.update');

        Route::prefix('plans')->name('plans.')->middleware('admin.can:plans.manage')->controller(SubscriptionPlanController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

        Route::prefix('subscriptions')->name('subscriptions.')->middleware('admin.can:subscriptions.manage')->controller(SubscriptionManagementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });

        Route::prefix('sms')->name('sms.')->group(function () {
            Route::prefix('providers')->name('providers.')->middleware('admin.can:sms.settings')->controller(SmsProviderController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'update')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
                Route::post('/{id}/default', 'makeDefault')->whereNumber('id')->name('default');
                Route::post('/{id}/toggle', 'toggle')->whereNumber('id')->name('toggle');
                Route::post('/{id}/test', 'test')->whereNumber('id')->middleware('throttle:10,1')->name('test');
            });

            Route::prefix('logs')->name('logs.')->middleware('admin.can:sms.logs')->controller(SmsLogController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
            });
        });

        Route::get('/building-users', [BuildingUserController::class, 'index'])->middleware('admin.can:users.view')->name('building-users.index');
        Route::get('/buildings', [BuildingDirectoryController::class, 'index'])->middleware('admin.can:buildings.view')->name('buildings.index');

        Route::prefix('users')->name('users.')->middleware('admin.can:admin.users.manage')->controller(AdminUserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
            Route::post('/{id}/reset-two-factor', 'resetTwoFactor')->whereNumber('id')->name('reset-two-factor');
        });

        Route::prefix('roles')->name('roles.')->middleware('admin.can:admin.roles.manage')->controller(AdminRoleController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
        });
    });
});
