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
        Schema::create('daily_job_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('aircraft_registration')->index();
            $table->date('date')->index();
            $table->string('task_id')->index();
            $table->enum('job_type', ['R01/WO', 'AOC/NSRDI', 'DMI'])->default('R01/WO');
            $table->text('description')->nullable();
            $table->string('station')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_job_assignments');
    }
};
