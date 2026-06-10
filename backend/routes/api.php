<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Teacher\AttendanceController;

Route::get('health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('users/{id}/avatar', [UserController::class, 'uploadAvatar']);
Route::get('schedules/{schedule_id}/attendances', [AttendanceController::class, 'getAttendances']);