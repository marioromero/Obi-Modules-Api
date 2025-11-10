<?php

use Illuminate\Support\Facades\Route;
use Modules\Banks\app\Http\Controllers\BankController;
use Modules\Banks\app\Http\Controllers\InsurerController;
use Modules\Banks\app\Http\Controllers\LossAdjusterController;

Route::get('/ping-banks', fn() => response()->json(['pong' => 'Banks']))->name('Banks.ping');


// REST para Bank
Route::get('banks', [BankController::class, 'index']);
Route::post('banks', [BankController::class, 'store']);
Route::get('banks/{bank}', [BankController::class, 'show'])->whereNumber('bank');
Route::put('banks/{bank}', [BankController::class, 'update'])->whereNumber('bank');
Route::patch('banks/{bank}', [BankController::class, 'patch'])->whereNumber('bank');
Route::delete('banks/{bank}', [BankController::class, 'destroy'])->whereNumber('bank');
Route::patch('banks/{bank}/soft-delete', [BankController::class, 'softDelete'])->whereNumber('bank');


// REST para Insurer
Route::get('insurers', [InsurerController::class, 'index']);
Route::get('insurers/{insurer}', [InsurerController::class, 'show']);
Route::post('insurers', [InsurerController::class, 'store']);
Route::put('insurers/{insurer}', [InsurerController::class, 'update']);
Route::patch('insurers/{insurer}', [InsurerController::class, 'patch']);
Route::delete('insurers/{insurer}', [InsurerController::class, 'destroy']);
Route::patch('insurers/{insurer}/soft-delete', [InsurerController::class, 'softDelete'])->whereNumber('insurer');

// REST para LossAdjuster
Route::get('loss-adjusters', [LossAdjusterController::class, 'index']);
Route::get('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'show']);
Route::post('loss-adjusters', [LossAdjusterController::class, 'store']);
Route::put('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'update']);
Route::patch('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'patch']);
Route::delete('loss-adjusters/{lossAdjuster}', [LossAdjusterController::class, 'destroy']);
Route::patch('loss-adjusters/{lossAdjuster}/soft-delete', [LossAdjusterController::class, 'softDelete'])->whereNumber('lossAdjuster');

