<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\AsatidzController;
use App\Http\Controllers\Api\QrCardController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\AttendanceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Master Data - Organizations
    Route::get('/units/tree', [UnitController::class, 'tree']);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('positions', PositionController::class);

    // Master Data - Asatidz
    Route::post('/import-excel', [AsatidzController::class, 'importExcel']);
    Route::apiResource('asatidz', AsatidzController::class);
    
    // QR Cards
    Route::get('/qr/{id}', [QrCardController::class, 'show']);
    Route::post('/qr/reprint', [QrCardController::class, 'reprint']);

    // Meetings
    Route::post('/meetings/{id}/start', [MeetingController::class, 'start']);
    Route::post('/meetings/{id}/finish', [MeetingController::class, 'finish']);
    Route::apiResource('meetings', MeetingController::class);

    // Approvals (Admin Yayasan)
    // TODO: move to ApprovalController
    
    // Attendance
    Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
    Route::post('/attendance/manual', [AttendanceController::class, 'manual']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
});
