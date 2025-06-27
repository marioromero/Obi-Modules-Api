<?php

use Illuminate\Support\Facades\Route;
use Modules\Configurations\app\Http\Controllers\ConfigurationsController;

Route::apiResource('configurations', ConfigurationsController::class)
     ->names('configurations');

// REST para Type
use Modules\Configurations\app\Http\Controllers\TypeController;
Route::get('types', [TypeController::class, 'index']);
Route::get('types/{type}', [TypeController::class, 'show']);
Route::post('types', [TypeController::class, 'store']);
Route::put('types/{type}', [TypeController::class, 'update']);
Route::patch('types/{type}', [TypeController::class, 'patch']);
Route::delete('types/{type}', [TypeController::class, 'destroy']);

// REST para Configuration
use Modules\Configurations\app\Http\Controllers\ConfigurationController;
Route::get('configurations', [ConfigurationController::class, 'index']);
Route::get('configurations/{configuration}', [ConfigurationController::class, 'show']);
Route::post('configurations', [ConfigurationController::class, 'store']);
Route::put('configurations/{configuration}', [ConfigurationController::class, 'update']);
Route::patch('configurations/{configuration}', [ConfigurationController::class, 'patch']);
Route::delete('configurations/{configuration}', [ConfigurationController::class, 'destroy']);
//Rutas especiales para logica de negocio
Route::get('configurations/{configuration}/countries', [ConfigurationController::class, 'countries'])->name('configurations.countries');
Route::patch('configurations/{configuration}/countries', [ConfigurationController::class, 'updateCountries'])->name('configurations.updateCountries');
