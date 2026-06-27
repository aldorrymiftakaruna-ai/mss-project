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
    Schema::create('purchase_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('spare_part_id')->constrained('spare_parts')->onDelete('cascade');
        $table->foreignId('requested_by')->constrained('employees')->onDelete('cascade');
        $table->integer('jumlah');
        $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending');
        $table->text('alasan')->nullable();
        $table->date('tanggal_request');
        $table->date('tanggal_fulfilled')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
