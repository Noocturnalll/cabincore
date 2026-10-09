<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('station', 10)->nullable()->index();
            $table->string('job_title')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('directorate', 20)->nullable();
            $table->string('supervisor_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn(['station', 'job_title', 'gender', 'directorate', 'supervisor_name']));
    }
};
