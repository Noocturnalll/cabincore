<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_items', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->text('description');
            $table->foreignId('category_id')->constrained('ims_categories');
            $table->foreignId('unit_id')->constrained('ims_units');
            $table->string('manufacturer')->nullable();
            $table->string('ata_chapter')->nullable();
            $table->string('tracking_type')->default('quantity');
            $table->boolean('is_rotable')->default(false);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->foreignId('default_location_id')->nullable()->constrained('ims_locations');
            $table->integer('shelf_life_days')->nullable();
            $table->boolean('is_hazmat')->default(false);
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('part_number');
        });
    }
    public function down(): void { Schema::dropIfExists('ims_items'); }
};