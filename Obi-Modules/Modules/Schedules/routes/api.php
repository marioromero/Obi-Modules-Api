<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedules\Http\Controllers\SchedulesController;

Route::get('/ping-schedules', fn() => response()->json(['pong' => 'Schedules']))->name('Schedules.ping');

