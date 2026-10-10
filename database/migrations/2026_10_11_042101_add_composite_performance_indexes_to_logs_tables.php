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
        Schema::table('wo_logs', function (Blueprint $table) {
            $table->index(['date', 'status'], 'wo_logs_date_status_index');
            $table->index('wo_number', 'wo_logs_wo_number_index');
        });

        Schema::table('dmi_logs', function (Blueprint $table) {
            $table->index(['date', 'status'], 'dmi_logs_date_status_index');
            $table->index('dmi_number', 'dmi_logs_dmi_number_index');
        });

        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->index(['plan_date', 'status'], 'nsrdi_logs_plan_date_status_index');
            $table->index(['report_date', 'status'], 'nsrdi_logs_report_date_status_index');
            $table->index('nsrdi_number', 'nsrdi_logs_nsrdi_number_index');
        });

        Schema::table('cml_logs', function (Blueprint $table) {
            $table->index(['date', 'status'], 'cml_logs_date_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wo_logs', function (Blueprint $table) {
            $table->dropIndex('wo_logs_date_status_index');
            $table->dropIndex('wo_logs_wo_number_index');
        });

        Schema::table('dmi_logs', function (Blueprint $table) {
            $table->dropIndex('dmi_logs_date_status_index');
            $table->dropIndex('dmi_logs_dmi_number_index');
        });

        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->dropIndex('nsrdi_logs_plan_date_status_index');
            $table->dropIndex('nsrdi_logs_report_date_status_index');
            $table->dropIndex('nsrdi_logs_nsrdi_number_index');
        });

        Schema::table('cml_logs', function (Blueprint $table) {
            $table->dropIndex('cml_logs_date_status_index');
        });
    }
};
