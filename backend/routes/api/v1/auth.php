<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

// Prefix sudah 'api/v1/auth' dari bootstrap/app.php
Route::post('login', [AuthController::class, 'login']);
Route::post('check-requirements', [AuthController::class, 'checkRequirements']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('force-change-password', [AuthController::class, 'forceChangePassword']);
});

Route::middleware(['auth:api', 'password.changed'])->group(function () {
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});
