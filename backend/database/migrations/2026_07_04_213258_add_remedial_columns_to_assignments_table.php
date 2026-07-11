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
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('is_remedial')->default(false)->after('attachments');
            $table->string('remedial_for_type', 20)->nullable()->after('is_remedial')->comment('ujian_harian, uts, or uas');
            $table->foreignId('linked_assignment_id')->nullable()->after('remedial_for_type')->constrained('assignments')->nullOnDelete()->comment('Parent exam assignment');
        });

        // Index for remedial lookups
        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['linked_assignment_id', 'is_remedial'], 'idx_assignments_remedial');
            $table->index('remedial_for_type', 'idx_assignments_remedial_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_remedial');
            $table->dropIndex('idx_assignments_remedial_type');
            $table->dropForeign(['linked_assignment_id']);
            $table->dropColumn(['is_remedial', 'remedial_for_type', 'linked_assignment_id']);
        });
    }
};
