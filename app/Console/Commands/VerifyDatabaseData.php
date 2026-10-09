<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyDatabaseData extends Command
{
    protected $signature = 'db:verify-counts';

    protected $description = 'Verifikasi dan tampilkan rekap jumlah baris data di semua tabel database';

    public function handle(): int
    {
        $this->info('Menghitung data di database ('.config('database.default').")...\n");

        $driver = DB::getDriverName();
        $tables = [];

        if ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'telescope_%' AND name NOT LIKE 'migrations' AND name NOT LIKE 'sessions' AND name NOT LIKE 'cache%' ORDER BY name");
            $tables = array_map(fn ($t) => $t->name, $tables);
        } else {
            $dbName = DB::getDatabaseName();
            $tables = DB::select("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = ? AND table_name NOT LIKE 'telescope_%' AND table_name NOT LIKE 'migrations' AND table_name NOT LIKE 'sessions' AND table_name NOT LIKE 'cache%' ORDER BY table_name", [$dbName]);
            $tables = array_map(fn ($t) => $t->name, $tables);
        }

        $rows = [];
        $totalRows = 0;

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $rows[] = [$table, number_format($count)];
                    $totalRows += $count;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        usort($rows, function ($a, $b) {
            $valA = (int) str_replace(',', '', $a[1]);
            $valB = (int) str_replace(',', '', $b[1]);

            return $valB <=> $valA;
        });

        $this->table(['Nama Tabel', 'Jumlah Baris Data'], $rows);
        $this->info("\nTOTAL KESELURUHAN: ".number_format($totalRows)." baris data berhasil diverifikasi!\n");

        return self::SUCCESS;
    }
}
