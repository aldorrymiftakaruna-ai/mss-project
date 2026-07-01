<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cm_findings', function (Blueprint $table) {
            $table->string('finding_code')->nullable()->unique()->after('id');
        });

        $findings = DB::table('cm_findings')->orderBy('tanggal')->orderBy('id')->get();
        $counters = [];

        foreach ($findings as $f) {
            $year = Carbon::parse($f->tanggal)->format('Y');
            $counters[$year] = ($counters[$year] ?? 0) + 1;
            $code = sprintf('VIDR-%s-%05d', $year, $counters[$year]);

            DB::table('cm_findings')->where('id', $f->id)->update(['finding_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('cm_findings', function (Blueprint $table) {
            $table->dropColumn('finding_code');
        });
    }
};
