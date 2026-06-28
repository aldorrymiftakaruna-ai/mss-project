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
        $table->string('tag_no')->unique();
        $table->string('description');
        $table->string('model')->nullable();
        $table->string('serial_number')->nullable();
        $table->string('head_capacity')->nullable();
        $table->decimal('motor_kw', 8, 2)->nullable();
        $table->integer('motor_rpm')->nullable();
        $table->decimal('motor_ampere', 8, 2)->nullable();
        $table->enum('status', ['normal', 'warning', 'critical', 'breakdown'])->default('normal');
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
