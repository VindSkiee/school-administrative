<?php

use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Teacher\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('health', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('users/{id}/avatar', [UserController::class, 'uploadAvatar']);
    Route::get('schedules/{schedule_id}/attendances', [AttendanceController::class, 'getAttendances']);
});
