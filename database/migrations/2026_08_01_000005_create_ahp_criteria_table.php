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
        Schema::create('ahp_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ahp_session_id')->constrained('ahp_sessions')->onDelete('cascade');
            $table->string('name');
            $table->string('label')->nullable();
            $table->decimal('weight', 8, 5)->nullable();
            $table->decimal('priority_vector', 8, 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahp_criteria');
    }
};
