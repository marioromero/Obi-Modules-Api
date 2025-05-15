<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\app\Http\Controllers\CustomerController;

Route::get('/ping-customers', fn() => response()->json(['pong' => 'Customers']))->name('Customers.ping');

