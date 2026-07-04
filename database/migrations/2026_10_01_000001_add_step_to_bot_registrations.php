<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_registrations', function (Blueprint $table) {
            $table->string('step', 30)->nullable()->after('nik')->comment('Langkah registrasi: waiting_name, waiting_jabatan, pending');
            $table->string('requested_jabatan', 50)->nullable()->after('step');
        });
    }

    public function down(): void
    {
        Schema::table('bot_registrations', function (Blueprint $table) {
            $table->dropColumn(['step', 'requested_jabatan']);
        });
    }
};
