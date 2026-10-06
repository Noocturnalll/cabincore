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
        Schema::create('sync_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('spreadsheet_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_status')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_settings');
    }
};
