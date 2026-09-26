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
        Schema::table('daily_job_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_job_assignments', 'source_spreadsheet_id')) {
                $table->string('source_spreadsheet_id')->nullable()->after('id');
            }
        });

        $tables = ['wo_logs', 'cml_logs', 'dmi_logs', 'nsrdi_logs'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'hold_reason_category')) {
                    $table->string('hold_reason_category')->nullable();
                }
                if (! Schema::hasColumn($tableName, 'hold_remarks')) {
                    $table->text('hold_remarks')->nullable();
                }
                if (! Schema::hasColumn($tableName, 'evidence_path')) {
                    $table->string('evidence_path')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('daily_job_assignments', function (Blueprint $table) {
            $table->dropColumn('source_spreadsheet_id');
        });

        $tables = ['wo_logs', 'cml_logs', 'dmi_logs', 'nsrdi_logs'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['hold_reason_category', 'hold_remarks', 'evidence_path']);
            });
        }
    }
};
