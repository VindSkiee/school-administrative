<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Student\AttendanceRequestController;

Route::get('attendance-requests', [AttendanceRequestController::class, 'index']);
Route::post('attendance-requests', [AttendanceRequestController::class, 'store']);