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
        Schema::create('nsrdi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dja_id')->nullable()->constrained('daily_job_assignments')->nullOnDelete();
            $table->string('aircraft_registration')->index();
            $table->string('station')->index()->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['Open', 'Closed', 'Pending'])->default('Open');
            $table->enum('hold_reason_category', ['Awaiting Sparepart', 'Aircraft Re-routed', 'Awaiting Ground Time', 'Manpower Shortage'])->nullable();
            $table->text('hold_remarks')->nullable();
            $table->string('photo_evidence_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nsrdi_logs');
    }
};
