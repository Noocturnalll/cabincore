<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('period'); // YYYYMM
            $table->integer('last_number')->default(0);
            $table->timestamps();
            
            $table->unique(['prefix', 'period']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_document_sequences'); }
};