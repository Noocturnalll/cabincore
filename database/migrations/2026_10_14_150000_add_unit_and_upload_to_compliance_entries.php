<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Briefing / attendant list / 5R evidence now belongs to a unit (AIEC from the sheet, Cabin Maintenance,
 * Aircraft Painting, Tambahan) and can be uploaded in the web app, not only mirrored from the sheet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_entries', function (Blueprint $table) {
            $table->string('unit', 12)->default('AIEC')->after('entry_id');
            $table->foreignId('uploaded_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->text('note')->nullable()->after('uploaded_by');
            $table->index(['unit', 'station', 'work_date', 'shift'], 'compliance_unit_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('compliance_entries', function (Blueprint $table) {
            $table->dropIndex('compliance_unit_lookup');
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['unit', 'note']);
        });
    }
};
