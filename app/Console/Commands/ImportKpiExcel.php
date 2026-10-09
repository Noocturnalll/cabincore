<?php

namespace App\Console\Commands;

use App\Services\Kpi\KpiExcelImporter;
use Illuminate\Console\Command;

class ImportKpiExcel extends Command
{
    protected $signature = 'kpi:import {type : accuracy | lgt} {file : path to the Excel file} {--dry-run : only report, change nothing}';

    protected $description = 'Imports the KPI Document Accuracy or LGT Monitoring Excel (re-runnable)';

    public function handle(KpiExcelImporter $importer): int
    {
        @ini_set('memory_limit', '2G');   // large workbooks do not fit the default 128 MB

        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');

        try {
            $stats = match ($this->argument('type')) {
                'accuracy' => $importer->importDocumentAccuracy($file, $dry),
                'lgt' => $importer->importLgt($file, $dry),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        if ($stats === null) {
            $this->error('Jenis impor harus accuracy atau lgt.');

            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY RUN] ' : '').'Selesai.');
        foreach ($stats as $key => $value) {
            $this->line("{$key}: {$value}");
        }

        return self::SUCCESS;
    }
}
