<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_repair_completed', function (Blueprint $table) {
            $table->id();
            $table->string('repair_code')->index();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->integer('qty')->default(1);
            $table->text('fault_description');
            $table->string('priority');
            $table->foreignId('origin_transaction_id')->nullable()->constrained('ims_transactions');
            $table->foreignId('location_id')->constrained('ims_locations');
            $table->dateTime('completed_at');
            $table->string('result');
            $table->text('findings')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('certificate_no')->nullable();
            $table->string('repaired_by_name')->nullable();
            $table->dateTime('returned_to_stock_at')->nullable();
            $table->foreignId('return_transaction_id')->nullable()->constrained('ims_transactions');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_repair_completed'); }
};