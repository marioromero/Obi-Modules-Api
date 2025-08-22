<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-cases', fn() => response()->json(['pong' => 'Cases']))->name('Cases.ping');


// REST para AccidentType
use Modules\Cases\app\Http\Controllers\AccidentTypeController;
Route::get('accident-types', [AccidentTypeController::class, 'index']);
Route::post('accident-types', [AccidentTypeController::class, 'store']);
Route::get('accident-types/{accidentType}', [AccidentTypeController::class, 'show'])->whereNumber('accidentType');
Route::put('accident-types/{accidentType}', [AccidentTypeController::class, 'update'])->whereNumber('accidentType');
Route::patch('accident-types/{accidentType}', [AccidentTypeController::class, 'patch'])->whereNumber('accidentType');
Route::delete('accident-types/{accidentType}', [AccidentTypeController::class, 'destroy'])->whereNumber('accidentType');


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

// REST para Cases
use Modules\Cases\app\Http\Controllers\CaseController;
use Modules\Cases\app\Http\Controllers\CaseSubstateController;
Route::get('cases/stats', [CaseController::class, 'stats']);
Route::get('cases', [CaseController::class, 'index']);
Route::post('cases', [CaseController::class, 'store']);
Route::get('cases/{case}', [CaseController::class, 'show'])->whereNumber('case');
Route::put('cases/{case}', [CaseController::class, 'update'])->whereNumber('case');
Route::patch('cases/{case}', [CaseController::class, 'patch'])->whereNumber('case');
Route::delete('cases/{case}', [CaseController::class, 'destroy'])->whereNumber('case');
Route::get('cases/agent/{agent}/recent', [CaseController::class, 'recentByAgent'])->whereNumber('agent');       // TraroUser
Route::get('cases/by-customer/{customer}', [CaseController::class, 'byCustomer'])->whereNumber('customer');       // Customer
Route::get('cases/office-by-user/{user}', [CaseController::class, 'officeByUser'])->whereNumber('user');         // TraroUser
Route::get('cases/{case}/transitions', [CaseController::class, 'transitions'])->whereNumber('case');
Route::post('cases/{case}/transition', [CaseController::class, 'transition'])->whereNumber('case');
Route::post('cases/{case}/substate', [CaseSubstateController::class, 'update'])->whereNumber('case');

// REST para Agreement
use Modules\Cases\app\Http\Controllers\AgreementController;
Route::get('agreements', [AgreementController::class, 'index']);
Route::get('agreements/{agreement}', [AgreementController::class, 'show']);
Route::post('agreements', [AgreementController::class, 'store']);
Route::put('agreements/{agreement}', [AgreementController::class, 'update']);
Route::patch('agreements/{agreement}', [AgreementController::class, 'patch']);
Route::delete('agreements/{agreement}', [AgreementController::class, 'destroy']);
