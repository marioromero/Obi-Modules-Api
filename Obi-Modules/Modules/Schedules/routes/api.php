<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedules\app\Http\Controllers\ScheduleController;
use Modules\Schedules\app\Http\Controllers\ScheduleStatusController;
use Modules\Schedules\app\Http\Controllers\DispatchController;
use Modules\Schedules\app\Http\Controllers\DispatchDetailController;

Route::get('/ping-schedules', fn() => response()->json(['pong' => 'Schedules']))->name('Schedules.ping');


// REST para Schedule
Route::get('schedules', [ScheduleController::class, 'index']);
Route::get('schedules/{schedule}', [ScheduleController::class, 'show']);
Route::post('schedules', [ScheduleController::class, 'store']);
Route::put('schedules/{schedule}', [ScheduleController::class, 'update']);
Route::patch('schedules/{schedule}', [ScheduleController::class, 'patch']);
Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy']);
Route::get('schedule/{case}', [ScheduleController::class, 'indexScheduleByCaseId']);   // Listar programaciones por caso
Route::post('schedule/{case}', [ScheduleController::class, 'save']);               // Crear o reprogramar
Route::patch('schedule/{case}', [ScheduleController::class, 'updateSchedule']);    // Editar programación vigente

// REST para ScheduleStatus
Route::get('schedule-statuses', [ScheduleStatusController::class, 'index']);
Route::get('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'show']);
Route::post('schedule-statuses', [ScheduleStatusController::class, 'store']);
Route::put('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'update']);
Route::patch('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'patch']);
Route::delete('schedule-statuses/{scheduleStatus}', [ScheduleStatusController::class, 'destroy']);

// REST para Dispatch
Route::get('dispatches', [DispatchController::class, 'index']);
Route::get('dispatches/{dispatch}', [DispatchController::class, 'show']);
Route::post('dispatches', [DispatchController::class, 'store']);
Route::put('dispatches/{dispatch}', [DispatchController::class, 'update']);
Route::patch('dispatches/{dispatch}', [DispatchController::class, 'patch']);
Route::delete('dispatches/{dispatch}', [DispatchController::class, 'destroy']);
Route::get('dispatches/date/{date}', [DispatchController::class, 'indexByDate']);           // Listar despachos por fecha
Route::get('dispatches/{dispatch}/details', [DispatchController::class, 'showWithDetails']); // Despacho con detalles

// REST para DispatchDetail
Route::get('dispatch-details', [DispatchDetailController::class, 'index']);
Route::get('dispatch-details/{dispatchDetail}', [DispatchDetailController::class, 'show']);
Route::post('dispatch-details', [DispatchDetailController::class, 'store']);
Route::put('dispatch-details/{dispatchDetail}', [DispatchDetailController::class, 'update']);
Route::patch('dispatch-details/{dispatchDetail}', [DispatchDetailController::class, 'patch']);
Route::delete('dispatch-details/{dispatchDetail}', [DispatchDetailController::class, 'destroy']);
Route::get('dispatch-details/dispatch/{dispatchId}', [DispatchDetailController::class, 'indexByDispatchId']);           // Listar detalles por despacho
Route::get('dispatch-details/{dispatchDetail}/movement-type', [DispatchDetailController::class, 'showWithMovementType']); // Detalle con tipo de movimiento

