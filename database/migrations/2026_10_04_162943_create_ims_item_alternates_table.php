<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_item_alternates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->string('part_number');
            $table->string('relation'); // alternate|interchangeable|superseded_by|supersedes
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['item_id', 'part_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_item_alternates'); }
};