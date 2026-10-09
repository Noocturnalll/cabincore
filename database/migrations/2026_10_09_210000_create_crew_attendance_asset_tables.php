<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many: a job has several people, a person does many jobs. The pivot carries the man hours each person
        // contributed, which is what makes "who works hard" measurable.
        Schema::create('job_crew', function (Blueprint $table) {
            $table->id();
            $table->string('jobable_type', 30);              // aircraft_cleaning / wo_log / dmi_log / nsrdi_log / cml_log
            $table->unsignedBigInteger('jobable_id');
            $table->string('employee_ref', 40);              // the ID as written in the report (usually the employee number)
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('man_hour', 8, 2)->nullable();   // this person's share of the job
            $table->date('work_date');
            $table->string('station', 10)->nullable();
            $table->timestamps();

            $table->index(['jobable_type', 'jobable_id']);
            $table->index(['employee_ref', 'work_date']);
        });
        Schema::table('leader_report_rows', fn (Blueprint $table) => $table->text('crew')->nullable());

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('employee_nik', 40);
            $table->string('employee_name')->nullable();
            $table->string('station', 10)->nullable();
            $table->date('work_date');
            $table->string('status_code', 12);               // H / S / C / I / A / OFF ... (Data Master: Status Presensi)
            $table->string('status_group', 12);             // hadir / sakit / cuti / izin / alpa / libur / training
            $table->string('scheduled_start', 5)->nullable();
            $table->string('clock_in', 5)->nullable();
            $table->string('clock_out', 5)->nullable();
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->string('source', 12)->default('import');
            $table->timestamps();

            $table->unique(['employee_nik', 'work_date']);
            $table->index(['work_date', 'station']);
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('category', 40);                  // Data Master: Kategori Asset
            $table->string('condition', 40)->default('BAIK'); // Data Master: Kondisi Asset
            $table->string('station', 10)->nullable();       // where it is kept
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('qty_total')->default(1);
            $table->string('unit', 20)->default('unit');
            $table->string('serial_no', 60)->nullable();
            $table->date('acquired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['station', 'category']);
        });

        // Many-to-many between assets and the people / stations holding them, with the history kept
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('holder_type', 10);               // employee / station
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('holder_name')->nullable();       // employee name or station code at the time
            $table->unsignedInteger('qty')->default(1);
            $table->date('assigned_at');
            $table->date('due_back')->nullable();
            $table->date('returned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'returned_at']);
            $table->index(['employee_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('attendance_records');
        Schema::table('leader_report_rows', fn (Blueprint $table) => $table->dropColumn('crew'));
        Schema::dropIfExists('job_crew');
    }
};
