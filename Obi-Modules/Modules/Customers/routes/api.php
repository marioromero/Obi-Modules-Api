<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\CustomersController;

Route::get('/ping-customers', fn() => response()->json(['pong' => 'Customers']))->name('Customers.ping');

