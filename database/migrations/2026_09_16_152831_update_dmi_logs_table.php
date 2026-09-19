<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dmi_logs', function (Blueprint $table) {
            // Drop old unused columns first
            $table->dropColumn(['hold_reason_category', 'hold_remarks']);

            // Rename station to act_station
            $table->renameColumn('station', 'act_station');

            // Add new columns
            $table->date('date')->nullable()->after('dja_id');
            $table->string('pn_required')->nullable()->after('description');
            $table->string('dmi_number')->nullable()->after('pn_required');
            $table->string('dmi_category')->nullable()->after('dmi_number');
            $table->string('plan_station')->nullable()->after('dmi_category');
            $table->string('category')->nullable()->after('plan_station');
            $table->text('remarks')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('dmi_logs', function (Blueprint $table) {
            $table->renameColumn('act_station', 'station');
            
            $table->dropColumn([
                'date', 'pn_required', 'dmi_number', 'dmi_category',
                'plan_station', 'category', 'remarks'
            ]);

            $table->enum('hold_reason_category', ['Awaiting Sparepart', 'Aircraft Re-routed', 'Awaiting Ground Time', 'Manpower Shortage'])->nullable();
            $table->text('hold_remarks')->nullable();
        });
    }
};
