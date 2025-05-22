<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-customers', fn() => response()->json(['pong' => 'Customers']))->name('Customers.ping');


// REST para Customer
use Modules\Customers\app\Http\Controllers\CustomerController;
Route::get('customers', [CustomerController::class, 'index']);
Route::get('customers/{customer}', [CustomerController::class, 'show']);
Route::post('customers', [CustomerController::class, 'store']);
Route::put('customers/{customer}', [CustomerController::class, 'update']);
Route::patch('customers/{customer}', [CustomerController::class, 'patch']);
Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);

// REST para CustomerStatus
use Modules\Customers\app\Http\Controllers\CustomerStatusController;
Route::get('customer-statuses', [CustomerStatusController::class, 'index']);
Route::get('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'show']);
Route::post('customer-statuses', [CustomerStatusController::class, 'store']);
Route::put('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'update']);
Route::patch('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'patch']);
Route::delete('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'destroy']);

