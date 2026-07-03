<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            // 1. Ubah kolom "jenis" dari enum('corrective','breakdown','inspeksi')
            //    menjadi enum('corrective','preventive'), default 'corrective'
            //    Gunakan DB::statement untuk mengubah enum karena lebih reliable
            //    dibandingkan $table->enum()->change() di berbagai driver database.
            DB::statement("ALTER TABLE maintenance_reports MODIFY COLUMN jenis ENUM('corrective', 'preventive') NOT NULL DEFAULT 'corrective'");

            // 2. Ubah kolom "foto_path" dari string(255) menjadi text, tetap nullable
            $table->text('foto_path')->nullable()->change();

            // 3. Tambah kolom baru "ai_analyzed" boolean default false, setelah "catatan"
            $table->boolean('ai_analyzed')->default(false)->after('catatan');

            // 4. Tambah kolom baru "ai_confidence" integer nullable, setelah "ai_analyzed"
            $table->integer('ai_confidence')->nullable()->after('ai_analyzed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            // Hapus kolom baru yang ditambahkan
            $table->dropColumn(['ai_analyzed', 'ai_confidence']);

            // Kembalikan foto_path ke string(255), tetap nullable
            $table->string('foto_path', 255)->nullable()->change();

            // Kembalikan jenis ke enum original: corrective, breakdown, inspeksi
            DB::statement("ALTER TABLE maintenance_reports MODIFY COLUMN jenis ENUM('corrective', 'breakdown', 'inspeksi') NOT NULL DEFAULT 'corrective'");
        });
    }
};
