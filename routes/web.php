<?php

use App\Http\Controllers\MarketingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AnalyticsCollectionController;
use Illuminate\Support\Facades\Route;

Route::post('/analytics/heartbeat', [AnalyticsCollectionController::class, 'heartbeat'])->middleware('throttle:120,1')->name('analytics.heartbeat');
Route::post('/analytics/event', [AnalyticsCollectionController::class, 'event'])->middleware('throttle:120,1')->name('analytics.event');

Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/enamad/image', [MarketingController::class, 'enamadImage'])->name('marketing.enamad.image');
Route::get('/payment/gateway/callback', [SubscriptionController::class, 'callback'])->name('payment.gateway.callback');
Route::get('/articles', [MarketingController::class, 'articles'])->name('marketing.articles.index');
Route::get('/articles/{slug}', [MarketingController::class, 'article'])->name('marketing.articles.show');

require __DIR__.'/auth.php';
require __DIR__.'/app.php';
require __DIR__.'/admin.php';
require __DIR__.'/calendar.php';
