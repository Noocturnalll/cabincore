<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Aircraft master gets the fields the audit pivots group by: AOC, work group and fleet. */
    public function up(): void
    {
        Schema::table('aircrafts', function (Blueprint $table) {
            $table->foreignId('aoc_id')->nullable()->after('maskapai')->constrained('aocs')->nullOnDelete();
            $table->string('wg', 20)->nullable()->after('aoc_id')->index();       // work group, e.g. "WG 03" (filled in later)
            $table->string('fleet', 20)->nullable()->after('wg')->index();         // B737, A320, A330, ATR72, ...
            $table->string('variant', 30)->nullable()->after('fleet');             // 800NG, MAX 8, 9ER, NEO, ...
            $table->string('type_raw')->nullable()->after('variant');              // the type exactly as written in the source list
        });
    }

    public function down(): void
    {
        Schema::table('aircrafts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aoc_id');
            $table->dropColumn(['wg', 'fleet', 'variant', 'type_raw']);
        });
    }
};
