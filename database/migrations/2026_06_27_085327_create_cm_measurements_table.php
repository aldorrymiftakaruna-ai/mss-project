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
    Schema::create('cm_measurements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
        $table->foreignId('measured_by')->constrained('employees')->onDelete('cascade');
        $table->date('tanggal');
        $table->decimal('vibrasi_de', 5, 2)->nullable();   // Drive End mm/s
        $table->decimal('vibrasi_nde', 5, 2)->nullable();  // Non Drive End mm/s
        $table->decimal('temperature', 5, 2)->nullable();  // °C
        $table->decimal('pressure', 8, 2)->nullable();     // Bar/PSI
        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_measurements');
    }
};
