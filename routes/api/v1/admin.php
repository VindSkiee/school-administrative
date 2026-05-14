<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;

Route::apiResource('users', UserController::class);

// Academic Years Management
Route::apiResource('academic-years', \App\Http\Controllers\API\Admin\AcademicYearController::class)
    ->except(['show']); // Kita skip show karena datanya sederhana

// Custom route untuk mengaktifkan tahun ajaran (menggunakan PATCH karena hanya mengubah 1 field status)
Route::patch('academic-years/{id}/set-active', [\App\Http\Controllers\API\Admin\AcademicYearController::class, 'setActive']);

// Class Management
Route::apiResource('classes', \App\Http\Controllers\API\Admin\ClassController::class);

// Custom routes untuk assign
Route::post('classes/{id}/assign-students', [\App\Http\Controllers\API\Admin\ClassController::class, 'assignStudents']);
Route::post('classes/{id}/assign-teacher', [\App\Http\Controllers\API\Admin\ClassController::class, 'assignTeacher']);

// Subject Management
Route::apiResource('subjects', \App\Http\Controllers\API\Admin\SubjectController::class);