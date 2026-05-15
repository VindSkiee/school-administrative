<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Student\AttendanceRequestController;
use App\Http\Controllers\API\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\API\Student\AssignmentController as StudentAssignController;
use App\Http\Controllers\API\Student\GradeController as StudentGradeController;
use App\Http\Controllers\API\Student\GradeAggregationController as StudentAggregate;
use App\Http\Controllers\API\Student\SemesterReportController as StudentSemesterReport;

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
Route::get('reports/semester', [StudentSemesterReport::class, 'show']);
Route::get('reports/semester/pdf', [StudentSemesterReport::class, 'downloadPdf']);
