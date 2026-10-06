<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('ims_locations')->cascadeOnDelete();
            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->unsignedInteger('qty_reserved')->default(0);
            $table->timestamps();
            
            $table->unique(['item_id', 'location_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_stocks'); }
};