<?php

use Illuminate\Support\Facades\Route;
use Modules\Cases\Http\Controllers\CasesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cases', CasesController::class)->names('cases');
});
