<?php

use App\Http\Controllers\MarketingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/articles', [MarketingController::class, 'articles'])->name('marketing.articles.index');
Route::get('/articles/{slug}', [MarketingController::class, 'article'])->name('marketing.articles.show');

require __DIR__.'/auth.php';
require __DIR__.'/app.php';
require __DIR__.'/admin.php';
require __DIR__.'/calendar.php';
