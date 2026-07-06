<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->string('remedial_mode', 20)->nullable()->after('graded_by')->comment('replace, average, or custom');
            $table->decimal('custom_score', 5, 2)->nullable()->after('remedial_mode')->comment('Teacher-entered score when remedial_mode = custom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['remedial_mode', 'custom_score']);
        });
    }
};
