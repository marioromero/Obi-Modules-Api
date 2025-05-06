<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\UsersController;

Route::get('/ping-users', fn() => response()->json(['pong' => 'Users']))->name('Users.ping');

