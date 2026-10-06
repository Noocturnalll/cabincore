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
        Schema::create('nsrdi_overdues', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('aircraft_registration')->nullable();
            $table->string('ac_status')->nullable();
            $table->string('nsrdi_number')->nullable();
            $table->string('mddr')->nullable();
            $table->string('status')->default('Open');
            $table->string('area')->nullable();
            $table->date('report_date')->nullable();
            $table->string('month_due')->nullable();
            $table->text('description')->nullable();
            $table->string('status_final')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nsrdi_overdues');
    }
};
