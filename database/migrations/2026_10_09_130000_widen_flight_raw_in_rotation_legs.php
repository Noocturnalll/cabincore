<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rotation_legs', function (Blueprint $table) {
            $table->string('flight_raw', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rotation_legs', function (Blueprint $table) {
            $table->string('flight_raw', 20)->change();
        });
    }
};
