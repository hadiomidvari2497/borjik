<?php

use App\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'profile.complete', 'building'])->group(function () {
    Route::middleware('can:event.view')->get('/calendar', [CalendarController::class, 'index'])->name('events.index');
    Route::middleware('can:event.manage')->post('/calendar/events', [CalendarController::class, 'store'])->name('events.store');
    Route::middleware('can:event.manage')->delete('/calendar/events/{event}', [CalendarController::class, 'destroy'])->whereNumber('event')->name('events.destroy');
});
