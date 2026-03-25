<?php

use Illuminate\Support\Facades\Route;
use PhucBui\Chat\Http\Controllers\BlockController;
use PhucBui\Chat\Http\Controllers\MessageController;
use PhucBui\Chat\Http\Controllers\ParticipantController;
use PhucBui\Chat\Http\Controllers\ReportController;
use PhucBui\Chat\Http\Controllers\RoomController;

/*
|--------------------------------------------------------------------------
| Chat Routes
|--------------------------------------------------------------------------
|
| These routes are dynamically registered for each actor type defined
| in config/chat.php. The middleware stack (including auth and
| ResolveActorMiddleware) is applied by ChatServiceProvider.
|
*/

// Rooms
Route::get('/rooms', [RoomController::class, 'index']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::put('/rooms/{room}', [RoomController::class, 'update'])->middleware('chat.room_access');
Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->middleware('chat.room_access');

// Messages
Route::get('/rooms/{room}/messages', [MessageController::class, 'index'])->middleware('chat.room_access');
Route::post('/rooms/{room}/messages', [MessageController::class, 'store'])->middleware('chat.room_access');
Route::post('/rooms/{room}/read', [MessageController::class, 'markAsRead'])->middleware('chat.room_access');
Route::post('/rooms/{room}/typing', [MessageController::class, 'typing'])->middleware('chat.room_access');

// Search
Route::get('/messages/search', [MessageController::class, 'search']);

// Participants
Route::get('/rooms/{room}/participants', [ParticipantController::class, 'index'])->middleware('chat.room_access');
Route::post('/rooms/{room}/participants', [ParticipantController::class, 'store'])->middleware('chat.room_access');
Route::put('/rooms/{room}/participants/{participant}', [ParticipantController::class, 'update'])->middleware('chat.room_access');
Route::delete('/rooms/{room}/participants/{participant}', [ParticipantController::class, 'destroy'])->middleware('chat.room_access');

// Block
Route::get('/blocked-users', [BlockController::class, 'index']);
Route::post('/users/{user}/block', [BlockController::class, 'store']);
Route::delete('/users/{user}/block', [BlockController::class, 'destroy']);

// Reports
Route::post('/messages/{message}/report', [ReportController::class, 'store']);
Route::get('/reports', [ReportController::class, 'index']);
Route::put('/reports/{report}', [ReportController::class, 'update']);
