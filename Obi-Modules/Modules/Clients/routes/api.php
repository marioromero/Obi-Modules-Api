<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Http\Controllers\ClientsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('clients', ClientsController::class)->names('clients');
});
