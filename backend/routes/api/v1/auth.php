<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

// Prefix sudah 'api/v1/auth' dari bootstrap/app.php
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});