<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizerEventController;
use App\Http\Controllers\Organizer\DashboardController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
// Route::get('/events', [EventController::class, 'index']);
// Route::get('/events/{event}', [EventController::class, 'show']);

// Protected routes ( need token )
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/organizer/dashboard', [DashboardController::class, 'dashboard']);
    
    
});
?>