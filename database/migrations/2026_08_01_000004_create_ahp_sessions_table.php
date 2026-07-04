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
        Schema::create('ahp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('ahli_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('consistency_ratio', 8, 5)->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahp_sessions');
    }
};
