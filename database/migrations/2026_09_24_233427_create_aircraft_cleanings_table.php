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
        Schema::create('aircraft_cleanings', function (Blueprint $table) {
            $table->id();
            $table->string('aircraft_registration');
            $table->date('date');
            $table->string('shift');
            $table->string('type'); // General, DCI, DCE
            $table->string('status')->default('Aktif');
            $table->text('remarks')->nullable();
            $table->string('operator')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aircraft_cleanings');
    }
};
