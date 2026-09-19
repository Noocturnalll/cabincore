<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cml_logs', function (Blueprint $table) {
            $table->date('date')->nullable();
            $table->string('operator')->nullable();
            $table->string('ac_status')->nullable();
            $table->string('doc_type')->nullable();
            $table->string('no_doc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cml_logs', function (Blueprint $table) {
            $table->dropColumn(['date', 'operator', 'ac_status', 'doc_type', 'no_doc']);
        });
    }
};
