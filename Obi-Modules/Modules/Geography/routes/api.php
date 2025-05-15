<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-geography', fn() => response()->json(['pong' => 'Geography']))->name('Geography.ping');

