<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sheet_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 10);           // pull | push
            $table->string('kind', 10)->nullable();    // wo | dmi | nsrdi
            $table->string('doc_no')->nullable();
            $table->string('event', 20);               // ok | failed | conflict | healed
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['event', 'resolved_at']);
            $table->index(['kind', 'doc_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheet_sync_logs');
    }
};
