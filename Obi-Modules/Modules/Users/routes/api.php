<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\app\Http\Controllers\UserController;

Route::get('/ping-users', fn() => response()->json(['pong' => 'Users']))->name('Users.ping');

