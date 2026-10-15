<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BuildingSwitchController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ChargeSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MyInvoiceController;
use App\Http\Controllers\MyPaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// مسیرهایی که بدون ساختمان فعال هم باید در دسترس باشند (کاربر تازه‌وارد)
Route::middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/buildings/create', [BuildingController::class, 'create'])->name('buildings.create');
    Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');

    // اشتراک: خرید / مشاهده — قفل نیست
    Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
    Route::get('/subscription/plans', [SubscriptionController::class, 'show'])->name('subscription.plans');
    Route::post('/subscription/plans/{id}', [SubscriptionController::class, 'subscribe'])->whereNumber('id')->name('subscription.subscribe');
});

// بخش‌های داخل ساختمان: ورود + پروفایل کامل + ساختمان فعال
Route::middleware(['auth', 'profile.complete', 'building'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/buildings/{building}/switch', BuildingSwitchController::class)->name('building.switch');

    Route::get('/buildings', [BuildingController::class, 'index'])->middleware('can:building.view')->name('buildings.index');
    Route::get('/building/settings', [BuildingController::class, 'edit'])->middleware('can:building.manage')->name('building.settings');
    Route::put('/building/settings', [BuildingController::class, 'update'])->middleware('can:building.manage')->name('building.update');

    // بلوک‌ها: افزودن با اشتراک فعال؛ ویرایش و حذف همیشه ممکن است
    Route::middleware('can:block.manage')->group(function () {
        Route::resource('blocks', BlockController::class)->except(['show', 'create', 'store'])->whereNumber('block');
        Route::middleware('subscription.active')->group(function () {
            Route::get('/blocks/create', [BlockController::class, 'create'])->name('blocks.create');
            Route::post('/blocks', [BlockController::class, 'store'])->name('blocks.store');
        });
    });

    // واحدها
    Route::get('/units', [UnitController::class, 'index'])->middleware('can:unit.view')->name('units.index');
    Route::middleware('can:unit.manage')->group(function () {
        Route::middleware('subscription.active')->group(function () {
            Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
            Route::post('/units', [UnitController::class, 'store'])->name('units.store');
            Route::get('/units/bulk', [UnitController::class, 'bulkCreate'])->name('units.bulk');
            Route::post('/units/bulk', [UnitController::class, 'bulkStore'])->name('units.bulk.store');
        });
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->whereNumber('unit')->name('units.edit');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->whereNumber('unit')->name('units.update');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->whereNumber('unit')->name('units.destroy');
    });

    // پارکینگ و انباری
    foreach (['parkings' => [ParkingController::class, 'parking.manage'], 'storages' => [StorageController::class, 'storage.manage']] as $prefix => [$controller, $permission]) {
        Route::middleware("can:{$permission}")->prefix($prefix)->name("{$prefix}.")->group(function () use ($controller) {
            Route::get('/', [$controller, 'index'])->name('index');
            Route::get('/create', [$controller, 'create'])->name('create');
            Route::post('/', [$controller, 'store'])->name('store');
            Route::get('/bulk', [$controller, 'bulkCreate'])->name('bulk');
            Route::post('/bulk', [$controller, 'bulkStore'])->name('bulk.store');
            Route::get('/{id}/edit', [$controller, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [$controller, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [$controller, 'destroy'])->whereNumber('id')->name('destroy');
            Route::post('/{id}/assign', [$controller, 'assign'])->whereNumber('id')->name('assign');
            Route::post('/{id}/release', [$controller, 'release'])->whereNumber('id')->name('release');
        });
    }

    Route::middleware('can:charge.settings')->prefix('charge-settings')->name('charge-settings.')->controller(ChargeSettingController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::post('/rules', 'storeRule')->name('rules.store');
        Route::delete('/rules/{id}', 'destroyRule')->whereNumber('id')->name('rules.destroy');
        Route::post('/categories', 'storeCategory')->name('categories.store');
        Route::delete('/categories/{id}', 'destroyCategory')->whereNumber('id')->name('categories.destroy');
    });

    Route::middleware('can:expense.manage')->prefix('expenses')->name('expenses.')->controller(ExpenseController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::middleware('can:charge.calculate')->prefix('charges')->name('charges.')->controller(ChargeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'open')->name('open');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}/calculate', 'calculate')->whereNumber('id')->name('calculate');
        Route::post('/{id}/publish', 'publish')->whereNumber('id')->name('publish');
    });

    Route::get('/files/{id}', [FileController::class, 'show'])->whereNumber('id')->name('files.show');

    Route::get('/my/invoices', [MyInvoiceController::class, 'index'])->middleware('can:invoice.view.own')->name('my.invoices');
    Route::middleware('can:payment.submit')->group(function () {
        Route::get('/my/payments', [MyPaymentController::class, 'index'])->name('my.payments');
        Route::post('/my/payments', [MyPaymentController::class, 'store'])->name('my.payments.store');
    });

    Route::middleware('can:payment.review')->prefix('payments')->name('payments.')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{id}/approve', 'approve')->whereNumber('id')->name('approve');
        Route::post('/{id}/reject', 'reject')->whereNumber('id')->name('reject');
    });

    Route::get('/staff', [TeamController::class, 'staff'])->middleware('can:staff.manage')->name('staff.index');
    Route::put('/staff/{userId}/roles', [TeamController::class, 'updateRoles'])->whereNumber('userId')->middleware('can:staff.manage')->name('staff.roles.update');
    Route::get('/members', [TeamController::class, 'members'])->middleware('can:member.manage')->name('members.index');
    Route::prefix('team/{userId}')->where(['userId' => '[0-9]+'])->name('team.')->controller(TeamController::class)->group(function () {
        Route::post('/deactivate', 'deactivate')->name('deactivate');
        Route::post('/activate', 'activate')->name('activate');
        Route::delete('/invitation', 'cancelInvitation')->name('invitation.cancel');
    });
});
