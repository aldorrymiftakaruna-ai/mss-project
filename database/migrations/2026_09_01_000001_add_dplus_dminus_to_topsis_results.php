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
        Schema::table('topsis_results', function (Blueprint $table) {
            $table->decimal('d_plus', 12, 6)->default(0)->after('score');
            $table->decimal('d_minus', 12, 6)->default(0)->after('d_plus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topsis_results', function (Blueprint $table) {
            $table->dropColumn(['d_plus', 'd_minus']);
        });
    }
};
