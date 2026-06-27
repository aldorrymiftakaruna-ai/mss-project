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
    Schema::create('cm_findings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
        $table->foreignId('reported_by')->constrained('employees')->onDelete('cascade');
        $table->date('tanggal');
        $table->enum('kategori', ['korosi', 'kebocoran', 'baut_loose', 'guard_lepas', 'abnormal_suara', 'lainnya']);
        $table->text('deskripsi');
        $table->enum('severity', ['low', 'medium', 'high'])->default('low');
        $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
        $table->string('foto_path')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_findings');
    }
};
