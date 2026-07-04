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
        Schema::create('ahp_pairwise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ahp_session_id')->constrained('ahp_sessions')->onDelete('cascade');
            $table->foreignId('criterion_a_id')->constrained('ahp_criteria')->onDelete('cascade');
            $table->foreignId('criterion_b_id')->constrained('ahp_criteria')->onDelete('cascade');
            $table->decimal('value', 5, 2); // skala Saaty 1-9
            $table->timestamps();

            $table->unique(['ahp_session_id', 'criterion_a_id', 'criterion_b_id'], 'ahp_pairwise_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahp_pairwise');
    }
};
