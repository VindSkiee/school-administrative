<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Student\AttendanceRequestController;
use App\Http\Controllers\API\Student\MaterialController as StudentMaterialController;

Route::get('attendance-requests', [AttendanceRequestController::class, 'index']);
Route::post('attendance-requests', [AttendanceRequestController::class, 'store']);
Route::get('materials', [StudentMaterialController::class, 'index']);
Route::get('materials/{id}/download', [StudentMaterialController::class, 'download']);