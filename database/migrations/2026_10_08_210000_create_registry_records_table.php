<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_records', function (Blueprint $table) {
            $table->id();
            $table->string('module', 40);
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('due_date')->nullable();
            $table->json('data');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module', 'division_id']);
            $table->index(['module', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_records');
    }
};
