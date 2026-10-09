<?php

namespace App\Console\Commands;

use App\Services\Attendance\AttendanceImporter;
use Illuminate\Console\Command;

class ImportAttendance extends Command
{
    protected $signature = 'attendance:import {file : presensi Excel (daftar atau matriks)} {--dry-run : only report, change nothing}';

    protected $description = 'Imports a month of attendance (presensi) so discipline can be calculated per month, quarter and year';

    public function handle(AttendanceImporter $importer): int
    {
        @ini_set('memory_limit', '2G');

        if (! is_file($this->argument('file'))) {
            $this->error('File tidak ditemukan: '.$this->argument('file'));

            return self::FAILURE;
        }

        try {
            $stats = $importer->import($this->argument('file'), (bool) $this->option('dry-run'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? '[DRY RUN] ' : '').'Selesai.');
        foreach (['read', 'saved', 'skipped', 'late', 'from', 'to'] as $k) {
            $this->line("{$k}: {$stats[$k]}");
        }
        if ($stats['unknown_status']) {
            $this->warn('Status tidak dikenali: '.json_encode(array_slice($stats['unknown_status'], 0, 10), JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
