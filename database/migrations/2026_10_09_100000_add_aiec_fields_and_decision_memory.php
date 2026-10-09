<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AIEC cleaning jobs carry the people who did them (employee IDs), so man hours are real, not estimated
        Schema::table('aircraft_cleanings', function (Blueprint $table) {
            $table->unsignedSmallInteger('man_power')->nullable();
            $table->decimal('man_hour', 8, 2)->nullable();
            $table->string('mp_ids')->nullable();          // "250409, 83117667, ..." in the order they were listed
            $table->string('ac_status', 20)->nullable();   // TRANSIT / RON / LGT
            $table->string('task_type', 20)->nullable();   // SCHEDULE / UNSCHEDULE
            $table->string('ref_no', 40)->nullable();      // flight / document number of the job
            $table->string('import_source', 20)->nullable();
        });

        // What a person decided about a WO / DMI, remembered so the same job is recognised next time
        Schema::create('classification_memories', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10);                    // wo / dmi
            $table->string('signature', 120);              // task card code, else the normalised description
            $table->string('signature_type', 10);          // card / text
            $table->string('category', 10);                // CBM / LINE
            $table->unsignedInteger('hits')->default(1);   // times a person confirmed it
            $table->string('example')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kind', 'signature', 'category']);
            $table->index(['kind', 'signature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_memories');
        Schema::table('aircraft_cleanings', fn (Blueprint $table) => $table->dropColumn(['man_power', 'man_hour', 'mp_ids', 'ac_status', 'task_type', 'ref_no', 'import_source']));
    }
};
