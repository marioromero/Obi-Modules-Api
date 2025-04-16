<?php

use Illuminate\Support\Facades\Route;
use Modules\Cases\Http\Controllers\CasesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cases', CasesController::class)->names('cases');
});
