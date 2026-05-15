<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

Route::prefix('organizer/events')->middleware(['auth:sanctum', 'role:organizer'])->group(function () {
    Route::put('{id}', [EventController::class, 'update']);
    Route::patch('{id}/status', [EventController::class, 'updateStatus']);
});

