<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping-mailing', fn() => response()->json(['pong' => 'Mailing']))->name('Mailing.ping');


// REST para Department
use Modules\Mailing\app\Http\Controllers\DepartmentController;
Route::get('departments', [DepartmentController::class, 'index']);
Route::get('departments/{department}', [DepartmentController::class, 'show']);
Route::post('departments', [DepartmentController::class, 'store']);
Route::put('departments/{department}', [DepartmentController::class, 'update']);
Route::patch('departments/{department}', [DepartmentController::class, 'patch']);
Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);

// REST para EmailTemplate
use Modules\Mailing\app\Http\Controllers\EmailTemplateController;
Route::get('email-templates', [EmailTemplateController::class, 'index']);
Route::get('email-templates/{emailTemplate}', [EmailTemplateController::class, 'show']);
Route::post('email-templates', [EmailTemplateController::class, 'store']);
Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update']);
Route::patch('email-templates/{emailTemplate}', [EmailTemplateController::class, 'patch']);
Route::delete('email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy']);

// REST para CustomersSet
use Modules\Mailing\app\Http\Controllers\CustomersSetController;
Route::get('customers-sets', [CustomersSetController::class, 'index']);
Route::get('customers-sets/{customersSet}', [CustomersSetController::class, 'show']);
Route::post('customers-sets', [CustomersSetController::class, 'store']);
Route::put('customers-sets/{customersSet}', [CustomersSetController::class, 'update']);
Route::patch('customers-sets/{customersSet}', [CustomersSetController::class, 'patch']);
Route::delete('customers-sets/{customersSet}', [CustomersSetController::class, 'destroy']);

// REST para CustomerDetail
use Modules\Mailing\app\Http\Controllers\CustomerDetailController;
Route::get('customer-details', [CustomerDetailController::class, 'index']);
Route::get('customer-details/{customerDetail}', [CustomerDetailController::class, 'show']);
Route::post('customer-details', [CustomerDetailController::class, 'store']);
Route::put('customer-details/{customerDetail}', [CustomerDetailController::class, 'update']);
Route::patch('customer-details/{customerDetail}', [CustomerDetailController::class, 'patch']);
Route::delete('customer-details/{customerDetail}', [CustomerDetailController::class, 'destroy']);

// REST para EmailSchedule
use Modules\Mailing\app\Http\Controllers\EmailScheduleController;
Route::get('email-schedules', [EmailScheduleController::class, 'index']);
Route::get('email-schedules/{emailSchedule}', [EmailScheduleController::class, 'show']);
Route::post('email-schedules', [EmailScheduleController::class, 'store']);
Route::put('email-schedules/{emailSchedule}', [EmailScheduleController::class, 'update']);
Route::patch('email-schedules/{emailSchedule}', [EmailScheduleController::class, 'patch']);
Route::delete('email-schedules/{emailSchedule}', [EmailScheduleController::class, 'destroy']);
