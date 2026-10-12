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
        Schema::create('stretchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('aircraft_type', 30)->nullable(); // e.g. B737, A320, A330
            $table->string('part_number', 50)->nullable();
            $table->string('serial_number', 50)->nullable();
            $table->string('current_station', 10)->default('CGK'); // Current Point / Location
            $table->string('status', 20)->default('AVAILABLE');    // AVAILABLE, IN_USE, MAINTENANCE
            $table->string('condition', 20)->default('GOOD');       // GOOD, FAIR, DAMAGED
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['current_station', 'status']);
        });

        Schema::create('stretcher_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stretcher_id')->constrained('stretchers')->cascadeOnDelete();
            $table->date('date');
            $table->string('aircraft_registration', 20); // e.g. PK-LGF
            $table->string('flight_number', 30);         // e.g. JT-380
            $table->string('origin_station', 10);        // Titik Asal
            $table->string('destination_station', 10);   // End Point / Titik Akhir
            $table->string('route', 30);                 // e.g. CGK - KNO
            $table->string('passenger_name');            // Siapa yang naik / Nama Pasien
            $table->string('medical_notes')->nullable(); // Kebutuhan khusus / dokter pendamping
            $table->string('status', 20)->default('COMPLETED'); // COMPLETED, IN_TRANSIT, CANCELLED
            $table->string('pic_name')->nullable();      // Teknisi / Petugas PIC
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stretcher_id', 'date']);
            $table->index(['aircraft_registration', 'date']);
            $table->index(['destination_station', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stretcher_transactions');
        Schema::dropIfExists('stretchers');
    }
};
