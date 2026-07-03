<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->integer('downtime_minutes')->nullable()->after('work_duration_minutes')
                ->comment('Durasi equipment berhenti (dari teknisi), dalam menit');

            $table->boolean('is_overtime')->default(false)->after('catatan')
                ->comment('Apakah pekerjaan ini dikerjakan lembur');

            $table->decimal('overtime_hours', 5, 1)->nullable()->after('is_overtime')
                ->comment('Jumlah jam lembur untuk pekerjaan ini');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropColumn(['downtime_minutes', 'is_overtime', 'overtime_hours']);
        });
    }
};
