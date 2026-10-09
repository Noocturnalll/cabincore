<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_id', 60)->unique();          // KOE_2026-04-10_Malam
            $table->string('station', 10);
            $table->date('work_date');
            $table->string('shift', 10);
            $table->string('url_5r')->nullable();
            $table->string('url_att')->nullable();
            $table->string('url_brf')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['work_date', 'station']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_entries');
    }
};
