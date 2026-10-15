<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: the CML sheet carries A/C type, shift, task type, perform times and up to five technicians
 * (MP 1-5). Total MP and Actual MHRS reuse the existing man_power / man_hour columns.
 */
return new class extends Migration
{
    private const COLUMNS = ['ac_type', 'shift', 'task_type', 'start_perform', 'finish_perform', 'mp1', 'mp2', 'mp3', 'mp4', 'mp5'];

    public function up(): void
    {
        Schema::table('cml_logs', function (Blueprint $table) {
            foreach (self::COLUMNS as $col) {
                if (! Schema::hasColumn('cml_logs', $col)) {
                    $table->string($col)->nullable();
                }
            }
            if (! Schema::hasColumn('cml_logs', 'individual_mhrs')) {
                $table->decimal('individual_mhrs', 8, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        $drop = array_values(array_filter([...self::COLUMNS, 'individual_mhrs'], fn ($c) => Schema::hasColumn('cml_logs', $c)));
        if ($drop) {
            Schema::table('cml_logs', fn (Blueprint $table) => $table->dropColumn($drop));
        }
    }
};
