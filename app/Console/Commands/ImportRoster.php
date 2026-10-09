<?php

namespace App\Console\Commands;

use App\Services\Roster\RosterImporter;
use Illuminate\Console\Command;

class ImportRoster extends Command
{
    protected $signature = 'roster:import {file : path to the roster workbook} {--dry-run : only report, change nothing}';

    protected $description = 'Imports the monthly roster workbook (one sheet per station, shift codes per date). Re-runnable.';

    public function handle(RosterImporter $importer): int
    {
        @ini_set('memory_limit', '2G');   // large workbooks do not fit the default 128 MB

        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        try {
            $stats = $importer->import($file, (bool) $this->option('dry-run'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? '[DRY RUN] ' : '').'Selesai.');
        foreach (['stations', 'entries', 'duplicates', 'from', 'to'] as $k) {
            $this->line("{$k}: {$stats[$k]}");
        }
        if ($stats['unknown_codes']) {
            $this->warn('Kode shift tidak dikenali: '.json_encode(array_slice($stats['unknown_codes'], 0, 15)));
        }

        return self::SUCCESS;
    }
}
