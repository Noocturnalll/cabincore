<?php

namespace App\Console\Commands;

use App\Imports\DailyReportImport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

#[Signature('import:daily-reports {--path=data1 : The folder containing the excel files}')]
#[Description('Import all Daily Report Excel files from a specific folder')]
class ImportDailyReports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');

        $folderPath = base_path($this->option('path'));

        if (! File::exists($folderPath)) {
            $this->error("Folder tidak ditemukan: {$folderPath}");

            return;
        }

        $files = File::files($folderPath);
        $excelFiles = array_filter($files, function ($file) {
            return in_array(strtolower($file->getExtension()), ['xlsx', 'xls', 'csv']);
        });

        if (empty($excelFiles)) {
            $this->info("Tidak ada file Excel di dalam folder {$folderPath}");

            return;
        }

        $this->info('Ditemukan '.count($excelFiles)." file Excel. Memulai proses import...\n");

        $successCount = 0;
        $failCount = 0;

        foreach ($excelFiles as $file) {
            $this->line('Mengimport: '.$file->getFilename().' ...');
            try {
                Excel::import(new DailyReportImport, $file->getPathname());
                $this->info('✓ Sukses mengimport '.$file->getFilename());
                $successCount++;
            } catch (\Exception $e) {
                $this->error('✗ Gagal mengimport '.$file->getFilename());
                $this->error('  Error: '.$e->getMessage());
                $failCount++;
            }
        }

        $this->newLine();
        $this->info('=========================================');
        $this->info('Proses Import Selesai!');
        $this->info("Sukses : {$successCount} file");
        $this->error("Gagal  : {$failCount} file");
        $this->info('=========================================');
    }
}
