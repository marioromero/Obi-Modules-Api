<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\app\Http\Controllers\CustomerController;
use Modules\Customers\app\Http\Controllers\CustomerStatusController;
use Modules\Customers\app\Http\Controllers\TagController;

Route::get('/ping-customers', fn() => response()->json(['pong' => 'Customers']))->name('Customers.ping');

// REST para customers
Route::get('customers',[CustomerController::class,'index']);
Route::get('customers/{customer}',[CustomerController::class,'show'])->whereNumber('customer');
Route::post('customers',[CustomerController::class,'store']);
Route::put('customers/{customer}',[CustomerController::class,'update'])->whereNumber('customer');
Route::patch('customers/{customer}',[CustomerController::class,'patch'])->whereNumber('customer');
Route::delete('customers/{customer}',[CustomerController::class,'destroy'])->whereNumber('customer');
Route::get('customers/by-dni/{dni}',[CustomerController::class,'showCustomerByDni']);
Route::get('customers/verify-existing-customer/{dni}',[CustomerController::class,'verifyExistingCustomer']);
Route::get('customers/get-tags-by-dni-user/{dni}',[CustomerController::class,'getTagsByDniUser']);
Route::get('customers/search/dni/{dni}',[CustomerController::class,'customersByDni'])->name('customers.search.dni');
Route::get('customers/search/name/{q}',[CustomerController::class,'customersByName'])->name('customers.search.name');
Route::get('customers/by-agent/{agent}',[CustomerController::class,'getCustomersByAgent'])->whereNumber('agent')->name('customers.by-agent');

// REST para CustomerStatus
Route::get('customer-statuses', [CustomerStatusController::class, 'index']);
Route::get('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'show']);
Route::post('customer-statuses', [CustomerStatusController::class, 'store']);
Route::put('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'update']);
Route::patch('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'patch']);
Route::delete('customer-statuses/{customerStatus}', [CustomerStatusController::class, 'destroy']);


// REST para Tag
Route::get('tags', [TagController::class, 'index']);
Route::get('tags/{tag}', [TagController::class, 'show']);
Route::post('tags', [TagController::class, 'store']);
Route::put('tags/{tag}', [TagController::class, 'update']);
Route::patch('tags/{tag}', [TagController::class, 'patch']);
Route::delete('tags/{tag}', [TagController::class, 'destroy']);
Route::get   ('customers/{customer}/tags',[TagController::class, 'customerTags']);
Route::patch ('customers/{customer}/tags/name/{name}',[TagController::class, 'toggleByName']); 
