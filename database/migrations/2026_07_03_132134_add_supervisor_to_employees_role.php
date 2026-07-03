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
        // Ubah enum role: tambah 'supervisor'
        DB::statement("ALTER TABLE employees MODIFY COLUMN role ENUM('foreman', 'teknisi', 'supervisor') NOT NULL DEFAULT 'teknisi'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum sebelumnya
        DB::statement("ALTER TABLE employees MODIFY COLUMN role ENUM('foreman', 'teknisi') NOT NULL DEFAULT 'teknisi'");
    }
};

