<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SATU file migration untuk SEMUA perubahan kolom berikut:
     *
     * 1. Tabel employees:
     *    - Ubah telegram_id dari string(unique) menjadi bigint(nullable, unique)
     *      karena Telegram user ID adalah integer 64-bit (bigint).
     *
     * 2. Tabel maintenance_reports:
     *    - report_code (string, nullable, unique) — kode laporan RPT-YYYYMMDD-XXXX
     *    - work_duration_minutes (integer, nullable) — durasi pengerjaan dalam menit
     *    - root_cause (text, nullable) — penyebab kerusakan/pekerjaan
     *    - photo_documentation (json, nullable) — array path foto dokumentasi
     *    - wizard_started_at (timestamp, nullable) — kapan wizard dimulai
     *    - submitted_at (timestamp, nullable) — kapan laporan dikonfirmasi teknisi
     *    - ai_suggestion_json (json, nullable) — saran alias AI mentah dari response
     */
    public function up(): void
    {
        // ====== 1. Tabel employees ======
        Schema::table('employees', function (Blueprint $table) {
            // Hapus kolom telegram_id string lama, buat ulang sebagai bigint
            // Karena unique constraint, drop dulu lalu buat baru
            $table->dropColumn('telegram_id');
        });

        // Gunakan statement terpisah karena dropColumn + addColumn dengan nama
        // yang sama dalam satu callback bisa bermasalah di beberapa driver
        Schema::table('employees', function (Blueprint $table) {
            $table->bigInteger('telegram_id')->nullable()->unique()->after('name');
        });

        // ====== 2. Tabel maintenance_reports ======
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->string('report_code', 50)->nullable()->unique()->after('id');
            $table->integer('work_duration_minutes')->nullable()->after('tindakan');
            $table->text('root_cause')->nullable()->after('work_duration_minutes');
            $table->json('photo_documentation')->nullable()->after('root_cause');
            $table->timestamp('wizard_started_at')->nullable()->after('photo_documentation');
            $table->timestamp('submitted_at')->nullable()->after('wizard_started_at');
            $table->json('ai_suggestion_json')->nullable()->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan employees.telegram_id ke string
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('telegram_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('telegram_id')->nullable()->unique()->after('name');
        });

        // Hapus kolom baru dari maintenance_reports
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropColumn([
                'report_code',
                'work_duration_minutes',
                'root_cause',
                'photo_documentation',
                'wizard_started_at',
                'submitted_at',
                'ai_suggestion_json',
            ]);
        });
    }
};
