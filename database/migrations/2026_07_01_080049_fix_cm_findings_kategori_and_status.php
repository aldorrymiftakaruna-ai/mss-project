<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Perbaikan final untuk cm_findings:
     * - kategori: dari ENUM jadi VARCHAR (free text, nullable)
     * - severity: diubah jadi VARCHAR (sudah nullable dari migration sebelumnya)
     * - status: diubah jadi VARCHAR (hanya open/closed)
     * - finding_code: VARCHAR cukup panjang
     * - pic: VARCHAR
     */
    public function up(): void
    {
        // Ubah kategori dari ENUM ke VARCHAR(255) free-text, nullable
        DB::statement("ALTER TABLE cm_findings MODIFY kategori VARCHAR(255) NULL");

        // Ubah severity dari ENUM ke VARCHAR(20), nullable
        DB::statement("ALTER TABLE cm_findings MODIFY severity VARCHAR(20) NULL");

        // Ubah status dari ENUM ke VARCHAR(20), default 'open'
        DB::statement("ALTER TABLE cm_findings MODIFY status VARCHAR(20) NOT NULL DEFAULT 'open'");

        // Ubah finding_code jadi VARCHAR(50) — cukup untuk format VIDR-YYYY-XXXXX
        DB::statement("ALTER TABLE cm_findings MODIFY finding_code VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke ENUM lama (hanya untuk rollback)
        DB::statement("ALTER TABLE cm_findings MODIFY kategori ENUM('korosi','kebocoran','baut_loose','guard_lepas','abnormal_suara','lainnya') NOT NULL");
        DB::statement("ALTER TABLE cm_findings MODIFY severity ENUM('low','medium','high') NULL");
        DB::statement("ALTER TABLE cm_findings MODIFY status ENUM('open','closed') NOT NULL DEFAULT 'open'");
    }
};
