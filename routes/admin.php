<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.submit');
        Route::get('/two-factor', [AuthController::class, 'challenge'])->name('two-factor.challenge');
        Route::post('/two-factor', [AuthController::class, 'verifyChallenge'])->middleware('throttle:admin-login')->name('two-factor.verify');
    });

    Route::middleware('admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::prefix('account')->name('account')->controller(AccountController::class)->group(function () {
            Route::get('/', 'show');
            Route::post('/password', 'password')->name('.password');
            Route::get('/two-factor', 'twoFactorSetup')->name('.two-factor');
            Route::post('/two-factor', 'twoFactorConfirm')->name('.two-factor.confirm');
            Route::delete('/two-factor', 'twoFactorDisable')->name('.two-factor.disable');
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
