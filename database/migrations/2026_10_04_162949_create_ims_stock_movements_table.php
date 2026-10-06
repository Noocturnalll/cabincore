<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('location_id')->constrained('ims_locations');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->string('movement_type'); 
            $table->integer('qty_change');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->foreignId('transaction_id')->nullable()->constrained('ims_transactions');
            $table->string('repair_code')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['item_id', 'created_at']);
            $table->index(['location_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_stock_movements'); }
};