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
        if (Schema::hasTable('wo_logs') && ! Schema::hasColumn('wo_logs', 'refresh_date')) {
            Schema::table('wo_logs', function (Blueprint $table) {
                $table->date('refresh_date')->nullable()->after('date');
            });
        }

        if (Schema::hasTable('dmi_logs') && ! Schema::hasColumn('dmi_logs', 'refresh_date')) {
            Schema::table('dmi_logs', function (Blueprint $table) {
                $table->date('refresh_date')->nullable()->after('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wo_logs') && Schema::hasColumn('wo_logs', 'refresh_date')) {
            Schema::table('wo_logs', function (Blueprint $table) {
                $table->dropColumn('refresh_date');
            });
        }

        if (Schema::hasTable('dmi_logs') && Schema::hasColumn('dmi_logs', 'refresh_date')) {
            Schema::table('dmi_logs', function (Blueprint $table) {
                $table->dropColumn('refresh_date');
            });
        }
    }
};
