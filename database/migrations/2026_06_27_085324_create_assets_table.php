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
    Schema::create('assets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
        $table->string('tag_no')->unique();        // SC14, SC03A, P-6163P7
        $table->string('name');                    // Nama equipment
        $table->string('model')->nullable();       // Screw Feeder, Varmex, dll
        $table->string('brand')->nullable();       // Merk
        $table->string('serial_number')->nullable();
        $table->enum('type', ['rotating', 'static', 'electrical', 'instrument'])->default('rotating');
        $table->enum('status', ['normal', 'warning', 'critical', 'breakdown'])->default('normal');
        $table->text('description')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
