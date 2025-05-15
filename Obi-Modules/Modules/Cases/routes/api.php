<?php

use Illuminate\Support\Facades\Route;
use Modules\Cases\app\Http\Controllers\CaseController;

Route::get('/ping-cases', fn() => response()->json(['pong' => 'Cases']))->name('Cases.ping');

