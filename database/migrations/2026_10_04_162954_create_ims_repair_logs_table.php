<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_repair_logs', function (Blueprint $table) {
            $table->id();
            $table->string('repair_code');
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps(); // created_at only is fine, or standard timestamps
        });
    }
    public function down(): void { Schema::dropIfExists('ims_repair_logs'); }
};