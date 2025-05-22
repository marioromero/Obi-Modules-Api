<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-cases', fn() => response()->json(['pong' => 'Cases']))->name('Cases.ping');


// REST para AccidentType
use Modules\Cases\app\Http\Controllers\AccidentTypeController;
Route::get('accident-types', [AccidentTypeController::class, 'index']);
Route::get('accident-types/{accidentType}', [AccidentTypeController::class, 'show']);
Route::post('accident-types', [AccidentTypeController::class, 'store']);
Route::put('accident-types/{accidentType}', [AccidentTypeController::class, 'update']);
Route::patch('accident-types/{accidentType}', [AccidentTypeController::class, 'patch']);
Route::delete('accident-types/{accidentType}', [AccidentTypeController::class, 'destroy']);

// REST para CaseStatus
use Modules\Cases\app\Http\Controllers\CaseStatusController;
Route::get('case-statuses', [CaseStatusController::class, 'index']);
Route::get('case-statuses/{caseStatus}', [CaseStatusController::class, 'show']);
Route::post('case-statuses', [CaseStatusController::class, 'store']);
Route::put('case-statuses/{caseStatus}', [CaseStatusController::class, 'update']);
Route::patch('case-statuses/{caseStatus}', [CaseStatusController::class, 'patch']);
Route::delete('case-statuses/{caseStatus}', [CaseStatusController::class, 'destroy']);

// REST para Comment
use Modules\Cases\app\Http\Controllers\CommentController;
Route::get('comments', [CommentController::class, 'index']);
Route::get('comments/{comment}', [CommentController::class, 'show']);
Route::post('comments', [CommentController::class, 'store']);
Route::put('comments/{comment}', [CommentController::class, 'update']);
Route::patch('comments/{comment}', [CommentController::class, 'patch']);
Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

// REST para Priority
use Modules\Cases\app\Http\Controllers\PriorityController;
Route::get('priorities', [PriorityController::class, 'index']);
Route::get('priorities/{priority}', [PriorityController::class, 'show']);
Route::post('priorities', [PriorityController::class, 'store']);
Route::put('priorities/{priority}', [PriorityController::class, 'update']);
Route::patch('priorities/{priority}', [PriorityController::class, 'patch']);
Route::delete('priorities/{priority}', [PriorityController::class, 'destroy']);

// REST para Case
use Modules\Cases\app\Http\Controllers\CaseController;
Route::get('cases', [CaseController::class, 'index']);
Route::get('cases/{case}', [CaseController::class, 'show']);
Route::post('cases', [CaseController::class, 'store']);
Route::put('cases/{case}', [CaseController::class, 'update']);
Route::patch('cases/{case}', [CaseController::class, 'patch']);
Route::delete('cases/{case}', [CaseController::class, 'destroy']);

