<?php

namespace App\Console\Commands;

use App\Services\Hr\HrExcelImporter;
use Illuminate\Console\Command;

class ImportHrExcel extends Command
{
    protected $signature = 'hr:import {type : employees | pasban} {file : path to the Excel file} {--dry-run : only report, change nothing}';

    protected $description = 'Imports the employee database or the airport-pass form responses from Excel (re-runnable)';

    public function handle(HrExcelImporter $importer): int
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
                'employees' => $importer->importEmployees($file, $dry),
                'pasban' => $importer->importPasban($file, $dry),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        if ($stats === null) {
            $this->error('Jenis impor harus employees atau pasban.');

            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY RUN] ' : '').'Selesai.');
        foreach ($stats as $key => $value) {
            if (is_array($value)) {
                $this->line("{$key}:");
                foreach (array_slice($value, 0, 15, true) as $k => $v) {
                    $this->line("   {$v}  {$k}");
                }
            } else {
                $this->line("{$key}: {$value}");
            }
        }

        return self::SUCCESS;
    }
}
