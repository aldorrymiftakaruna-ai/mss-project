<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reset semua status yang tidak valid ke 'normal' sebelum ubah enum
        DB::statement("UPDATE assets SET status = 'normal' WHERE status NOT IN ('normal', 'alarm', 'danger')");

        // MySQL: ALTER COLUMN untuk ubah enum
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('normal', 'alarm', 'danger') NOT NULL DEFAULT 'normal'");
    }

    public function down(): void
    {
        DB::statement("UPDATE assets SET status = 'normal' WHERE status NOT IN ('normal', 'warning', 'critical', 'breakdown')");
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('normal', 'warning', 'critical', 'breakdown') NOT NULL DEFAULT 'normal'");
    }
};