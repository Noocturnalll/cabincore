<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_item_aircraft_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->foreignId('aircraft_type_id')->constrained('ims_aircraft_types')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_item_aircraft_type'); }
};