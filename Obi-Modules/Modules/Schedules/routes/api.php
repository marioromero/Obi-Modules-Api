<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedules\Http\Controllers\SchedulesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('schedules', SchedulesController::class)->names('schedules');
});
