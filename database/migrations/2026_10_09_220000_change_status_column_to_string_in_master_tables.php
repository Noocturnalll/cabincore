<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->string('status', 20)->default('Aktif')->change();
        });

        Schema::table('aircrafts', function (Blueprint $table) {
            $table->string('status', 20)->default('Aktif')->change();
        });
    }

    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->boolean('status')->default(true)->change();
        });

        Schema::table('aircrafts', function (Blueprint $table) {
            $table->boolean('status')->default(true)->change();
        });
    }
};
