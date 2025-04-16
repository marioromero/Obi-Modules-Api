<?php

use Illuminate\Support\Facades\Route;
use Modules\Geography\Http\Controllers\GeographyController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('geography', GeographyController::class)->names('geography');
});
