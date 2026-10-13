<?php

namespace App\Console\Commands;

use App\Services\Nsrdi\NsrdiExcelImporter;
use Illuminate\Console\Command;

class ImportNsrdiExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nsrdi:import {file : Path to the NSRDI Excel file} {--dry-run : Only inspect and report, change nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import NSRDI workbook (DJA Planned, Unplanned, and Targets) from Excel into the database';

    /**
     * Execute the console command.
     */
    public function handle(NsrdiExcelImporter $importer): int
    {
        @ini_set('memory_limit', '1024M');

        $file = $this->argument('file');
        if (! file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Memulai import file NSRDI: {$file}...");

        try {
            $stats = $importer->import($file, $dryRun);

            $this->newLine();
            $this->info(($dryRun ? '[DRY RUN] ' : '').'Import NSRDI selesai dengan sukses!');
            $this->table(
                ['Kategori / Sheet', 'Total', 'CBM', 'Painting', 'Closed', 'Open'],
                [
                    ['DJA (Planned)', $stats['dja_count'], $stats['dja_cbm'], $stats['dja_painting'], $stats['dja_closed'], $stats['dja_open']],
                    ['Unplanned', $stats['unplanned_count'], $stats['unplanned_cbm'], $stats['unplanned_painting'], $stats['unplanned_closed'], $stats['unplanned_open']],
                    ['TOTAL', $stats['total'], $stats['dja_cbm'] + $stats['unplanned_cbm'], $stats['dja_painting'] + $stats['unplanned_painting'], $stats['dja_closed'] + $stats['unplanned_closed'], $stats['dja_open'] + $stats['unplanned_open']],
                ]
            );

            $this->newLine();
            $this->line('Distribusi Tanggal:');
            foreach ($stats['dates'] as $date => $cnt) {
                $this->line("  - {$date}: {$cnt} record");
            }

            if ($stats['targets_updated'] > 0) {
                $this->newLine();
                $this->info("Capacity targets disinkronisasi: {$stats['targets_updated']} AOC.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Terjadi kesalahan saat import: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
