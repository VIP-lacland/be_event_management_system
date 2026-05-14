<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

Route::put('/organizer/events/{id}', [EventController::class, 'update']);
Route::patch('/organizer/events/{id}/status', [EventController::class, 'updateStatus']);

?>
