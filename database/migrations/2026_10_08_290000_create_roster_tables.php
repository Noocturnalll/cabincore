<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('shift', 10)->nullable();        // PAGI / SIANG / MALAM, null = not working (OFF, sick, leave, training)
            $table->string('time_range', 20)->nullable();   // 08:00-17:00
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('roster_entries', function (Blueprint $table) {
            $table->id();
            $table->date('work_date');
            $table->string('station', 10);
            $table->string('employee_id', 30);
            $table->string('employee_name')->nullable();
            $table->string('position')->nullable();
            $table->string('team', 20)->nullable();         // CBM / AIEC / PAINTING / IRREG / PI / COD
            $table->string('shift_code', 12);
            $table->string('shift', 10)->nullable();
            $table->timestamps();

            $table->unique(['work_date', 'station', 'employee_id']);
            $table->index(['work_date', 'station', 'team', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_entries');
        Schema::dropIfExists('shift_codes');
    }
};
