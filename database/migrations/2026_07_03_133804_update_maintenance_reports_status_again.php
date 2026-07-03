<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE maintenance_reports MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'belum_selesai'");
        DB::statement("UPDATE maintenance_reports SET status = 'belum_selesai' WHERE status IN ('open', 'on_progress')");
        DB::statement("UPDATE maintenance_reports SET status = 'selesai' WHERE status = 'done'");
        DB::statement("ALTER TABLE maintenance_reports MODIFY COLUMN status ENUM('belum_selesai', 'selesai') NOT NULL DEFAULT 'belum_selesai'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE maintenance_reports MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'");
        DB::statement("UPDATE maintenance_reports SET status = 'open' WHERE status = 'belum_selesai'");
        DB::statement("UPDATE maintenance_reports SET status = 'done' WHERE status = 'selesai'");
        DB::statement("ALTER TABLE maintenance_reports MODIFY COLUMN status ENUM('open', 'on_progress', 'done') NOT NULL DEFAULT 'open'");
    }
};
