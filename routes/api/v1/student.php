<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Student\AttendanceRequestController;
use App\Http\Controllers\API\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\API\Student\AssignmentController as StudentAssignController;
use App\Http\Controllers\API\Student\GradeController as StudentGradeController;

Route::get('attendance-requests', [AttendanceRequestController::class, 'index']);
Route::post('attendance-requests', [AttendanceRequestController::class, 'store']);
Route::get('materials', [StudentMaterialController::class, 'index']);
Route::get('materials/{id}/download', [StudentMaterialController::class, 'download']);
Route::get('assignments', [StudentAssignController::class, 'index']);
Route::post('assignments/{id}/submit', [StudentAssignController::class, 'submit']);
Route::get('grades', [StudentGradeController::class, 'index']);