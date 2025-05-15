<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\app\Http\Controllers\ReportController;

Route::get('/ping-reports', fn() => response()->json(['pong' => 'Reports']))->name('Reports.ping');

