<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_logs', function (Blueprint $table) {
            $table->dropColumn('durasi_menit');
        });
    }

    public function down(): void
    {
        Schema::table('manpower_logs', function (Blueprint $table) {
            $table->integer('durasi_menit')->nullable();
        });
    }
};
