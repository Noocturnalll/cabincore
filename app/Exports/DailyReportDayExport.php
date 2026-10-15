<?php

namespace App\Exports;

use App\Exports\Sheets\DetailDataSheet;
use App\Services\Dja\DjaPersister;
use App\Services\Kpi\OperationalInsights;
use App\Services\Kpi\ReportPeriod;
use App\Support\DashboardScope;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One workbook for one day: DJA (WO, DMI, NSRDI), Unplanned (WO, DMI, NSRDI), CML and DBI, each with its own pivot
 * and detail, followed by the advanced pivots (WG, AOC, ageing, reasons, planned vs unplanned, man hours) of that day.
 */
class DailyReportDayExport implements Export, WithMultipleSheets
{
    /** tab => sheet name prefix (an Excel sheet name is limited to 31 characters) */
    public const TABS = [
        'dja-wo' => 'DJA WO',
        'unplanned-wo' => 'Unplanned WO',
        'dja-dmi' => 'DJA DMI',
        'unplanned-dmi' => 'Unplanned DMI',
        'dja-nsrdi' => 'DJA NSRDI',
        'unplanned-nsrdi' => 'Unplanned NSRDI',
        'cml' => 'CML',
        'dbi' => 'DBI',
    ];

    public function __construct(protected string $date = '', protected string $search = '')
    {
        $this->date = $this->date ?: DjaPersister::activeDate();
    }

    public function date(): string
    {
        return $this->date;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach (self::TABS as $tab => $prefix) {
            foreach ((new DailyReportExport($this->search, $this->date, $tab))->withSheetPrefix($prefix)->sheets() as $sheet) {
                $sheets[] = $sheet;
            }
        }

        return array_merge($sheets, $this->insightSheets());
    }

    /** @return array<int, DetailDataSheet> */
    private function insightSheets(): array
    {
        try {
            $user = auth()->user();
            $stations = $user ? DashboardScope::for($user)->stations : null;
            $d = app(OperationalInsights::class)->build(ReportPeriod::day(Carbon::parse($this->date)), $stations);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        $sheets = [];
        $mods = ['wo' => 'WO', 'dmi' => 'DMI', 'nsrdi' => 'NSRDI'];

        foreach (['byWg' => ['Pivot WG', 'WG'], 'byAoc' => ['Pivot AOC', 'OPERATOR / AOC']] as $key => [$title, $label]) {
            $rows = collect($d[$key])->map(function ($row, $name) use ($mods) {
                $line = [$name];
                $closed = $total = 0;
                foreach (array_keys($mods) as $m) {
                    $line[] = isset($row[$m]) ? $row[$m]['closed'].' / '.$row[$m]['total'] : '-';
                    $closed += $row[$m]['closed'] ?? 0;
                    $total += $row[$m]['total'] ?? 0;
                }
                $line[] = $total ? round($closed / $total * 100).'%' : '-';

                return $line;
            })->values();
            $sheets[] = new DetailDataSheet($rows, [$label, ...array_values($mods), '% CLOSED'], $title);
        }

        $aging = collect($d['aging'])->map(fn ($row, $bucket) => [$bucket, $row['wo'] ?? 0, $row['dmi'] ?? 0, $row['nsrdi'] ?? 0, array_sum($row)])->values();
        $sheets[] = new DetailDataSheet($aging, ['UMUR OPEN', 'WO', 'DMI', 'NSRDI', 'TOTAL'], 'Umur Open');

        $reasons = collect($d['reasons'])->map(fn ($n, $code) => [$code, $n])->values();
        $sheets[] = new DetailDataSheet($reasons, ['CODE REASON', 'JUMLAH'], 'Reason Open');

        $planned = collect($d['planned'])->map(fn ($r, $sta) => [$sta, $r['planned'], $r['unplanned'], ($r['planned'] + $r['unplanned']) ? round($r['unplanned'] / ($r['planned'] + $r['unplanned']) * 100).'%' : '-'])->values();
        $sheets[] = new DetailDataSheet($planned, ['STATION', 'PLANNED', 'UNPLANNED', '% UNPLANNED'], 'Planned vs Unplanned');

        $mh = collect($d['manHours']['perStation'])->map(fn ($r, $sta) => [$sta, $r['CML']['jobs'] ?? 0, $r['CML']['hours'] ?? 0, $r['DBI']['jobs'] ?? 0, $r['DBI']['hours'] ?? 0])->values();
        $sheets[] = new DetailDataSheet($mh, ['STATION', 'CML JOB', 'CML JAM', 'DBI JOB', 'DBI JAM'], 'Man-hour CML & DBI');

        return $sheets;
    }
}
