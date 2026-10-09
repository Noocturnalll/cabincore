<?php

namespace App\Console\Commands;

use App\Services\Aiec\AiecReportImporter;
use Illuminate\Console\Command;

class ImportAiecReport extends Command
{
    protected $signature = 'aiec:import {file : path to "AIEC Report ....xlsx"} {--dry-run : only report, change nothing}';

    protected $description = 'Imports the AIEC Report workbook: cleaning jobs with their crew (MP 1..n) and start/finish, giving real man hours (re-runnable)';

    public function handle(AiecReportImporter $importer): int
    {
        @ini_set('memory_limit', '2G');

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
        foreach ($stats['sheets'] as $sheet => $n) {
            $this->line("  sheet {$sheet}: {$n} pekerjaan");
        }
        $this->line("tersimpan: {$stats['saved']}   periode: {$stats['from']} .. {$stats['to']}");
        if ($stats['skipped']) {
            $this->warn('Dilewati (jenis tidak dikenali): '.json_encode(array_slice($stats['skipped'], 0, 10), JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
