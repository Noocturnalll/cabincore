<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CML / NSRDI documents reported by the stations vs recorded in eMRO, per station and day
        Schema::create('document_accuracy', function (Blueprint $table) {
            $table->id();
            $table->date('work_date');
            $table->string('station', 10);
            $table->unsignedInteger('cml_reported')->default(0);
            $table->unsignedInteger('nsrdi_reported')->default(0);
            $table->unsignedInteger('cml_missed')->default(0);
            $table->unsignedInteger('nsrdi_missed')->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['work_date', 'station']);
        });

        // Long ground time: one row per task done during a long ground time (an aircraft can have several), with the CBM and AIEC result
        Schema::create('lgt_records', function (Blueprint $table) {
            $table->id();
            $table->date('work_date');
            $table->string('station', 10);
            $table->string('aircraft_registration', 20);
            $table->string('aoc', 40)->nullable();
            $table->string('sta_time', 5)->nullable();
            $table->string('std_time', 5)->nullable();
            $table->string('cbm_action')->nullable();
            $table->string('cbm_mp')->nullable();
            $table->string('cbm_status', 12)->nullable();     // CLOSED / CANCEL / OPEN
            $table->string('aiec_action')->nullable();
            $table->string('aiec_mp')->nullable();
            $table->string('aiec_status', 12)->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['work_date', 'station']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgt_records');
        Schema::dropIfExists('document_accuracy');
    }
};
