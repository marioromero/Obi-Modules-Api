<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\app\Http\Controllers\SoftdeleteController;

// REST para Softdelete
Route::get('softdeletes', [SoftdeleteController::class, 'index']);
Route::get('softdeletes/{softdelete}', [SoftdeleteController::class, 'show']);
Route::post('softdeletes', [SoftdeleteController::class, 'store']);
Route::put('softdeletes/{softdelete}', [SoftdeleteController::class, 'update']);
Route::patch('softdeletes/{softdelete}', [SoftdeleteController::class, 'patch']);
Route::delete('softdeletes/{softdelete}', [SoftdeleteController::class, 'destroy']);
