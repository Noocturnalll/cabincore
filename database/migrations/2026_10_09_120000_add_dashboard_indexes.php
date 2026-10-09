<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Indexes behind the dashboard pivot, charts and "perlu perhatian" counts (date ranges, station, DJA joins, open requests). */
return new class extends Migration
{
    /** table => [index name => columns] */
    private const INDEXES = [
        'aircraft_cleanings' => ['ac_clean_date_station_type' => ['date', 'station', 'type'], 'ac_clean_station_date' => ['station', 'date']],
        'wo_logs' => ['wo_logs_dja_status' => ['dja_id', 'status'], 'wo_logs_date' => ['date']],
        'dmi_logs' => ['dmi_logs_dja_status' => ['dja_id', 'status'], 'dmi_logs_date' => ['date']],
        'nsrdi_logs' => ['nsrdi_logs_dja_status' => ['dja_id', 'status'], 'nsrdi_logs_refresh_date' => ['refresh_date']],
        'cml_logs' => ['cml_logs_date_station' => ['date', 'station']],
        'ims_transactions' => ['ims_tx_status_type' => ['status', 'type']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $name)) {
                    continue;
                }
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
