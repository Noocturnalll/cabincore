<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->unique();
            $table->string('name');
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->date('join_date')->nullable();
            $table->string('status', 20)->default('Aktif');
            $table->string('contract_type', 20)->default('PKWT'); // PKWT (fixed term) / PKWTT (permanent)
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('passport_no', 50)->nullable();
            $table->date('passport_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
