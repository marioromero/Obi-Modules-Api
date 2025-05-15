<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-schedules', fn() => response()->json(['pong' => 'Schedules']))->name('Schedules.ping');


// REST para Schedule
use Modules\Schedules\app\Http\Controllers\ScheduleController;
Route::get('schedules', [ScheduleController::class, 'index']);
Route::get('schedules/{schedule}', [ScheduleController::class, 'show']);
Route::post('schedules', [ScheduleController::class, 'store']);
Route::put('schedules/{schedule}', [ScheduleController::class, 'update']);
Route::patch('schedules/{schedule}', [ScheduleController::class, 'patch']);
Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy']);

// REST para ScheduleStatus
use Modules\Schedules\app\Http\Controllers\ScheduleStatusController;
Route::get('schedule-statuses', [ScheduleStatusController::class, 'index']);
Route::get('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'show']);
Route::post('schedule-statuses', [ScheduleStatusController::class, 'store']);
Route::put('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'update']);
Route::patch('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'patch']);
Route::delete('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'destroy']);
