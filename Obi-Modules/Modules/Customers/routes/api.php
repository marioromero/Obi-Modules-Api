<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-customers', fn() => response()->json(['pong' => 'Customers']))->name('Customers.ping');


// REST para Customer
use Modules\Customers\app\Http\Controllers\CustomerController;
Route::get('customers', [CustomerController::class, 'index']);
Route::get('customers/{customer}', [CustomerController::class, 'show'])->where('customer', '\d+');
Route::post('customers', [CustomerController::class, 'store']);
Route::put('customers/{customer}', [CustomerController::class, 'update'])->where('customer', '\d+');
Route::patch('customers/{customer}', [CustomerController::class, 'patch'])->where('customer', '\d+');
Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->where('customer', '\d+');
Route::get('customers/search', [CustomerController::class, 'search']);
// Endpoint: búsqueda por nombre + apellido
Route::get('customers/search/name', [CustomerController::class, 'customersByName'])->name('customers.search.name');
// Endpoint: Búsqueda por dni
Route::get('customers/search/dni', [CustomerController::class, 'customersByDni'])->name('customers.search.dni');
// Endpoint para devolver tags y comentarios de cliente segun dni
Route::get('customers/get-tags-by-dni-user', [CustomerController::class, 'getTagsByDniUser']);
// Filtrar usuarios por dni
Route::get('customers/by-dni/{dni}', [CustomerController::class,'findByDni']);

// REST para CustomerStatus
use Modules\Customers\app\Http\Controllers\CustomerStatusController;
Route::get('customer-statuses', [CustomerStatusController::class, 'index']);
Route::get('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'show']);
Route::post('customer-statuses', [CustomerStatusController::class, 'store']);
Route::put('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'update']);
Route::patch('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'patch']);
Route::delete('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'destroy']);


// REST para Tag
use Modules\Customers\app\Http\Controllers\TagController;
Route::get('tags', [TagController::class, 'index']);
Route::get('tags/{tag}', [TagController::class, 'show']);
Route::post('tags', [TagController::class, 'store']);
Route::put('tags/{tag}', [TagController::class, 'update']);
Route::patch('tags/{tag}', [TagController::class, 'patch']);
Route::delete('tags/{tag}', [TagController::class, 'destroy']);
Route::get   ('customers/{customer}/tags',[TagController::class, 'customerTags']);
Route::patch ('customers/{customer}/tags/name/{name}',[TagController::class, 'toggleByName']); 
