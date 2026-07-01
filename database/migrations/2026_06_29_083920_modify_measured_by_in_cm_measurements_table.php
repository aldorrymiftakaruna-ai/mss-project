<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cm_measurements', function (Blueprint $table) {
            // Hapus foreign key lama dulu, lalu ubah jadi nullable
            $table->dropForeign(['measured_by']);
            $table->foreignId('measured_by')->nullable()->nullOnDelete()->change();
            $table->foreign('measured_by')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cm_measurements', function (Blueprint $table) {
            $table->dropForeign(['measured_by']);
            $table->foreignId('measured_by')->nullable(false)->change();
            $table->foreign('measured_by')->references('id')->on('employees')->onDelete('cascade');
        });
    }
};