<?php

use Illuminate\Support\Facades\Route;
use Modules\Geography\app\Http\Controllers\CountryController;
use Modules\Geography\app\Http\Controllers\RegionController;
use Modules\Geography\app\Http\Controllers\ProvinceController;
use Modules\Geography\app\Http\Controllers\CommuneController;
use Modules\Geography\app\Http\Controllers\VersionPAController;
use Modules\Geography\app\Http\Controllers\CommuneDistanceController;

Route::get('/ping-geography', fn() => response()->json(['pong' => 'Geography']))->name('Geography.ping');


// REST para Country
Route::get('countries', [CountryController::class, 'index']);
Route::get('countries/{country}', [CountryController::class, 'show']);
Route::post('countries', [CountryController::class, 'store']);
Route::put('countries/{country}', [CountryController::class, 'update']);
Route::patch('countries/{country}', [CountryController::class, 'patch']);
Route::delete('countries/{country}', [CountryController::class, 'destroy']);

// REST para Region
Route::get('regions', [RegionController::class, 'index']);
Route::get('regions/{region}', [RegionController::class, 'show']);
Route::post('regions', [RegionController::class, 'store']);
Route::put('regions/{region}', [RegionController::class, 'update']);
Route::patch('regions/{region}', [RegionController::class, 'patch']);
Route::delete('regions/{region}', [RegionController::class, 'destroy']);

// REST para Province
Route::get('provinces', [ProvinceController::class, 'index']);
Route::get('provinces/{province}', [ProvinceController::class, 'show']);
Route::post('provinces', [ProvinceController::class, 'store']);
Route::put('provinces/{province}', [ProvinceController::class, 'update']);
Route::patch('provinces/{province}', [ProvinceController::class, 'patch']);
Route::delete('provinces/{province}', [ProvinceController::class, 'destroy']);

// REST para Commune
Route::get('communes', [CommuneController::class, 'index']);
Route::get('communes/{commune}', [CommuneController::class, 'show']);
Route::post('communes', [CommuneController::class, 'store']);
Route::put('communes/{commune}', [CommuneController::class, 'update']);
Route::patch('communes/{commune}', [CommuneController::class, 'patch']);
Route::delete('communes/{commune}', [CommuneController::class, 'destroy']);

// REST para VersionPA
Route::get('version-p-as', [VersionPAController::class, 'index']);
Route::get('version-p-as/{versionPA}', [VersionPAController::class, 'show']);
Route::post('version-p-as', [VersionPAController::class, 'store']);
Route::put('version-p-as/{versionPA}', [VersionPAController::class, 'update']);
Route::patch('version-p-as/{versionPA}', [VersionPAController::class, 'patch']);
Route::delete('version-p-as/{versionPA}', [VersionPAController::class, 'destroy']);

// Ruta para obtener distancia entre comunas
Route::get('commune-distances/{originId}/{destinationId}', [CommuneDistanceController::class, 'getDistance']);

