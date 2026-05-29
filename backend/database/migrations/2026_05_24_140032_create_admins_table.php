<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            // user_id sebagai Primary Key & Foreign Key ke tabel users
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();

            // NIP bersifat Wajib (Required) dan Unik
            $table->string('nip', 50)->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
