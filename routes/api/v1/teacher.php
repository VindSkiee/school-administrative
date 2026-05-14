<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Teacher\AttendanceController;
use App\Http\Controllers\API\Teacher\AttendanceRequestController as TeacherReqController;
use App\Http\Controllers\API\Teacher\MaterialController as TeacherMaterialController;
use App\Http\Controllers\API\Teacher\AssignmentController as TeacherAssignController;
use App\Http\Controllers\API\Teacher\GradeController as TeacherGradeController;
use App\Http\Controllers\API\Teacher\GradeAggregationController as TeacherAggregate;

Route::get('schedules/today', [AttendanceController::class, 'getTodaySchedules']);
Route::get('schedules/{schedule_id}/students', [AttendanceController::class, 'getStudentsForAttendance']);
Route::post('attendances/bulk', [AttendanceController::class, 'storeBulk']);
Route::get('attendance-requests', [TeacherReqController::class, 'index']);
Route::patch('attendance-requests/{id}/review', [TeacherReqController::class, 'review']);
Route::get('materials', [TeacherMaterialController::class, 'index']);
Route::post('materials', [TeacherMaterialController::class, 'store']);
Route::delete('materials/{id}', [TeacherMaterialController::class, 'destroy']);
Route::get('assignments', [TeacherAssignController::class, 'index']);
Route::post('assignments', [TeacherAssignController::class, 'store']);
Route::get('assignments/{id}/submissions', [TeacherAssignController::class, 'submissions']);
Route::post('submissions/{id}/grade', [TeacherGradeController::class, 'store']);
Route::get('schedules/{schedule_id}/grades/aggregate', [TeacherAggregate::class, 'show']);