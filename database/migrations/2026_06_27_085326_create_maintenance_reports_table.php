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
    Schema::create('maintenance_reports', function (Blueprint $table) {
        $table->id();
        $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
        $table->foreignId('reported_by')->constrained('employees')->onDelete('cascade');
        $table->enum('shift', ['1', '2', '3']);
        $table->date('tanggal');
        $table->enum('jenis', ['corrective', 'breakdown', 'inspeksi'])->default('corrective');
        $table->text('deskripsi_masalah');
        $table->text('tindakan')->nullable();
        $table->enum('status', ['open', 'on_progress', 'done'])->default('open');
        $table->string('foto_path')->nullable();
        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};
