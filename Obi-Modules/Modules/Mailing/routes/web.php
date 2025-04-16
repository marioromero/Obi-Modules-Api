<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailing\Http\Controllers\MailingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('mailing', MailingController::class)->names('mailing');
});
