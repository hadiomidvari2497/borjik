<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordLoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

// مهمان
Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'show'])->name('login');
    Route::post('/login/otp', [OtpLoginController::class, 'send'])->middleware('throttle:otp-send')->name('login.otp');
    Route::post('/login/otp/verify', [OtpLoginController::class, 'verify'])->middleware('throttle:otp-verify')->name('login.otp.verify');
    Route::post('/login/password', [PasswordLoginController::class, 'store'])->middleware('throttle:otp-verify')->name('login.password');

    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:otp-verify');
});

// لینک دعوت (برای مهمان و کاربر واردشده)
Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invite.show');

// کاربر واردشده (بدون نیاز به ساختمان)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [OtpLoginController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/two-factor', [TwoFactorController::class, 'setup'])->name('profile.two-factor');
    Route::post('/profile/two-factor', [TwoFactorController::class, 'confirm'])->name('profile.two-factor.confirm');
    Route::delete('/profile/two-factor', [TwoFactorController::class, 'disable'])->name('profile.two-factor.disable');
});

// دعوت عضو یا پرسنل (داخل ساختمان فعال)
Route::middleware(['auth', 'profile.complete', 'building'])->group(function () {
    Route::post('/members/invite', [InvitationController::class, 'store'])->name('members.invite');
});
