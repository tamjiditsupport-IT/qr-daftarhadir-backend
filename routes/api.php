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
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Broadcast;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware('auth:sanctum')->group(function () {
    // ── Accessible by all authenticated users ──
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

    // Global Search
    Route::get('/search', [\App\Http\Controllers\Api\SearchController::class, 'index']);

    // ── Asatidz Management ──
    Route::middleware('permission:manage_asatidz,web')->group(function () {
        Route::post('/import-excel', [AsatidzController::class, 'importExcel']);
        Route::get('/asatidz/template', [AsatidzController::class, 'downloadTemplate']);
        Route::get('/asatidz/{id}/history', [AsatidzController::class, 'history']);
        Route::apiResource('asatidz', AsatidzController::class);
    });

    // ── Meetings Management ──
    Route::middleware('permission:manage_meetings,web')->group(function () {
        Route::post('/meetings/import-excel', [MeetingController::class, 'importExcel']);
        Route::get('/meetings/template', [MeetingController::class, 'downloadTemplate']);
        Route::apiResource('meeting-types', \App\Http\Controllers\Api\MeetingTypeController::class);
        Route::post('/meetings/{id}/start', [MeetingController::class, 'start']);
        Route::post('/meetings/{id}/finish', [MeetingController::class, 'finish']);
        Route::get('/meetings/{id}/export/excel', [ExportController::class, 'exportExcel']);
        Route::get('/meetings/{id}/export/pdf', [ExportController::class, 'exportPdf']);
        Route::apiResource('meetings', MeetingController::class);

        // Meeting Attachments
        Route::post('/meetings/{id}/attachments', [\App\Http\Controllers\MeetingAttachmentController::class, 'store']);
        Route::delete('/attachments/{id}', [\App\Http\Controllers\MeetingAttachmentController::class, 'destroy']);
        Route::get('/attachments/{id}/download', [\App\Http\Controllers\MeetingAttachmentController::class, 'download']);

        // Attendance
        Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
        Route::post('/attendance/manual', [AttendanceController::class, 'manual']);
    });

    // ── QR Cards ──
    Route::middleware('permission:manage_asatidz,web')->group(function () {
        Route::get('/qr/{id}', [QrCardController::class, 'show']);
        Route::post('/qr/reprint', [QrCardController::class, 'reprint']);
    });

    // ── Reports ──
    Route::middleware('permission:view_reports,web')->group(function () {
        Route::get('/reports/meetings', [ReportController::class, 'meetings']);
        Route::get('/reports/units', [ReportController::class, 'units']);
        Route::get('/reports/asatidz', [ReportController::class, 'asatidz']);
        Route::get('/reports/monthly', [ReportController::class, 'monthly']);
        Route::get('/reports/yearly', [ReportController::class, 'yearly']);
        Route::get('/reports/export', [ReportController::class, 'export']);
    });

    // ── Approvals (Admin Yayasan) ──
    Route::middleware('permission:manage_approvals,web')->group(function () {
        Route::get('/approvals', [\App\Http\Controllers\Api\ApprovalController::class, 'index']);
        Route::post('/approvals', [\App\Http\Controllers\Api\ApprovalController::class, 'store']);
        Route::post('/approvals/{id}/resolve', [\App\Http\Controllers\Api\ApprovalController::class, 'resolve']);
    });

    // ── User & Role Management (Super Admin) ──
    Route::middleware('permission:manage_users,web')->group(function () {
        Route::post('/users/import-excel', [UserController::class, 'importExcel']);
        Route::get('/users/template', [UserController::class, 'downloadTemplate']);
        Route::apiResource('users', UserController::class);
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::put('/roles/{id}', [RoleController::class, 'update']);
        Route::get('/audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
    });

    // ── Master Data (Super Admin) ──
    Route::middleware('permission:manage_master_data,web')->group(function () {
        Route::get('/units/tree', [UnitController::class, 'tree']);
        Route::apiResource('units', UnitController::class);
        Route::apiResource('positions', PositionController::class);
    });

    // ── Settings (Super Admin) ──
    Route::middleware('permission:manage_settings,web')->group(function () {
        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
});
