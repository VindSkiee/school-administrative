<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Teacher\AttendanceController;

// Semua route di sini sudah dibungkus prefix 'api/v1/teacher' dan middleware 'role:teacher' oleh bootstrap/app.php

Route::get('schedules/today', [AttendanceController::class, 'getTodaySchedules']);
Route::get('schedules/{schedule_id}/students', [AttendanceController::class, 'getStudentsForAttendance']);
Route::post('attendances/bulk', [AttendanceController::class, 'storeBulk']);