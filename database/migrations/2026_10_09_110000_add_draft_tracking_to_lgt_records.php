<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who drafted an LGT line (Admin COD) and who filled in the jobs (Finishing / CBM / AIEC). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lgt_records', function (Blueprint $table) {
            $table->foreignId('drafted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('filled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('filled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lgt_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drafted_by');
            $table->dropConstrainedForeignId('filled_by');
            $table->dropColumn('filled_at');
        });
    }
};
