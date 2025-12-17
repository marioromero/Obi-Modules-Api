<?php

use Illuminate\Support\Facades\Route;
use Modules\Configurations\app\Http\Controllers\TypeController;
use Modules\Configurations\app\Http\Controllers\ConfigurationController;

//Route::apiResource('configurations', ConfigurationController::class)
  //   ->names('configurations');

// REST para Type
Route::get('types', [TypeController::class, 'index']);
Route::get('types/{type}', [TypeController::class, 'show']);
Route::post('types', [TypeController::class, 'store']);
Route::put('types/{type}', [TypeController::class, 'update']);
Route::patch('types/{type}', [TypeController::class, 'patch']);
Route::delete('types/{type}', [TypeController::class, 'destroy']);

// REST para Configuration
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
Route::get('configurations/filters-by-user/{user}', [ConfigurationController::class, 'filtersByUsers'])->whereNumber('user');
Route::get('configurations/filters-by-user/{user}/cases/{key}',[ConfigurationController::class, 'filterCasesByKey'])->whereNumber('user');
Route::get('users/{user}/filters-columns/{paso}/{filter?}', [ConfigurationController::class, 'getUsersFiltersAndColumns'])->name('configurations.users.filters-columns');
Route::get   ('configurations/agents-available',  [ConfigurationController::class, 'getAgentsAvailable']);
Route::patch ('configurations/agents-available',  [ConfigurationController::class, 'updateAgentsAvailable']);
Route::get   ('configurations/consultants-available',  [ConfigurationController::class, 'getConsultantsAvailable']);
Route::patch ('configurations/consultants-available',  [ConfigurationController::class, 'updateConsultantsAvailable']);

// Rutas para filtros de usuario en configuraciones
Route::get( 'configurations/user-filters/{user}/{step?}',[ConfigurationController::class, 'indexFilters'])->whereNumber('user');
Route::get('configurations/user-filters/{user}/{step}/{key}',[ConfigurationController::class, 'showFilters'])->whereNumber('user');
Route::post('configurations/user-filters/{user}/{step}',[ConfigurationController::class, 'storeFilters'])->whereNumber('user');
Route::put('configurations/user-filters/{user}/{step}/{key}',[ConfigurationController::class, 'updateFilters'])->whereNumber('user');
Route::delete('configurations/user-filters/{user}/{step}/{key}',[ConfigurationController::class, 'destroyFilters'])->whereNumber('user');
