<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eskul_change_requests', function (Blueprint $table) {
            $table->foreignId('requested_eskul_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('eskul_change_requests', function (Blueprint $table) {
            $table->foreignId('requested_eskul_id')->nullable(false)->change();
        });
    }
};
