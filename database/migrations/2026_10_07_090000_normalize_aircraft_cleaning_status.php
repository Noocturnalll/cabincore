<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The manual cleaning forms used to save "Aktif" / "Selesai", while the dashboard,
     * reports and Excel import all count "Open" / "Closed". Align the stored values.
     */
    public function up(): void
    {
        DB::table('aircraft_cleanings')->where('status', 'Aktif')->update(['status' => 'Open']);
        DB::table('aircraft_cleanings')->where('status', 'Selesai')->update(['status' => 'Closed']);
    }

    public function down(): void
    {
        // Irreversible on purpose: original labels cannot be told apart from imported Open/Closed rows.
    }
};
