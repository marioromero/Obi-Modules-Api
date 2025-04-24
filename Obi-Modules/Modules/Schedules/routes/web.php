<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedules\Http\Controllers\SchedulesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('schedules', SchedulesController::class)->names('schedules');
});
