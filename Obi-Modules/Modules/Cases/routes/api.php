<?php

use Illuminate\Support\Facades\Route;
use Modules\Cases\Http\Controllers\CasesController;

Route::get('/ping-cases', fn() => response()->json(['pong' => 'Cases']))->name('Cases.ping');

