<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // For ORDER BY created_at DESC (latest())
            $table->index('created_at', 'idx_activity_logs_created_at');

            // For exact-match filter on loggable_type (dropdown)
            $table->index('loggable_type', 'idx_activity_logs_loggable_type');

            // Composite for morph lookup (loggable_type + loggable_id)
            $table->index(['loggable_type', 'loggable_id'], 'idx_activity_logs_loggable');

            // FULLTEXT for search on action column (replaces LIKE '%...%')
            $table->fullText('action', 'ft_activity_logs_action');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_created_at');
            $table->dropIndex('idx_activity_logs_loggable_type');
            $table->dropIndex('idx_activity_logs_loggable');
            $table->dropFullText('ft_activity_logs_action');
        });
    }
};
