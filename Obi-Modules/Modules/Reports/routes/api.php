<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::get('/ping-reports', fn() => response()->json(['pong' => 'Reports']))->name('Reports.ping');

