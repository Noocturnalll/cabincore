<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('ims_transactions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->foreignId('location_id')->nullable()->constrained('ims_locations');
            $table->foreignId('to_location_id')->nullable()->constrained('ims_locations');
            $table->unsignedInteger('qty');
            $table->string('condition')->nullable();
            $table->string('batch_no')->nullable();
            $table->integer('system_qty')->nullable();
            $table->integer('actual_qty')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('returned_qty')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_transaction_items'); }
};