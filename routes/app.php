<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BuildingSwitchController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ChargeSettingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MyContractController;
use App\Http\Controllers\MyInvoiceController;
use App\Http\Controllers\MyPaymentController;
use App\Http\Controllers\MyUnitController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\StaffRoleController;
use App\Http\Controllers\SecurityShiftController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

/*
| مسیرهایی که هم با ساختمان و هم بدون ساختمان کار می‌کنند.
| middleware «building» ساختمان را از session می‌خواند (اگر باشد)؛
| اگر نباشد فقط این مسیرها مجازند (SetCurrentBuilding).
*/
Route::middleware(['auth', 'profile.complete', 'building'])->group(function () {
    Route::get('/buildings/create', [BuildingController::class, 'create'])->name('buildings.create');
    Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');

    Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
    Route::get('/subscription/plans', [SubscriptionController::class, 'show'])->name('subscription.plans');
    Route::post('/subscription/plans/{id}', [SubscriptionController::class, 'subscribe'])->whereNumber('id')->name('subscription.subscribe');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/buildings/{building}/switch', BuildingSwitchController::class)->name('building.switch');

    Route::get('/buildings', [BuildingController::class, 'index'])->middleware('can:building.view')->name('buildings.index');
    Route::get('/building/settings', [BuildingController::class, 'edit'])->middleware('can:building.manage')->name('building.settings');
    Route::put('/building/settings', [BuildingController::class, 'update'])->middleware('can:building.manage')->name('building.update');

    Route::middleware('can:block.manage')->group(function () {
        Route::resource('blocks', BlockController::class)->except(['show', 'create', 'store'])->whereNumber('block');
        Route::middleware('subscription.active')->group(function () {
            Route::get('/blocks/create', [BlockController::class, 'create'])->name('blocks.create');
            Route::post('/blocks', [BlockController::class, 'store'])->name('blocks.store');
        });
    });

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

    Route::get('/audit', [AuditController::class, 'index'])->middleware('can:audit.view')->name('audit.index');

    Route::middleware('can:document.view')->prefix('documents')->name('documents.')->controller(DocumentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}/download', 'download')->whereNumber('id')->name('download');
        Route::post('/', 'store')->middleware('can:document.manage')->name('store');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('can:document.manage')->name('destroy');
    });

    Route::middleware('can:contract.manage')->prefix('contracts')->name('contracts.')->controller(ContractController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}/end', 'end')->whereNumber('id')->name('end');
        Route::put('/{id}/payer', 'payer')->whereNumber('id')->name('payer');
        Route::post('/{id}/file', 'file')->whereNumber('id')->name('file');
    });
    Route::get('/my/contracts', [MyContractController::class, 'index'])->middleware('can:contract.view.own')->name('my.contracts');
    Route::get('/my/unit', [MyUnitController::class, 'index'])->middleware('can:unit.view.own')->name('my.units');

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

    Route::middleware('can:supply.manage')->prefix('supplies')->name('supplies.')->controller(SupplyController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/movements', 'move')->whereNumber('id')->name('move');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::middleware('can:sms.templates')->prefix('sms-templates')->name('sms-templates.')->controller(SmsTemplateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::middleware('can:shift.view')->prefix('shifts')->name('shifts.')->controller(SecurityShiftController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::middleware('can:shift.manage')->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/{shift}/edit', 'edit')->whereNumber('shift')->name('edit');
            Route::put('/{shift}', 'update')->whereNumber('shift')->name('update');
            Route::delete('/{shift}', 'destroy')->whereNumber('shift')->name('destroy');
        });
    });

    Route::get('/staff', [TeamController::class, 'staff'])->middleware('can:staff.manage')->name('staff.index');
    Route::middleware('can:staff.manage')->prefix('staff-roles')->name('staff-roles.')->controller(StaffRoleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{role}', 'update')->whereNumber('role')->name('update');
        Route::delete('/{role}', 'destroy')->whereNumber('role')->name('destroy');
    });
    Route::put('/staff/{userId}/roles', [TeamController::class, 'updateRoles'])->whereNumber('userId')->middleware('can:staff.manage')->name('staff.roles.update');
    Route::get('/members', [TeamController::class, 'members'])->middleware('can:member.manage')->name('members.index');
    Route::post('/members/{userId}/membership-role', [TeamController::class, 'changeMemberRole'])->whereNumber('userId')->middleware('can:member.manage')->name('members.membership-role.update');
    Route::prefix('team/{userId}')->where(['userId' => '[0-9]+'])->name('team.')->controller(TeamController::class)->group(function () {
        Route::post('/deactivate', 'deactivate')->name('deactivate');
        Route::post('/activate', 'activate')->name('activate');
        Route::delete('/invitation', 'cancelInvitation')->name('invitation.cancel');
    });

    Route::prefix('notifications')->name('notifications.')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/read-all', 'readAll')->name('read-all');
        Route::post('/{notification}/read', 'read')->name('read');
    });

    // مدیریت تسک‌های پرسنل (جدا از درخواست‌های ثبت‌شده توسط ساکنین).
    Route::prefix('tickets')->name('tickets.')->controller(TaskController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{ticket}/edit', 'edit')->whereNumber('ticket')->name('edit');
        Route::put('/{ticket}', 'update')->whereNumber('ticket')->name('update');
        Route::get('/{ticket}', 'show')->whereNumber('ticket')->name('show');
        Route::post('/{ticket}/status', 'updateStatus')->whereNumber('ticket')->name('status');
        Route::post('/{ticket}/claim', 'claim')->whereNumber('ticket')->name('claim');
        Route::post('/{ticket}/assign', 'assign')->whereNumber('ticket')->name('assign');
        Route::post('/{ticket}/comments', 'comment')->whereNumber('ticket')->name('comments.store');
        Route::post('/{ticket}/ratings', 'rate')->whereNumber('ticket')->name('rate');
        Route::get('/{ticket}/attachments/{attachment}', 'attachment')->whereNumber('ticket')->whereNumber('attachment')->name('attachments.show');
    });

    // درخواست‌های ساکنان: مدیر همه را می‌بیند و مسئول فقط درخواست‌های ارجاع‌شده به خودش را.
    Route::middleware('can:request.view.domain')->prefix('requests')->name('requests.')->controller(RequestController::class)->group(function () {
        Route::get('/reports', 'reports')->middleware('can:request.view.all')->name('reports');
        Route::get('/', 'index')->name('index');
        Route::get('/{ticket}', 'show')->whereNumber('ticket')->name('show');
        Route::post('/{ticket}/comments', 'comment')->whereNumber('ticket')->name('comments.store');
        Route::post('/{ticket}/claim', 'claim')->whereNumber('ticket')->name('claim');
        Route::post('/{ticket}/status', 'updateStatus')->whereNumber('ticket')->name('status');
        Route::post('/{ticket}/assign', 'assign')->whereNumber('ticket')->name('assign');
    });
    Route::middleware('can:request.create')->controller(RequestController::class)->group(function () {
        Route::get('/my/requests', 'myIndex')->name('my.requests');
        Route::post('/my/requests', 'store')->name('my.requests.store');
    });
    Route::middleware('can:request.view.own')->prefix('my/requests')->name('my.requests.')->controller(RequestController::class)->group(function () {
        Route::get('/{ticket}', 'show')->whereNumber('ticket')->name('show');
        Route::post('/{ticket}/comments', 'comment')->whereNumber('ticket')->name('comments.store');
    });


    // نظرسنجی و رأی‌گیری ساختمان فعال.
    Route::middleware('can:poll.manage')->prefix('polls')->name('polls.')->controller(\App\Http\Controllers\PollController::class)->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('/{poll}', 'show')->whereNumber('poll')->name('show');
        Route::post('/{poll}/close', 'close')->whereNumber('poll')->name('close');
    });
    Route::middleware('can:poll.vote')->prefix('polls')->name('polls.')->controller(\App\Http\Controllers\PollController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{poll}/vote', 'vote')->whereNumber('poll')->name('vote');
    });

});
