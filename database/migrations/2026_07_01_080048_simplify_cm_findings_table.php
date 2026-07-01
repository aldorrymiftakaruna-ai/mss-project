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
    Schema::table('cm_findings', function (Blueprint $table) {
        foreach (['cm_findings_reported_by_foreign', 'cm_findings_pic_id_foreign'] as $fk) {
            try {
                $table->dropForeign($fk);
            } catch (\Throwable $e) {
                //
            }
        }
    });

    Schema::table('cm_findings', function (Blueprint $table) {
        foreach (['deskripsi', 'reported_by', 'pic_id'] as $col) {
            if (Schema::hasColumn('cm_findings', $col)) {
                $table->dropColumn($col);
            }
        }
    });

    if (!Schema::hasColumn('cm_findings', 'pic')) {
        Schema::table('cm_findings', function (Blueprint $table) {
            $table->string('pic')->nullable()->after('action');
        });
    }

    DB::statement("ALTER TABLE cm_findings MODIFY severity ENUM('low','medium','high') NULL");
    DB::statement("UPDATE cm_findings SET status = 'closed' WHERE status IN ('acknowledged','resolved')");
    DB::statement("ALTER TABLE cm_findings MODIFY status ENUM('open','closed') NOT NULL DEFAULT 'open'");
}
    public function down(): void
{
    Schema::table('cm_findings', function (Blueprint $table) {
        $table->text('deskripsi')->nullable();
        $table->unsignedBigInteger('reported_by')->nullable();
        $table->unsignedBigInteger('pic_id')->nullable();
        $table->dropColumn('pic');
    });

    DB::statement("ALTER TABLE cm_findings MODIFY severity ENUM('low','medium','high') NOT NULL");
    DB::statement("ALTER TABLE cm_findings MODIFY status ENUM('open','acknowledged','resolved') NOT NULL DEFAULT 'open'");
}
};
