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
        Schema::create('ict_findings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('no_finding');
            $table->string('operator')->nullable();
            $table->string('aircraft_registration');
            $table->text('defect_description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ict_findings');
    }
};
