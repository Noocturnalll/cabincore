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
            $table->boolean('is_submitted')->default(false);
        });
        Schema::table('dmi_logs', function (Blueprint $table) {
            $table->boolean('is_submitted')->default(false);
        });
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->boolean('is_submitted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wo_logs', function (Blueprint $table) {
            $table->dropColumn('is_submitted');
        });
        Schema::table('dmi_logs', function (Blueprint $table) {
            $table->dropColumn('is_submitted');
        });
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->dropColumn('is_submitted');
        });
    }
};
