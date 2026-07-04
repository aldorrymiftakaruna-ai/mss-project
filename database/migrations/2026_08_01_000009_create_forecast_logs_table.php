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
        Schema::create('forecast_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // ES atau MA
            $table->string('period');
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->decimal('forecast_value', 12, 2)->nullable();
            $table->decimal('absolute_error', 12, 2)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_logs');
    }
};
