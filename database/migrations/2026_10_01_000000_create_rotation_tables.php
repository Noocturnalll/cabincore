<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotation_imports', function (Blueprint $t) {
            $t->id();
            $t->date('operation_date')->unique();   // 1 import aktif per tanggal (re-upload = ganti)
            $t->string('source_filename');
            $t->string('sheet_name');
            $t->json('stats')->nullable();
            $t->json('warnings')->nullable();
            $t->timestamps();
        });

        Schema::create('aircraft_rotations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rotation_import_id')->constrained()->cascadeOnDelete();
            $t->string('registration', 10);          // PK-WGR
            $t->string('reg_code', 5);               // WGR
            $t->unsignedSmallInteger('rotation_no')->nullable();
            $t->unsignedSmallInteger('declared_flights')->nullable(); // angka di kolom AN
            $t->string('status', 10)->default('OK'); // OK | MX | AOG
            $t->json('notes')->nullable();           // kolom A (AT500 LOCK, C-CHECK, ...)
            $t->json('remarks')->nullable();         // teks remark di baris registrasi
            $t->index(['rotation_import_id', 'reg_code']);
        });

        Schema::create('rotation_legs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aircraft_rotation_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('seq');
            $t->string('flight_raw', 255);           // 1864P / CHT/2513S / NOTAM remarks
            $t->string('flight_no', 10);             // 1864
            $t->string('flight_suffix', 2)->nullable();
            $t->boolean('is_charter')->default(false);
            $t->string('origin', 3);
            $t->string('destination', 3);
            $t->unsignedBigInteger('dep_ts');        // epoch UTC (bebas masalah timezone app)
            $t->unsignedBigInteger('arr_ts');
            $t->string('dep_local', 5);              // HH:MM lokal stasiun
            $t->string('arr_local', 5);
            $t->tinyInteger('dep_tz');
            $t->tinyInteger('arr_tz');
            $t->boolean('is_revised')->default(false); // memakai jam baris ke-2 (re-ETD)
            $t->json('flags')->nullable();           // ["DU"]
            $t->index(['aircraft_rotation_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotation_legs');
        Schema::dropIfExists('aircraft_rotations');
        Schema::dropIfExists('rotation_imports');
    }
};
