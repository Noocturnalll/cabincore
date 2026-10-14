<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Support\StationNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off clean-up of station cells that are not station codes ("BANTEK PNK", "UNSCEDHULE", formulas), and
 * registration of real stations that have work but are missing from the Bandara master (BDJ, NBX, CBN, ...).
 * Stations without roster / MP CBM are fine: their tasks count, they just show "tanpa MP" in the KPI.
 */
class CleanStations extends Command
{
    protected $signature = 'stations:clean {--dry-run : Only report what would change} {--no-master : Do not add stations to the Bandara master}';

    protected $description = 'Normalise station codes in the task logs and add missing real stations to the Bandara master';

    /** Stations we know by IATA code: code => [name, city]. Anything else is reported, never invented. */
    private const KNOWN = [
        'CBN' => ['Penggung Airport', 'Cirebon'],
        'DMK' => ['Don Mueang International Airport', 'Bangkok'],
        'BDJ' => ['Syamsudin Noor Airport', 'Banjarmasin'],
        'NBX' => ['Douw Aturure Airport', 'Nabire'],
        'PNK' => ['Supadio International Airport', 'Pontianak'],
        'JED' => ['King Abdulaziz International Airport', 'Jeddah'],
        'BEJ' => ['Kalimarau Airport', 'Berau'],
        'BTW' => ['Bersujud Airport', 'Batulicin'],
        'BTJ' => ['Sultan Iskandar Muda International Airport', 'Banda Aceh'],
        'DJJ' => ['Sentani Airport', 'Jayapura'],
        'AAP' => ['APT Pranoto Airport', 'Samarinda'],
        'DJB' => ['Sultan Thaha Airport', 'Jambi'],
        'WGP' => ['Umbu Mehang Kunda Airport', 'Waingapu'],
    ];

    /** table => columns that hold a station */
    private const COLUMNS = [
        'wo_logs' => ['act_station', 'plan_station'],
        'dmi_logs' => ['act_station', 'plan_station'],
        'nsrdi_logs' => ['act_station', 'plan_station'],
        'daily_job_assignments' => ['station'],
        'cml_logs' => ['station'],
        'aircraft_cleanings' => ['station'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $unusable = [];

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $values = DB::table($table)->whereNotNull($column)->where($column, '!=', '')->select($column.' as v', DB::raw('COUNT(*) as n'))->groupBy($column)->get();
                $shown = [];
                foreach ($values as $row) {
                    // "Unknown" is the importer's own placeholder for a task without a station
                    if (strcasecmp($row->v, 'Unknown') === 0) {
                        continue;
                    }
                    $clean = StationNormalizer::normalize($row->v);
                    if ($clean === $row->v) {
                        continue;
                    }
                    $label = str_starts_with(trim($row->v), '=') ? '(rumus Excel yang masuk sebagai teks)' : mb_substr($row->v, 0, 40);
                    $key = $label.' -> '.($clean ?? 'NULL');
                    $shown[$key] = ($shown[$key] ?? 0) + $row->n;
                    if ($clean === null) {
                        $unusable[$label] = ($unusable[$label] ?? 0) + $row->n;
                    }
                    if (! $dry) {
                        DB::table($table)->where($column, $row->v)->update([$column => $clean]);
                    }
                    $changed += $row->n;
                }
                foreach ($shown as $key => $n) {
                    $this->line(sprintf('  %-22s %-12s %s  (%d)', $table, $column, $key, $n));
                }
            }
        }
        $this->info(($dry ? 'Would change ' : 'Changed ').$changed.' cells.');

        if ($this->option('no-master')) {
            return self::SUCCESS;
        }

        // real stations that have work but no master row
        $used = collect();
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $used = $used->merge(DB::table($table)->whereNotNull($column)->distinct()->pluck($column));
            }
        }
        $used = $used->map(fn ($c) => StationNormalizer::normalize($c))->filter()->unique();
        $have = Airport::pluck('kode')->map(fn ($k) => strtoupper($k));
        $missing = $used->diff($have)->values();

        $added = [];
        $unknown = [];
        foreach ($missing as $code) {
            if (! isset(self::KNOWN[$code])) {
                $unknown[] = $code;

                continue;
            }
            if (! $dry) {
                Airport::create(['kode' => $code, 'nama' => self::KNOWN[$code][0], 'kota' => self::KNOWN[$code][1], 'status' => 'Aktif']);
            }
            $added[] = $code;
        }

        $this->info(($dry ? 'Would add to the Bandara master: ' : 'Added to the Bandara master: ').($added ? implode(', ', $added) : 'none'));
        if ($unknown) {
            $this->warn('Used in the logs but not a station we can name, check them by hand: '.implode(', ', $unknown));
        }
        if ($unusable) {
            $this->warn('Cells that were not stations and are now empty: '.collect($unusable)->map(fn ($n, $v) => "\"$v\" ($n)")->implode(', '));
        }

        return self::SUCCESS;
    }
}
