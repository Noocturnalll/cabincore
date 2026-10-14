<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Aircraft that have work in the logs but are not in the aircraft master (so no AOC / fleet / WG), and master aircraft
 * without a WG. Writes two CSV files to fill in; nothing is created or guessed here.
 */
class MissingAircraft extends Command
{
    protected $signature = 'master:missing-aircraft {--dir= : Folder for the CSV files (default storage/app/exports)}';

    protected $description = 'List aircraft used in the logs but missing from the master, and master aircraft without a WG';

    /** table => label */
    private const SOURCES = ['wo_logs' => 'WO', 'dmi_logs' => 'DMI', 'nsrdi_logs' => 'NSRDI', 'cml_logs' => 'CML', 'aircraft_cleanings' => 'Cleaning', 'lgt_records' => 'LGT'];

    public function handle(): int
    {
        $dir = $this->option('dir') ?: storage_path('app/exports');
        File::ensureDirectoryExists($dir);

        $known = DB::table('aircrafts')->pluck('registration')->map(fn ($r) => strtoupper(trim($r)))->flip();

        $missing = [];
        foreach (self::SOURCES as $table => $label) {
            $rows = DB::table($table)->whereNotNull('aircraft_registration')->where('aircraft_registration', '!=', '')
                ->select(DB::raw('UPPER(TRIM(aircraft_registration)) as reg'), DB::raw('COUNT(*) as n'))->groupBy('reg')->get();
            foreach ($rows as $r) {
                if (isset($known[$r->reg])) {
                    continue;
                }
                $missing[$r->reg]['count'] = ($missing[$r->reg]['count'] ?? 0) + $r->n;
                $missing[$r->reg]['sources'][$label] = $r->n;
            }
        }
        uasort($missing, fn ($a, $b) => $b['count'] <=> $a['count']);

        $stamp = now()->format('Ymd');
        $file = "{$dir}/aircraft-missing-from-master-{$stamp}.csv";
        $fh = fopen($file, 'w');
        fputcsv($fh, ['registration', 'records', 'used_in', 'maskapai (isi)', 'tipe (isi)', 'wg (isi)']);
        foreach ($missing as $reg => $m) {
            fputcsv($fh, [$reg, $m['count'], collect($m['sources'])->map(fn ($n, $s) => "$s $n")->implode('; '), '', '', '']);
        }
        fclose($fh);

        $noWg = DB::table('aircrafts')->where(fn ($q) => $q->whereNull('wg')->orWhere('wg', ''))->orderBy('maskapai')->orderBy('registration')->get(['registration', 'tipe', 'maskapai']);
        $wgFile = "{$dir}/aircraft-without-wg-{$stamp}.csv";
        $fh = fopen($wgFile, 'w');
        fputcsv($fh, ['registration', 'tipe', 'maskapai', 'wg (isi)']);
        foreach ($noWg as $a) {
            fputcsv($fh, [$a->registration, $a->tipe, $a->maskapai, '']);
        }
        fclose($fh);

        $this->info(count($missing).' registrasi dipakai di log tetapi belum ada di master -> '.$file);
        $this->info($noWg->count().' pesawat master belum punya WG -> '.$wgFile);

        return self::SUCCESS;
    }
}
