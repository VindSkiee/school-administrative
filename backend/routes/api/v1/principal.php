<?php

use App\Http\Controllers\API\Principal\DashboardController;
use App\Http\Controllers\API\Principal\SettingController;
use App\Http\Controllers\API\Principal\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:heavy-api')->prefix('dashboard')->group(function () {
    Route::get('overview', [DashboardController::class, 'overview']);
    Route::get('attendance-trends', [DashboardController::class, 'attendanceTrends']);
    Route::get('academic-performance', [DashboardController::class, 'academicPerformance']);
    Route::get('yoy', [DashboardController::class, 'yoy']);
    Route::get('grade-distribution', [DashboardController::class, 'gradeDistribution']);
    Route::get('curriculum-trend', [DashboardController::class, 'curriculumTrend']);
    Route::get('cohort-trend', [DashboardController::class, 'cohortTrend']);
});

// Quick stats (not throttled — lightweight query)
Route::get('dashboard/stats', [DashboardController::class, 'stats']);
Route::get('dashboard/academic-years', [DashboardController::class, 'academicYears']);

// Staff directory
Route::get('staff', [StaffController::class, 'index']);

// Grading settings
Route::get('settings/grading', [SettingController::class, 'getGrading']);
Route::put('settings/grading', [SettingController::class, 'updateGrading']);
