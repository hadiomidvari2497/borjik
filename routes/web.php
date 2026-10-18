<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'marketing.home')->name('home');

require __DIR__.'/auth.php';
require __DIR__.'/app.php';
require __DIR__.'/admin.php';
require __DIR__.'/calendar.php';
