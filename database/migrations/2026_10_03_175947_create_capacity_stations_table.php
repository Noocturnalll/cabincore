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
        Schema::create('capacity_stations', function (Blueprint $table) {
            $table->id();
            $table->integer('order_no')->nullable();
            $table->string('kh_region');
            $table->string('group_type')->nullable();
            $table->string('station_code');
            $table->string('code_store')->nullable();
            $table->string('working_hours')->nullable();
            $table->integer('tech_day')->nullable();
            $table->integer('tech_night')->nullable();
            $table->integer('ron_jt')->default(0);
            $table->integer('ron_iw')->default(0);
            $table->integer('ron_id')->default(0);
            $table->integer('ron_iu')->default(0);
            $table->integer('ron_sl')->default(0);
            $table->integer('ron_od')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capacity_stations');
    }
};
