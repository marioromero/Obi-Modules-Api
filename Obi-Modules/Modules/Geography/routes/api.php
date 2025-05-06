<?php

use Illuminate\Support\Facades\Route;
use Modules\Geography\Http\Controllers\GeographyController;

Route::get('/ping-geography', fn() => response()->json(['pong' => 'Geography']))->name('Geography.ping');

