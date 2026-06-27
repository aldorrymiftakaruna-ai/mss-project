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
    Schema::create('manpower_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('maintenance_report_id')->constrained('maintenance_reports')->onDelete('cascade');
        $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
        $table->time('jam_mulai')->nullable();
        $table->time('jam_selesai')->nullable();
        $table->integer('durasi_menit')->nullable();
        $table->text('keterangan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manpower_logs');
    }
};
