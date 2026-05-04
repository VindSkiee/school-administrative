<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;

// Prefix sudah 'api/v1/admin' dan sudah diproteksi role:admin dari bootstrap/app.php
Route::post('users', [UserController::class, 'store']);