<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How an overdue document was closed (matched to the data bank / Daily Report, the file, or by hand) and when the file last listed it. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nsrdi_overdues', function (Blueprint $table) {
            $table->string('closed_via', 12)->nullable()->after('closed_at');   // bank | daily_report | file | manual
            $table->string('bank_ref', 60)->nullable()->after('closed_via');    // the NSRDI number it matched in the log
            $table->date('last_seen_at')->nullable()->after('bank_ref');        // last import file that listed it
            $table->index('nsrdi_number');
            $table->index('mddr');
        });
    }

    public function down(): void
    {
        Schema::table('nsrdi_overdues', function (Blueprint $table) {
            $table->dropIndex(['nsrdi_number']);
            $table->dropIndex(['mddr']);
            $table->dropColumn(['closed_via', 'bank_ref', 'last_seen_at']);
        });
    }
};
