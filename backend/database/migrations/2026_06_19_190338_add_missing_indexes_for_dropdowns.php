<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Indeks untuk pengurutan nama di semua dropdown
        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
        });

        // 2. Indeks untuk filter siswa aktif
        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};