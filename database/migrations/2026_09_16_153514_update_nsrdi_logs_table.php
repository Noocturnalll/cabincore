<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            // Drop old unused columns first
            $table->dropColumn(['hold_reason_category', 'hold_remarks']);

            // Rename station to act_station
            $table->renameColumn('station', 'act_station');

            // Add new columns
            $table->date('refresh_date')->nullable()->after('dja_id');
            $table->string('work_group')->nullable()->after('refresh_date');
            $table->string('nsrdi_number')->nullable()->after('aircraft_registration');
            $table->string('category')->nullable()->after('description');
            $table->date('report_date')->nullable()->after('category');
            $table->date('due_date')->nullable()->after('report_date');
            $table->string('part_number')->nullable()->after('due_date');
            $table->text('part_description')->nullable()->after('part_number');
            $table->string('defer')->nullable()->after('part_description');
            $table->string('aoc')->nullable()->after('defer');
            $table->string('type')->nullable()->after('aoc');
            $table->string('plan_station')->nullable()->after('type');
            $table->date('plan_date')->nullable()->after('plan_station');
            $table->text('remarks')->nullable()->after('plan_date');
            $table->date('close_date')->nullable()->after('status');
            $table->string('reason_open')->nullable()->after('act_station');
            $table->string('code_open')->nullable()->after('reason_open');
        });
    }

    public function down(): void
    {
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->renameColumn('act_station', 'station');
            
            $table->dropColumn([
                'refresh_date', 'work_group', 'nsrdi_number', 'category', 'report_date',
                'due_date', 'part_number', 'part_description', 'defer', 'aoc', 'type',
                'plan_station', 'plan_date', 'remarks', 'close_date', 'reason_open', 'code_open'
            ]);

            $table->enum('hold_reason_category', ['Awaiting Sparepart', 'Aircraft Re-routed', 'Awaiting Ground Time', 'Manpower Shortage'])->nullable();
            $table->text('hold_remarks')->nullable();
        });
    }
};
