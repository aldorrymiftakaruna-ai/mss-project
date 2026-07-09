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
        Schema::table('decision_recommendations', function (Blueprint $table) {
            if (!Schema::hasColumn('decision_recommendations', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('description');
                $table->index('generated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decision_recommendations', function (Blueprint $table) {
            $table->dropIndex(['generated_at']);
            $table->dropColumn('generated_at');
        });
    }
};
