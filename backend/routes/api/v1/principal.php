<?php

use App\Http\Controllers\API\Principal\DashboardController;
use App\Http\Controllers\API\Principal\SettingController;
use App\Http\Controllers\API\Principal\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:heavy-api')->prefix('dashboard')->group(function () {
    Route::get('overview', [DashboardController::class, 'overview']);
    Route::get('attendance-trends', [DashboardController::class, 'attendanceTrends']);
    Route::get('academic-performance', [DashboardController::class, 'academicPerformance']);
});

// Quick stats (not throttled — lightweight query)
Route::get('dashboard/stats', [DashboardController::class, 'stats']);

// Staff directory
Route::get('staff', [StaffController::class, 'index']);

// Grading settings
Route::get('settings/grading', [SettingController::class, 'getGrading']);
Route::put('settings/grading', [SettingController::class, 'updateGrading']);
