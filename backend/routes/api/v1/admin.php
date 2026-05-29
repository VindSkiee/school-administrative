<?php

use App\Http\Controllers\API\Admin\AcademicYearController;
use App\Http\Controllers\API\Admin\ActivityLogController;
use App\Http\Controllers\API\Admin\ClassController;
use App\Http\Controllers\API\Admin\ReportController as AdminReportController;
use App\Http\Controllers\API\Admin\ScheduleController;
use App\Http\Controllers\API\Admin\SemesterReportController as AdminSemesterReport;
use App\Http\Controllers\API\Admin\SubjectController;
use App\Http\Controllers\API\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::patch('users/{id}/reset-password', [UserController::class, 'resetPassword']);
Route::apiResource('users', UserController::class);

// Academic Years Management
Route::apiResource('academic-years', AcademicYearController::class)
    ->except(['show']); // Kita skip show karena datanya sederhana

// Custom route untuk mengaktifkan tahun ajaran (menggunakan PATCH karena hanya mengubah 1 field status)
Route::patch('academic-years/{id}/set-active', [AcademicYearController::class, 'setActive']);

// Class Management
Route::apiResource('classes', ClassController::class);

// Custom routes untuk assign
Route::post('classes/{id}/assign-students', [ClassController::class, 'assignStudents']);
Route::post('classes/{id}/assign-teacher', [ClassController::class, 'assignTeacher']);

// Subject Management
Route::apiResource('subjects', SubjectController::class);

// Schedule Management
Route::apiResource('schedules', ScheduleController::class);

Route::middleware('throttle:heavy-api')->group(function () {
    Route::get('reports/distribution', [AdminReportController::class, 'distribution']);
    Route::get('reports/attendance', [AdminReportController::class, 'attendanceSummary']);
    Route::get('reports/academic', [AdminReportController::class, 'academicSummary']);
    Route::get('reports/semester/{academicYearId}/students/{studentId}/pdf', [AdminSemesterReport::class, 'downloadStudentPdf']);
    Route::post('/classes/migrate-semester', [ClassController::class, 'migrateSemester']);
});

Route::get('activity-logs', [ActivityLogController::class, 'index']);
// Endpoint untuk mempublikasikan rapor dan mengunci semester
Route::patch('academic-years/{id}/publish-reports', [AdminSemesterReport::class, 'publish']);
