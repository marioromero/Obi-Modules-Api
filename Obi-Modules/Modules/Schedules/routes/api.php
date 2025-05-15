<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-schedules', fn() => response()->json(['pong' => 'Schedules']))->name('Schedules.ping');

