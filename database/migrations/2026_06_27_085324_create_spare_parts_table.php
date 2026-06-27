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
    Schema::create('spare_parts', function (Blueprint $table) {
        $table->id();
        $table->string('kode_material')->unique();  // 6180100076
        $table->string('deskripsi');                // UCFC 212
        $table->string('satuan')->default('Pcs');
        $table->integer('stok_minimum')->default(1);
        $table->integer('stok_tersedia')->default(0);
        $table->string('kategori')->nullable();     // Bearing, Seal, Chain, dll
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
