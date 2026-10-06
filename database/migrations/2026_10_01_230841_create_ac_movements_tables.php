<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Terminal Movements (Untuk Terminal 1, Terminal 2, dst)
        Schema::create('terminal_movements', function (Blueprint $table) {
            $table->id();
            $table->string('terminal_name'); // 'Terminal 1', 'Terminal 2'
            $table->date('flight_date')->nullable();
            $table->integer('no_seq')->nullable();
            $table->string('registration')->nullable();
            $table->string('flight_no_in')->nullable();
            $table->string('sta')->nullable();
            $table->string('eta')->nullable();
            $table->string('plan_ps')->nullable();
            $table->string('flight_no_out')->nullable();
            $table->string('std')->nullable();
            $table->string('atd')->nullable();
            $table->string('engineer_handle')->nullable();
            $table->string('input_afml')->nullable();
            $table->string('actual_registration')->nullable();
            $table->string('tear_off_afml_pink')->nullable();
            $table->timestamps();
        });

        // 2. Tabel AC RON
        Schema::create('ac_rons', function (Blueprint $table) {
            $table->id();
            $table->date('ron_date')->nullable();
            $table->integer('no_seq')->nullable();
            $table->string('reg_flt')->nullable();
            $table->string('ex_flt')->nullable();
            $table->string('sta_ata')->nullable();
            $table->string('stand')->nullable();
            $table->string('flt_no')->nullable();
            $table->string('route')->nullable();
            $table->string('std')->nullable();
            $table->text('remarks')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 3. Tabel AC STBY
        Schema::create('ac_standbies', function (Blueprint $table) {
            $table->id();
            $table->string('airline_category'); // Lion Air, Super Air Jet, Batik Air, A330
            $table->integer('no_seq')->nullable();
            $table->string('reg_flt')->nullable();
            $table->string('parking')->nullable();
            $table->string('plan_rts')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_movements');
        Schema::dropIfExists('ac_rons');
        Schema::dropIfExists('ac_standbies');
    }
};
