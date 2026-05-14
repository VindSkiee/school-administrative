<?php

use App\Http\Controllers\API\Principal\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:heavy-api')->prefix('dashboard')->group(function () {
    Route::get('overview', [DashboardController::class, 'overview']);
    Route::get('attendance-trends', [DashboardController::class, 'attendanceTrends']);
    Route::get('academic-performance', [DashboardController::class, 'academicPerformance']);
});
