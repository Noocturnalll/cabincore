<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->integer('level')->default(1);
            $table->string('required_role')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->string('status'); // pending|approved|rejected|skipped
            $table->text('note')->nullable();
            $table->dateTime('acted_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_approvals'); }
};