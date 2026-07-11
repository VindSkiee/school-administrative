<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('submitted_at');
            $table->dateTime('edited_at')->nullable()->after('is_late');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['is_late', 'edited_at']);
        });
    }
};
