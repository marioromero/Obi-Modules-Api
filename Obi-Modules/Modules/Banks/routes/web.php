<?php

use Illuminate\Support\Facades\Route;
use Modules\Banks\Http\Controllers\BanksController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('banks', BanksController::class)->names('banks');
});
