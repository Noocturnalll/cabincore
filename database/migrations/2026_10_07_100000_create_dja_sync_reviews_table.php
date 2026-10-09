<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every planner-sheet row that the sync did NOT accept automatically is kept here, so nothing is
     * discarded silently and a person can overrule the classification rules.
     */
    public function up(): void
    {
        Schema::create('dja_sync_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('row_key', 64)->unique();       // md5(tab|task or reg+description)
            $table->string('spreadsheet_id')->nullable();
            $table->string('tab', 40);
            $table->string('kind', 10);                      // wo | dmi | nsrdi
            $table->string('task_id')->nullable();
            $table->string('aircraft_registration')->nullable();
            $table->text('description')->nullable();
            $table->string('ata', 30)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('bucket', 10)->index();           // review | reject (what the rules said)
            $table->string('rule', 40);
            $table->string('reason')->nullable();
            $table->string('decision', 10)->nullable()->index(); // accepted | rejected (human)
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->json('payload');                         // mapped row, used when a person accepts it later
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dja_sync_reviews');
    }
};
