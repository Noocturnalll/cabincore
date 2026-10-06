<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->string('serial_number');
            $table->string('batch_no')->nullable();
            $table->string('condition');
            $table->string('status'); // in_stock|reserved|issued|in_repair|scrapped
            $table->foreignId('location_id')->nullable()->constrained('ims_locations')->nullOnDelete();
            $table->decimal('tsn_hours', 10, 2)->nullable();
            $table->integer('csn_cycles')->nullable();
            $table->decimal('tso_hours', 10, 2)->nullable();
            $table->integer('cso_cycles')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('certificate_no')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['item_id', 'serial_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_item_serials'); }
};