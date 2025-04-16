<?php

use Illuminate\Support\Facades\Route;
use Modules\Banks\Http\Controllers\BanksController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('banks', BanksController::class)->names('banks');
});
