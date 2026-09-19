<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_logs', function (Blueprint $table) {
            // Drop old unused columns first
            $table->dropColumn(['hold_reason_category', 'hold_remarks']);

            // Rename station to act_station
            $table->renameColumn('station', 'act_station');

            // Add new columns
            $table->date('date')->nullable()->after('dja_id');
            $table->string('work_group')->nullable()->after('date');
            $table->string('wo_number')->nullable()->after('aircraft_registration');
            $table->string('wo_category')->nullable()->after('wo_number');
            $table->string('pn_picklist')->nullable()->after('description');
            $table->decimal('man_hour', 8, 2)->nullable()->after('pn_picklist');
            $table->string('operator')->nullable()->after('man_hour');
            $table->string('type')->nullable()->after('operator');
            $table->string('plan_station')->nullable()->after('type');
            $table->text('remarks_ppc_to_lm')->nullable()->after('plan_station');
            $table->string('reason_open')->nullable()->after('status');
            $table->string('code_open')->nullable()->after('reason_open');
        });
    }

    public function down(): void
    {
        Schema::table('wo_logs', function (Blueprint $table) {
            $table->renameColumn('act_station', 'station');
            
            $table->dropColumn([
                'date', 'work_group', 'wo_number', 'wo_category',
                'pn_picklist', 'man_hour', 'operator', 'type',
                'plan_station', 'remarks_ppc_to_lm', 'reason_open', 'code_open'
            ]);

            $table->enum('hold_reason_category', ['Awaiting Sparepart', 'Aircraft Re-routed', 'Awaiting Ground Time', 'Manpower Shortage'])->nullable();
            $table->text('hold_remarks')->nullable();
        });
    }
};
