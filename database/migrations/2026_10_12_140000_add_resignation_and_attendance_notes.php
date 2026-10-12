<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('resign_date')->nullable()->after('join_date');
            $table->string('resign_reason')->nullable()->after('resign_date');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('notes')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['resign_date', 'resign_reason']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['notes']);
        });
    }
};
