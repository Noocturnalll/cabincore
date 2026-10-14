<?php

use App\Support\CmlKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** CML or DBI (Daily Beautification Inspection): DBI is Cabin Maintenance and gets its own tab. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cml_logs', function (Blueprint $table) {
            $table->string('kind', 8)->default('CML')->after('doc_type');
            $table->index(['kind', 'date']);
        });

        CmlKind::sync();
    }

    public function down(): void
    {
        Schema::table('cml_logs', function (Blueprint $table) {
            $table->dropIndex(['kind', 'date']);
            $table->dropColumn('kind');
        });
    }
};
