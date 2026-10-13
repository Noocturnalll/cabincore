<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_nik', 50)->index();
            $table->string('employee_name');
            $table->string('station', 10)->nullable()->index();
            $table->string('job_title')->nullable();
            $table->string('company', 20)->nullable()->index();
            $table->string('category', 50)->index();
            $table->string('course_name')->index();
            $table->date('course_date')->nullable();
            $table->date('last_recurrent_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('status', 30)->default('valid')->index();
            $table->string('schedule_status', 50)->nullable();
            $table->string('raw_value', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_nik', 'course_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_trainings');
    }
};
