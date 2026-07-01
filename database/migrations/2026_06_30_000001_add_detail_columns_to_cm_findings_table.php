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
        Schema::table('cm_findings', function (Blueprint $table) {
            // Foto tambahan (foto_path yang lama tetap dipakai sebagai foto pertama)
            $table->string('foto_path_2')->nullable()->after('foto_path');
            $table->string('foto_path_3')->nullable()->after('foto_path_2');

            // Detail tindak lanjut (mengikuti kolom Analysis/Action/PIC/Date Action/Remark di Excel)
            $table->text('analysis')->nullable()->after('deskripsi');
            $table->text('action')->nullable()->after('analysis');
            $table->foreignId('pic_id')->nullable()->after('action')
                ->constrained('employees')->onDelete('set null');
            $table->date('date_action')->nullable()->after('pic_id');
            $table->text('remark')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_findings', function (Blueprint $table) {
            $table->dropForeign(['pic_id']);
            $table->dropColumn([
                'foto_path_2',
                'foto_path_3',
                'analysis',
                'action',
                'pic_id',
                'date_action',
                'remark',
            ]);
        });
    }
};