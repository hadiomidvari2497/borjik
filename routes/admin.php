<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AiConnectorController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\MarketingContentController;
use App\Http\Controllers\Admin\MailboxController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\SystemDiagnosticsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BuildingAnalyticsController;
use App\Http\Controllers\Admin\OperationsCenterController;
use App\Http\Controllers\Admin\HostMonitoringController;
use App\Http\Controllers\Admin\PerformanceMonitoringController;
use App\Http\Middleware\RecordAdminActivity;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BuildingUserController;
use App\Http\Controllers\Admin\BuildingDirectoryController;
use App\Http\Controllers\Admin\BuildingToolsController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SmsLogController;
use App\Http\Controllers\Admin\SmsProviderController;
use App\Http\Controllers\Admin\SubscriptionManagementController;
use App\Http\Controllers\Admin\SubscriptionPaymentReportController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\FinancialManagementController;
use App\Http\Controllers\Admin\DiscountCouponController;
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
        Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('admin.can:stats.view')->name('analytics.index');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->middleware('admin.can:stats.view')->name('analytics.export');
        Route::get('/building-analytics', [BuildingAnalyticsController::class, 'index'])->middleware('admin.can:stats.view')->name('building-analytics.index');
        Route::get('/operations', [OperationsCenterController::class, 'index'])->middleware('admin.can:site.settings')->name('operations.index');
        Route::prefix('ai-connector')->name('ai-connector.')->middleware('admin.can:site.settings')->controller(AiConnectorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/tokens', 'storeToken')->middleware('throttle:6,1')->name('tokens.store');
            Route::delete('/tokens/{token}', 'revokeToken')->whereNumber('token')->name('tokens.revoke');
            Route::post('/actions/{action}/approve', 'approveAction')->whereNumber('action')->middleware('throttle:6,1')->name('actions.approve');
            Route::delete('/actions/{action}', 'rejectAction')->whereNumber('action')->name('actions.reject');
        });
        Route::put('/operations', [OperationsCenterController::class, 'update'])->middleware('admin.can:site.settings')->name('operations.update');
        Route::post('/operations/backup', [OperationsCenterController::class, 'runBackup'])->middleware('admin.can:site.settings')->middleware('throttle:2,1')->name('operations.backup');
        Route::post('/operations/backups/{backupRun}/verify', [OperationsCenterController::class, 'verifyBackup'])->whereNumber('backupRun')->middleware('admin.can:site.settings')->middleware('throttle:6,1')->name('operations.backups.verify');
        Route::post('/operations/health-check', [OperationsCenterController::class, 'checkNow'])->middleware('admin.can:site.settings')->name('operations.check');
        Route::post('/operations/test-sms', [OperationsCenterController::class, 'testSms'])->middleware('admin.can:site.settings')->middleware('throttle:5,1')->name('operations.test-sms');
        Route::get('/monitoring/host', [HostMonitoringController::class, 'host'])->middleware('admin.can:logs.view')->name('monitoring.host');
        Route::get('/monitoring/cron', [HostMonitoringController::class, 'cron'])->middleware('admin.can:logs.view')->name('monitoring.cron');
        Route::get('/monitoring/performance', [PerformanceMonitoringController::class, 'index'])->middleware('admin.can:logs.view')->name('monitoring.performance');
        Route::post('/monitoring/performance/sample', [PerformanceMonitoringController::class, 'sampleNow'])->middleware('admin.can:logs.view')->middleware('throttle:6,1')->name('monitoring.performance.sample');

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
            Route::get('/template', 'downloadTemplate')->name('template');
            Route::post('/import', 'import')->middleware('throttle:3,1')->name('import');
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

        Route::get('/seo', [SeoController::class, 'index'])->middleware('admin.can:site.settings')->name('seo.index');
        Route::put('/seo', [SeoController::class, 'update'])->middleware('admin.can:site.settings')->name('seo.update');

        Route::get('/settings', [SiteSettingController::class, 'edit'])->middleware('admin.can:site.settings')->name('settings.edit');
        Route::put('/settings', [SiteSettingController::class, 'update'])->middleware('admin.can:site.settings')->name('settings.update');

        Route::prefix('finances')->name('finances.')->middleware('admin.can:plans.manage')->controller(FinancialManagementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/bulk-edit', fn () => view('admin.finances.bulk-edit'))->name('bulk-edit');
            Route::post('/bulk-adjust', 'bulkAdjust')->name('bulk-adjust');
        });

        Route::prefix('coupons')->name('coupons.')->middleware('admin.can:plans.manage')->controller(DiscountCouponController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('/{id}/toggle', 'toggle')->whereNumber('id')->name('toggle');
        });

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

        Route::prefix('subscription-payments')->name('subscription-payments.')->middleware('admin.can:subscriptions.manage')->controller(SubscriptionPaymentReportController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export', 'export')->name('export');
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

        Route::prefix('mailbox')->name('mailbox.')->controller(MailboxController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/sent', 'sent')->name('sent');
            Route::post('/login', 'login')->middleware('throttle:8,1')->name('login');
            Route::post('/logout', 'logout')->name('logout');
            Route::get('/settings', 'settings')->name('settings');
            Route::put('/settings', 'updateSettings')->name('settings.update');
            Route::post('/accounts', 'storeAccount')->middleware('throttle:8,1')->name('accounts.store');
            Route::delete('/accounts/{account}', 'destroyAccount')->whereNumber('account')->name('accounts.destroy');
            Route::get('/compose', 'compose')->name('compose');
            Route::post('/send', 'send')->middleware('throttle:10,1')->name('send');
            Route::get('/{uid}', 'show')->whereNumber('uid')->name('show');
        });

        Route::get('/building-users', [BuildingUserController::class, 'index'])->middleware('admin.can:users.view')->name('building-users.index');
        Route::get('/buildings', [BuildingDirectoryController::class, 'index'])->middleware('admin.can:buildings.view')->name('buildings.index');

        Route::prefix('building-tools')->name('building-tools.')->middleware('admin.can:site.settings')->controller(BuildingToolsController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::put('/', 'update')->name('update');
        });

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
