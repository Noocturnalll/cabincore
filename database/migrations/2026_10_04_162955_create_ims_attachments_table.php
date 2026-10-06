<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->integer('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_attachments'); }
};