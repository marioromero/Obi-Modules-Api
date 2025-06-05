<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-users', fn() => response()->json(['pong' => 'Users']))->name('Users.ping');


// REST para User
use Modules\Users\app\Http\Controllers\UserController;
Route::get('users', [UserController::class, 'index']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::post('users', [UserController::class, 'store']);
Route::put('users/{user}', [UserController::class, 'update']);
Route::patch('users/{user}', [UserController::class, 'patch']);
Route::delete('users/{user}', [UserController::class, 'destroy']);

// REST para Role
use Modules\Users\app\Http\Controllers\RoleController;
Route::get('roles', [RoleController::class, 'index']);
Route::get('roles/{role}', [RoleController::class, 'show']);
Route::post('roles', [RoleController::class, 'store']);
Route::put('roles/{role}', [RoleController::class, 'update']);
Route::patch('roles/{role}', [RoleController::class, 'patch']);
Route::delete('roles/{role}', [RoleController::class, 'destroy']);

// REST para UserStatus
use Modules\Users\app\Http\Controllers\UserStatusController;
Route::get('user-statuses', [UserStatusController::class, 'index']);
Route::get('user-statuses/{userStatus}', [UserStatusController::class, 'show']);
Route::post('user-statuses', [UserStatusController::class, 'store']);
Route::put('user-statuses/{userStatus}', [UserStatusController::class, 'update']);
Route::patch('user-statuses/{userStatus}', [UserStatusController::class, 'patch']);
Route::delete('user-statuses/{userStatus}', [UserStatusController::class, 'destroy']);

// REST para Event
use Modules\Users\app\Http\Controllers\EventController;
Route::get('events', [EventController::class, 'index']);
Route::get('events/{event}', [EventController::class, 'show']);
Route::post('events', [EventController::class, 'store']);
Route::put('events/{event}', [EventController::class, 'update']);
Route::patch('events/{event}', [EventController::class, 'patch']);
Route::delete('events/{event}', [EventController::class, 'destroy']);

// REST para UserLog
use Modules\Users\app\Http\Controllers\UserLogController;
Route::get('user-logs', [UserLogController::class, 'index']);
Route::get('user-logs/{userLog}', [UserLogController::class, 'show']);
Route::post('user-logs', [UserLogController::class, 'store']);
Route::put('user-logs/{userLog}', [UserLogController::class, 'update']);
Route::patch('user-logs/{userLog}', [UserLogController::class, 'patch']);
Route::delete('user-logs/{userLog}', [UserLogController::class, 'destroy']);

