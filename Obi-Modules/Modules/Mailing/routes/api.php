<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-mailing', fn() => response()->json(['pong' => 'Mailing']))->name('Mailing.ping');

