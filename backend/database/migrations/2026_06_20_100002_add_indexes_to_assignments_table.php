<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // FULLTEXT for search on title + description (replaces LIKE '%...%')
            $table->fullText(['title', 'description'], 'ft_assignments_search');

            // Index for created_at ordering and date range filter (replaces whereDate)
            $table->index('created_at', 'idx_assignments_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropFullText('ft_assignments_search');
            $table->dropIndex('idx_assignments_created_at');
        });
    }
};
