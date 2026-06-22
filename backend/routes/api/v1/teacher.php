<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Teacher\AttendanceController;
use App\Http\Controllers\API\Teacher\AttendanceRequestController as TeacherReqController;
use App\Http\Controllers\API\Teacher\MaterialController as TeacherMaterialController;
use App\Http\Controllers\API\Teacher\AssignmentController as TeacherAssignController;
use App\Http\Controllers\API\Teacher\GradeController as TeacherGradeController;
use App\Http\Controllers\API\Teacher\GradeAggregationController as TeacherAggregate;
use App\Http\Controllers\API\Teacher\TeacherDashboardController;
use App\Http\Controllers\API\Teacher\TeacherHomeroomController;
use App\Http\Controllers\API\Teacher\TeacherGradebookController;

Route::get('schedules/today', [AttendanceController::class, 'getTodaySchedules']);
Route::get('schedules/{schedule_id}/students', [AttendanceController::class, 'getStudentsForAttendance']);
Route::post('attendances/bulk', [AttendanceController::class, 'storeBulk']);
Route::get('attendance-requests', [TeacherReqController::class, 'index']);
Route::patch('attendance-requests/{id}/review', [TeacherReqController::class, 'review']);
Route::middleware('throttle:upload-api')->group(function () {
    Route::post('materials', [TeacherMaterialController::class, 'store']);
    Route::post('assignments', [TeacherAssignController::class, 'store']);
});
Route::get('schedules/{schedule_id}/materials', [TeacherMaterialController::class, 'index']);
Route::delete('materials/{id}', [TeacherMaterialController::class, 'destroy']);
Route::delete('assignments/{id}', [TeacherAssignController::class, 'destroy']);
Route::get('schedules/{schedule_id}/assignments', [TeacherAssignController::class, 'index']);
Route::get('assignments', [TeacherAssignController::class, 'globalIndex']);
Route::get('assignments/{id}/submissions', [TeacherAssignController::class, 'submissions']);
Route::post('submissions/{id}/grade', [TeacherGradeController::class, 'store']);
Route::get('schedules/{schedule_id}/grades/aggregate', [TeacherAggregate::class, 'show']);
Route::get('dashboard/stats', [TeacherDashboardController::class, 'index']);
// Rute khusus detail kelas perwalian
Route::get('homeroom-class', [TeacherHomeroomController::class, 'show']);
Route::get('schedules/{schedule_id}', [AttendanceController::class, 'show']);
Route::get('students/{id}', [\App\Http\Controllers\Api\Teacher\TeacherStudentController::class, 'showProfile']);

// === Gradebook (Buku Nilai) ===
Route::get('report-status', [TeacherGradebookController::class, 'reportStatus']);
Route::get('gradebook/academic-years', [TeacherGradebookController::class, 'academicYears']);
Route::get('gradebook/schedules', [TeacherGradebookController::class, 'schedules']);
Route::get('gradebook', [TeacherGradebookController::class, 'index']);
Route::post('gradebook/inline-save', [TeacherGradebookController::class, 'inlineSave']);
Route::get('homeroom/gradebook-recap', [TeacherGradebookController::class, 'homeroomRecap']);