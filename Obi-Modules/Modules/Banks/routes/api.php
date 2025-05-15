<?php

use Illuminate\Support\Facades\Route;
use Modules\Banks\app\Http\Controllers\BankController;

Route::get('/ping-banks', fn() => response()->json(['pong' => 'Banks']))->name('Banks.ping');

