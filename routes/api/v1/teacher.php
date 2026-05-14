<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Teacher\AttendanceController;
use App\Http\Controllers\API\Teacher\AttendanceRequestController as TeacherReqController;
use App\Http\Controllers\API\Teacher\MaterialController as TeacherMaterialController;

// Semua route di sini sudah dibungkus prefix 'api/v1/teacher' dan middleware 'role:teacher' oleh bootstrap/app.php

Route::get('schedules/today', [AttendanceController::class, 'getTodaySchedules']);
Route::get('schedules/{schedule_id}/students', [AttendanceController::class, 'getStudentsForAttendance']);
Route::post('attendances/bulk', [AttendanceController::class, 'storeBulk']);
Route::get('attendance-requests', [TeacherReqController::class, 'index']);
Route::patch('attendance-requests/{id}/review', [TeacherReqController::class, 'review']);
Route::get('materials', [TeacherMaterialController::class, 'index']);
Route::post('materials', [TeacherMaterialController::class, 'store']);
Route::delete('materials/{id}', [TeacherMaterialController::class, 'destroy']);