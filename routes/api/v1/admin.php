<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;

Route::apiResource('users', UserController::class);

// Academic Years Management
Route::apiResource('academic-years', \App\Http\Controllers\API\Admin\AcademicYearController::class)
    ->except(['show']); // Kita skip show karena datanya sederhana

// Custom route untuk mengaktifkan tahun ajaran (menggunakan PATCH karena hanya mengubah 1 field status)
Route::patch('academic-years/{id}/set-active', [\App\Http\Controllers\API\Admin\AcademicYearController::class, 'setActive']);