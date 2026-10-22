<?php

use App\Http\Controllers\Api\AiConnectorController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai/v1')->name('api.ai.v1.')->middleware('throttle:30,1')->group(function () {
    Route::get('/overview', [AiConnectorController::class, 'overview'])->middleware('ai.token:read:overview')->name('overview');
    Route::get('/incidents', [AiConnectorController::class, 'incidents'])->middleware('ai.token:read:diagnostics')->name('incidents');
    Route::post('/actions', [AiConnectorController::class, 'requestAction'])->middleware(['ai.token:actions:request', 'throttle:6,1'])->name('actions.store');
});
