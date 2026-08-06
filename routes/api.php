<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\AsatidzController;
use App\Http\Controllers\Api\QrCardController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\AttendanceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Master Data - Organizations
    Route::get('/units/tree', [UnitController::class, 'tree']);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('users', UserController::class);
    Route::get('/roles', [RoleController::class, 'index']);

    // Master Data - Asatidz
    Route::post('/import-excel', [AsatidzController::class, 'importExcel']);
    Route::apiResource('asatidz', AsatidzController::class);
    
    // QR Cards
    Route::get('/qr/{id}', [QrCardController::class, 'show']);
    Route::post('/qr/reprint', [QrCardController::class, 'reprint']);

    // Meetings
    Route::get('/meeting-types', [\App\Http\Controllers\Api\MeetingTypeController::class, 'index']);
    Route::post('/meetings/{id}/start', [MeetingController::class, 'start']);
    Route::post('/meetings/{id}/finish', [MeetingController::class, 'finish']);
    Route::get('/meetings/{id}/export/excel', [ExportController::class, 'exportExcel']);
    Route::get('/meetings/{id}/export/pdf', [ExportController::class, 'exportPdf']);
    Route::apiResource('meetings', MeetingController::class);

    // Approvals (Admin Yayasan)
    Route::get('/approvals', [\App\Http\Controllers\Api\ApprovalController::class, 'index']);
    Route::post('/approvals', [\App\Http\Controllers\Api\ApprovalController::class, 'store']);
    Route::post('/approvals/{id}/resolve', [\App\Http\Controllers\Api\ApprovalController::class, 'resolve']);
    
    // Attendance
    Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
    Route::post('/attendance/manual', [AttendanceController::class, 'manual']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    // Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
});
