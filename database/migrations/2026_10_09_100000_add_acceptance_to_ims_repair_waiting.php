<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** The repair team accepts (ACC) a faulty part filed by COD before it takes a place on Rak Repair 1. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ims_repair_waiting', function (Blueprint $table) {
            $table->dateTime('accepted_at')->nullable()->after('received_by');
            $table->foreignId('accepted_by')->nullable()->after('accepted_at')->constrained('users');
        });

        // parts already on the rack were physically accepted
        DB::table('ims_repair_waiting')->whereNull('accepted_at')->update(['accepted_at' => DB::raw('received_at')]);
    }

    public function down(): void
    {
        Schema::table('ims_repair_waiting', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_by');
            $table->dropColumn('accepted_at');
        });
    }
};
