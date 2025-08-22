<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-banks', fn() => response()->json(['pong' => 'Banks']))->name('Banks.ping');


// REST para Bank
use Modules\Banks\app\Http\Controllers\BankController;
Route::get('banks', [BankController::class, 'index']);
Route::post('banks', [BankController::class, 'store']);
Route::get('banks/{bank}', [BankController::class, 'show'])->whereNumber('bank');
Route::put('banks/{bank}', [BankController::class, 'update'])->whereNumber('bank');
Route::patch('banks/{bank}', [BankController::class, 'patch'])->whereNumber('bank');
Route::delete('banks/{bank}', [BankController::class, 'destroy'])->whereNumber('bank');


// REST para Insurer
use Modules\Banks\app\Http\Controllers\InsurerController;
Route::get('insurers', [InsurerController::class, 'index']);
Route::get('insurers/{insurer}', [InsurerController::class, 'show']);
Route::post('insurers', [InsurerController::class, 'store']);
Route::put('insurers/{insurer}', [InsurerController::class, 'update']);
Route::patch('insurers/{insurer}', [InsurerController::class, 'patch']);
Route::delete('insurers/{insurer}', [InsurerController::class, 'destroy']);

// REST para LossAdjuster
use Modules\Banks\app\Http\Controllers\LossAdjusterController;
Route::get('loss-adjusters', [LossAdjusterController::class, 'index']);
Route::get('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'show']);
Route::post('loss-adjusters', [LossAdjusterController::class, 'store']);
Route::put('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'update']);
Route::patch('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'patch']);
Route::delete('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'destroy']);

