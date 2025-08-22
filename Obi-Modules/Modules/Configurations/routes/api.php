<?php

use Illuminate\Support\Facades\Route;

//Route::apiResource('configurations', ConfigurationController::class)
  //   ->names('configurations');

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

Route::get   ('configurations', [ConfigurationController::class, 'index']);
Route::get   ('configurations/{configuration}', [ConfigurationController::class, 'show'])->whereNumber('configuration');
Route::post  ('configurations', [ConfigurationController::class, 'store']);
Route::put   ('configurations/{configuration}', [ConfigurationController::class, 'update'])->whereNumber('configuration');
Route::patch ('configurations/{configuration}', [ConfigurationController::class, 'patch'])->whereNumber('configuration');
Route::delete('configurations/{configuration}', [ConfigurationController::class, 'destroy'])->whereNumber('configuration');
Route::get   ('configurations/countries', [ConfigurationController::class, 'countries']);
Route::patch ('configurations/countries', [ConfigurationController::class, 'updateCountries']);
Route::get   ('configurations/responsibilities', [ConfigurationController::class, 'getUserResponsibilities']);
Route::patch ('configurations/responsibilities', [ConfigurationController::class, 'updateUserResponsibilities']);
Route::get   ('configurations/columns-by-role/{roleId}',[ConfigurationController::class, 'getColumnsAndCasesByRole'])->whereNumber('roleId');

