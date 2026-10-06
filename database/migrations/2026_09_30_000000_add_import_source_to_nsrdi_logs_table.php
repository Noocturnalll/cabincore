<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            // NULL      = data buatan aplikasi (DJA punya dja_id, unplanned manual tanpa dja_id)
            // 'dja'     = hasil import sheet "DJA AOC NSRDIL R01"
            // 'unplanned' = hasil import sheet "UNPLANNED" (DOC TYPE = NSRDI)
            $table->string('import_source', 20)->nullable();
            $table->index(['import_source', 'plan_date']);
        });
    }

    public function down(): void
    {
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->dropIndex(['import_source', 'plan_date']);
            $table->dropColumn('import_source');
        });
    }
};
