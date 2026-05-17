<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AuthController;

Route::prefix('organizer/events')->middleware(['auth:sanctum', 'role:organizer'])->group(function () {
    Route::put('{id}', [EventController::class, 'update']);
    Route::patch('{id}/status', [EventController::class, 'updateStatus']);
});

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
// Route::get('/events', [EventController::class, 'index']);
// Route::get('/events/{event}', [EventController::class, 'show']);

// Protected routes ( need token )
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    // Route::get('/auth/me', [AuthController::class, 'me']);

    // Organizer only
    Route::middleware('role:organizer')->group(function () {
        Route::post('/organizer/events', [EventController::class, 'create']);
        // Route::apiResource('organizer/events', OrganizerEventController::class);
        // Route::patch('organizer/events/{event}/status', [OrganizerEventController::class, 'updateStatus']);
    });

    // // Attendee only
    // Route::middleware('role:attendee')->group(function () {
    //     Route::get('/attendee/registrations', [RegistrationController::class, 'myRegistrations']);
    //     Route::apiResource('registrations', RegistrationController::class)->only(['store', 'destroy']);
    //     Route::post('/events/{event}/reviews', [ReviewController::class, 'store']);
    // });
});
