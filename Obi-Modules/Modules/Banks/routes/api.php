<?php

use Illuminate\Support\Facades\Route;
use Modules\Banks\Http\Controllers\BanksController;

Route::get('/ping-banks', fn() => response()->json(['pong' => 'Banks']))->name('Banks.ping');

