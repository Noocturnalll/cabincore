<?php

namespace App\Console\Commands;

use App\Imports\DailyReportImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-report:import {file : Path to the Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Daily Report from an Excel file manually via CLI';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return Command::FAILURE;
        }

        $this->info("Memulai import file: {$file}...");

        try {
            DB::transaction(function () use ($file) {
                Excel::import(new DailyReportImport, $file);
            });

            $this->info('Import berhasil diselesaikan!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan saat import: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
