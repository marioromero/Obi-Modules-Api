<?php

use Illuminate\Support\Facades\Route;
use Modules\Geography\Http\Controllers\GeographyController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('geography', GeographyController::class)->names('geography');
});
