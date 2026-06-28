<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('label');        // label tampil di halaman settings
            $table->string('unit')->nullable(); // mm/s, °C, kW, dll
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Seed default values
        $now = now();
        DB::table('settings')->insert([
            // ── Vibration thresholds by motor class ──────────────
            ['key' => 'vib_class1_alarm',  'value' => '2.3',  'label' => 'Alarm Vibrasi — Motor ≤15 kW',   'unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class1_danger', 'value' => '4.5',  'label' => 'Danger Vibrasi — Motor ≤15 kW',  'unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class2_alarm',  'value' => '4.5',  'label' => 'Alarm Vibrasi — Motor 15–75 kW', 'unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class2_danger', 'value' => '7.1',  'label' => 'Danger Vibrasi — Motor 15–75 kW','unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class3_alarm',  'value' => '7.1',  'label' => 'Alarm Vibrasi — Motor 75–300 kW','unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class3_danger', 'value' => '11.0', 'label' => 'Danger Vibrasi — Motor 75–300 kW','unit' => 'mm/s','group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class4_alarm',  'value' => '11.0', 'label' => 'Alarm Vibrasi — Motor >300 kW',  'unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vib_class4_danger', 'value' => '18.0', 'label' => 'Danger Vibrasi — Motor >300 kW', 'unit' => 'mm/s', 'group' => 'vibration', 'created_at' => $now, 'updated_at' => $now],
            // ── Temperature threshold ─────────────────────────────
            ['key' => 'temp_danger',       'value' => '82',   'label' => 'Danger Temperature Bearing',      'unit' => '°C',   'group' => 'temperature', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
