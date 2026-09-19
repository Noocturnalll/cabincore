<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aircraft_id')->constrained('aircrafts')->onDelete('cascade');
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('job_categories')->onDelete('cascade');
            $table->foreignId('teknisi_id')->constrained('users')->onDelete('cascade');
            $table->string('status'); // e.g. Pending, Selesai, Hold
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('tools_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('stok')->default(0);
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->string('kondisi'); // e.g. Good, Repair
            $table->timestamps();
        });

        Schema::create('shift_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->string('divisi');
            $table->date('tanggal_shift');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_recaps');
        Schema::dropIfExists('tools_equipment');
        Schema::dropIfExists('maintenance_reports');
    }
};
