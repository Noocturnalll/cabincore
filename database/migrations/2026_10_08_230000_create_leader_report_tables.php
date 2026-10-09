<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $logs = ['wo_logs', 'dmi_logs', 'nsrdi_logs', 'cml_logs'];

    public function up(): void
    {
        Schema::create('leader_report_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->date('report_date');
            $table->string('status', 20)->default('preview'); // preview | applied
            $table->json('stats')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leader_report_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('leader_report_imports')->cascadeOnDelete();
            $table->string('sheet', 60)->nullable();
            $table->string('doc_type', 20)->nullable();   // WO / NSRDI / DMI / CML
            $table->string('doc_no', 60)->nullable();
            $table->string('aircraft_registration', 30)->nullable();
            $table->string('station', 20)->nullable();
            $table->string('plan_station', 20)->nullable();
            $table->string('operator', 60)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->nullable();      // Closed / Open
            $table->string('reason_open')->nullable();
            $table->string('code_open', 60)->nullable();
            $table->date('work_date')->nullable();
            $table->unsignedSmallInteger('man_power')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->decimal('man_hour', 8, 2)->nullable();
            $table->string('outcome', 30)->default('pending');
            $table->string('target_table', 30)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['import_id', 'outcome']);
        });

        foreach ($this->logs as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedSmallInteger('man_power')->nullable();
                if ($table !== 'wo_logs') {
                    $t->decimal('man_hour', 8, 2)->nullable();
                }
                if (in_array($table, ['wo_logs', 'dmi_logs'], true)) {
                    $t->dateTime('start_at')->nullable();
                    $t->dateTime('end_at')->nullable();
                }
                $t->unsignedBigInteger('leader_import_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->logs as $table) {
            $cols = ['man_power', 'leader_import_id'];
            if ($table !== 'wo_logs') {
                $cols[] = 'man_hour';
            }
            if (in_array($table, ['wo_logs', 'dmi_logs'], true)) {
                array_push($cols, 'start_at', 'end_at');
            }
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($cols));
        }
        Schema::dropIfExists('leader_report_rows');
        Schema::dropIfExists('leader_report_imports');
    }
};
