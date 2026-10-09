<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One table for the small lookup lists the system is configured from (teams, shifts, attendance rules,
        // asset categories, targets ...). The definition of every type lives in config/master.php.
        Schema::create('master_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('code', 60);
            $table->string('label');
            $table->json('attrs')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'code']);
            $table->index(['type', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_entries');
    }
};
