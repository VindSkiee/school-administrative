<?php

use App\Http\Controllers\API\Student\AssignmentController as StudentAssignController;
use App\Http\Controllers\API\Student\AttendanceRequestController;
use App\Http\Controllers\Api\Student\ClassDetailController;
use App\Http\Controllers\API\Student\EskulController as StudentEskulController;
use App\Http\Controllers\API\Student\GradeAggregationController as StudentAggregate;
use App\Http\Controllers\API\Student\GradeController as StudentGradeController;
use App\Http\Controllers\API\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\API\Student\SemesterReportController as StudentSemesterReport;
use App\Http\Controllers\Api\Student\StudentDashboardController;
use App\Http\Controllers\API\Student\StudentScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('attendance-requests', [AttendanceRequestController::class, 'index']);
Route::get('materials', [StudentMaterialController::class, 'index']);
Route::get('materials/{id}/download', [StudentMaterialController::class, 'download']);
Route::get('assignments', [StudentAssignController::class, 'index']);
Route::middleware('throttle:upload-api')->group(function () {
    Route::post('assignments/{id}/submit', [StudentAssignController::class, 'submit']);
    Route::post('attendance-requests', [AttendanceRequestController::class, 'store']);
});
Route::get('grades', [StudentGradeController::class, 'index']);
Route::get('grades/aggregate', [StudentAggregate::class, 'index']);
// Endpoint khusus yang dilindungi Gatekeeper untuk mengambil rekap nilai akhir
Route::get('reports/academic-years', [StudentSemesterReport::class, 'academicYears']);
Route::get('reports/report-status', [StudentSemesterReport::class, 'reportStatus']);
Route::get('reports/semester', [StudentSemesterReport::class, 'show']);
Route::get('reports/semester/pdf', [StudentSemesterReport::class, 'downloadPdf']);
Route::get('dashboard', [StudentDashboardController::class, 'index']);
Route::get('schedules', [StudentScheduleController::class, 'index']);
Route::get('schedules/{id}', [StudentScheduleController::class, 'show']);
Route::get('class-detail', [ClassDetailController::class, 'index']);

// Eskul Selection & Management
Route::get('eskuls/options', [StudentEskulController::class, 'options']);
Route::post('eskuls', [StudentEskulController::class, 'store']);
Route::get('eskuls/my', [StudentEskulController::class, 'myEskuls']);
Route::post('eskuls/skip', [StudentEskulController::class, 'skip']);
Route::get('eskuls/deadline', [StudentEskulController::class, 'getDeadline']);
Route::post('eskuls/change-request', [StudentEskulController::class, 'submitChangeRequest']);
Route::delete('eskuls/change-request', [StudentEskulController::class, 'cancelChangeRequest']);
