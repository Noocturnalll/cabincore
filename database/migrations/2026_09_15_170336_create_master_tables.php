<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique(); // e.g. CGK, DPS, SUB
            $table->string('nama');
            $table->string('kota');
            $table->boolean('status')->default(true); // true = Active, false = Inactive
            $table->timestamps();
        });

        Schema::create('aircrafts', function (Blueprint $table) {
            $table->id();
            $table->string('registration', 20)->unique(); // e.g. PK-LGP
            $table->string('tipe'); // e.g. B737-800
            $table->string('maskapai');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->string('divisi'); // Cabin, AIC, Painting, Supporting
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_categories');
        Schema::dropIfExists('aircrafts');
        Schema::dropIfExists('airports');
    }
};
