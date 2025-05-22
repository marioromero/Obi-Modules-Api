<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-reports', fn() => response()->json(['pong' => 'Reports']))->name('Reports.ping');


// REST para Report
use Modules\Reports\app\Http\Controllers\ReportController;
Route::get('reports', [ReportController::class, 'index']);
Route::get('reports/{report}', [ReportController::class, 'show']);
Route::post('reports', [ReportController::class, 'store']);
Route::put('reports/{report}', [ReportController::class, 'update']);
Route::patch('reports/{report}', [ReportController::class, 'patch']);
Route::delete('reports/{report}', [ReportController::class, 'destroy']);

