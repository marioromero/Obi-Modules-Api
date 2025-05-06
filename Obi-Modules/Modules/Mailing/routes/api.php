<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailing\Http\Controllers\MailingController;

Route::get('/ping-mailing', fn() => response()->json(['pong' => 'Mailing']))->name('Mailing.ping');

