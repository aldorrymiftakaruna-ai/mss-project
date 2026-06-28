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
    Schema::create('cm_measurements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
        $table->foreignId('measured_by')->constrained('employees')->onDelete('cascade');
        $table->date('tanggal');

        // Driver (Motor) - DE
        $table->decimal('driver_de_vib_v', 5, 2)->nullable();
        $table->decimal('driver_de_vib_h', 5, 2)->nullable();
        $table->decimal('driver_de_vib_a', 5, 2)->nullable();
        $table->decimal('driver_de_cf', 5, 2)->nullable();
        $table->decimal('driver_de_temp', 5, 2)->nullable();

        // Driver (Motor) - NDE
        $table->decimal('driver_nde_vib_v', 5, 2)->nullable();
        $table->decimal('driver_nde_vib_h', 5, 2)->nullable();
        $table->decimal('driver_nde_vib_a', 5, 2)->nullable();
        $table->decimal('driver_nde_cf', 5, 2)->nullable();
        $table->decimal('driver_nde_temp', 5, 2)->nullable();

        // Driver Ampere
        $table->decimal('driver_ampere', 6, 2)->nullable();

        // Driven (Gearbox/Pump/dll) - DE
        $table->decimal('driven_de_vib_v', 5, 2)->nullable();
        $table->decimal('driven_de_vib_h', 5, 2)->nullable();
        $table->decimal('driven_de_vib_a', 5, 2)->nullable();
        $table->decimal('driven_de_cf', 5, 2)->nullable();
        $table->decimal('driven_de_temp', 5, 2)->nullable();

        // Driven - NDE
        $table->decimal('driven_nde_vib_v', 5, 2)->nullable();
        $table->decimal('driven_nde_vib_h', 5, 2)->nullable();
        $table->decimal('driven_nde_vib_a', 5, 2)->nullable();
        $table->decimal('driven_nde_cf', 5, 2)->nullable();
        $table->decimal('driven_nde_temp', 5, 2)->nullable();

        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_measurements');
    }
};
