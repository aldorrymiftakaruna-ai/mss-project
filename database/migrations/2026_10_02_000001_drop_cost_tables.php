<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel cost_analyses dan cost_rates yang tidak lagi digunakan.
     * Menu Forecasting dan Analisis Biaya telah dihapus dari aplikasi.
     */
    public function up(): void
    {
        Schema::dropIfExists('cost_analyses');
        Schema::dropIfExists('cost_rates');
    }

    /**
     * Jika rollback, buat ulang tabel (hanya struktur).
     */
    public function down(): void
    {
        if (!Schema::hasTable('cost_rates')) {
            Schema::create('cost_rates', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->decimal('downtime_rate_per_min', 12, 2)->default(0);
                $table->decimal('overtime_rate_per_hour', 12, 2)->default(0);
                $table->date('effective_date');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cost_analyses')) {
            Schema::create('cost_analyses', function ($table) {
                $table->id();
                $table->foreignId('maintenance_report_id')->constrained()->onDelete('cascade');
                $table->decimal('downtime_cost', 14, 2)->default(0);
                $table->decimal('overtime_cost', 14, 2)->default(0);
                $table->decimal('labor_cost', 14, 2)->default(0);
                $table->decimal('sparepart_cost', 14, 2)->default(0);
                $table->timestamp('analyzed_at')->nullable();
                $table->timestamps();
            });
        }
    }
};
