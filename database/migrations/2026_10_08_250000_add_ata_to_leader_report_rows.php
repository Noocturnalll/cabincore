<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leader_report_rows', fn (Blueprint $table) => $table->string('ata', 20)->nullable());
    }

    public function down(): void
    {
        Schema::table('leader_report_rows', fn (Blueprint $table) => $table->dropColumn('ata'));
    }
};
