<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master of Air Operator Certificates (airlines). The AOC of an aircraft comes from this master, never from the
     * registration prefix (PK-L.. is shared by Lion Air and Batik Air).
     */
    public function up(): void
    {
        Schema::create('aocs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();          // JT, ID, IU, IW, OD, SL
            $table->string('name');                        // Lion Air
            $table->json('aliases')->nullable();           // spellings seen in sheets: "LION AIR", "THAI LION AIR"...
            $table->boolean('include_in_report')->default(true); // false = kept as data, left out of the audit reports
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aocs');
    }
};
