<?php

namespace App\Services\Aiec;

use App\Models\AircraftCleaning;
use App\Models\JobCrew;
use App\Services\Audit\AocResolver;
use App\Services\Crew\CrewWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports the AIEC Report workbook: every sheet with the job table (DATE, A/C REG, STA, SHIFT, ACTION TAKEN,
 * START PERFORM, FINISH PERFORM, MP 1..n) becomes aircraft cleaning records with their real crew and man hours:
 *
 *     man hours = people listed in MP 1..n  x  (finish - start, finish after midnight rolls to the next day)
 *
 * The action decides the cleaning type (TRANSIT, GENERAL, GENERAL EXTERIOR = GCE, DCI, DCE). Re-running replaces the
 * dates found in the file, only for rows that came from this import: records typed in by hand are never touched.
 */
class AiecReportImporter
{
    public const SOURCE = 'aiec_report';

    private const TYPES = [
        'TRANSIT' => 'Transit',
        'GENERAL' => 'General',
        'GENERAL INTERIOR' => 'GCI',
        'GENERAL EXTERIOR' => 'GCE',
        'DCI' => 'DCI',
        'DCE' => 'DCE',
        'DBI' => 'DBI',
        'LGT' => 'LGT',
    ];

    /** @return array{sheets: array<string, int>, saved: int, skipped: array<string, int>, from: ?string, to: ?string} */
    public function import(string $path, bool $dryRun = false): array
    {
        @ini_set('memory_limit', '2G');   // a month of jobs is ~12,000 rows x 29 columns

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $aocs = app(AocResolver::class);
        $stats = ['sheets' => [], 'saved' => 0, 'skipped' => [], 'from' => null, 'to' => null];

        foreach ($book->getAllSheets() as $ws) {
            $rows = $this->readSheet($ws, $aocs, $stats);
            if (! $rows) {
                continue;
            }
            $stats['sheets'][$ws->getTitle()] = count($rows);
            $stats['saved'] += count($rows);
            if (! $dryRun) {
                $this->store($rows);
            }
        }

        return $stats;
    }

    /** @return array<int, array<string, mixed>> */
    private function readSheet(Worksheet $ws, AocResolver $aocs, array &$stats): array
    {
        $last = $ws->getHighestDataRow();
        $header = null;
        for ($r = 1; $r <= min(6, $last); $r++) {
            $names = array_map(fn ($v) => strtoupper(trim((string) $v)), $ws->rangeToArray('A'.$r.':AC'.$r, null, false, false, false)[0]);
            if (in_array('DATE', $names, true) && in_array('A/C REG', $names, true) && in_array('START PERFORM', $names, true)) {
                $header = ['row' => $r, 'names' => $names];
                break;
            }
        }
        if (! $header) {
            return [];
        }

        $ix = [];
        $mp = [];
        foreach ($header['names'] as $i => $name) {
            if (preg_match('/^MP \d+$/', $name)) {
                $mp[] = $i;
            } elseif ($name !== '') {
                $ix[$name] ??= $i;
            }
        }
        foreach (['DATE', 'A/C REG', 'STA', 'SHIFT', 'ACTION TAKEN / REASON', 'START PERFORM', 'FINISH PERFORM'] as $need) {
            if (! isset($ix[$need])) {
                return [];
            }
        }

        $data = $ws->rangeToArray('A'.($header['row'] + 1).':AC'.$last, null, false, false, false);
        $out = [];
        foreach ($data as $row) {
            $serial = $row[$ix['DATE']] ?? null;
            $reg = strtoupper(trim((string) ($row[$ix['A/C REG']] ?? '')));
            if (! is_numeric($serial) || $serial < 30000 || $reg === '') {
                continue;
            }
            $date = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $serial))->toDateString();

            $action = strtoupper(trim(preg_replace('/\s+/', ' ', (string) ($row[$ix['ACTION TAKEN / REASON']] ?? ''))));
            $type = self::TYPES[$action] ?? null;
            if (! $type) {
                $stats['skipped'][$action ?: '(kosong)'] = ($stats['skipped'][$action ?: '(kosong)'] ?? 0) + 1;

                continue;
            }

            $ids = [];
            foreach ($mp as $col) {
                $v = $row[$col] ?? null;
                if (is_float($v) && floor($v) === $v) {
                    $v = (int) $v;
                }
                $v = trim((string) $v);
                if ($v !== '') {
                    $ids[] = $v;
                }
            }

            [$start, $end] = $this->window($date, $row[$ix['START PERFORM']] ?? null, $row[$ix['FINISH PERFORM']] ?? null);
            $people = count($ids);
            $status = strtoupper(trim((string) ($row[$ix['STATUS']] ?? 'CLOSED')));

            $out[] = [
                'aircraft_registration' => $reg,
                'date' => $date,
                'station' => strtoupper(trim((string) ($row[$ix['STA']] ?? ''))) ?: null,
                'shift' => ucfirst(strtolower(trim((string) ($row[$ix['SHIFT']] ?? '')))) ?: 'Pagi',
                'type' => $type,
                'status' => $status === 'OPEN' ? 'Open' : 'Closed',
                'remarks' => null,
                'operator' => $aocs->resolve($reg)?->name,
                'start_at' => $start,
                'end_at' => $end,
                'man_power' => $people ?: null,
                'man_hour' => $people && $start && $end ? round($people * (strtotime($end) - strtotime($start)) / 3600, 2) : null,
                'mp_ids' => $ids ? mb_substr(implode(', ', $ids), 0, 250) : null,
                'ac_status' => strtoupper(trim((string) ($row[$ix['A/C STATUS']] ?? ''))) ?: null,
                'task_type' => strtoupper(trim((string) ($row[$ix['TASK TYPE']] ?? ''))) ?: null,
                'ref_no' => mb_substr(trim((string) ($row[$ix['NO. DOC']] ?? '')), 0, 40) ?: null,
                'import_source' => self::SOURCE,
            ];
            $stats['from'] = $stats['from'] === null ? $date : min($stats['from'], $date);
            $stats['to'] = $stats['to'] === null ? $date : max($stats['to'], $date);
        }

        return $out;
    }

    /** @return array{0: ?string, 1: ?string} datetimes; a finish at or before the start is the next day */
    private function window(string $date, mixed $start, mixed $end): array
    {
        // A time of day is a fraction of a day; a whole number such as "12" is a typo and would turn into a bogus 12-hour job
        if (! is_numeric($start) || ! is_numeric($end) || ($start >= 1 && floor($start) === (float) $start) || ($end >= 1 && floor($end) === (float) $end)) {
            return [null, null];
        }
        $s = Carbon::parse($date)->addMinutes((int) round(fmod((float) $start, 1) * 1440));
        $e = Carbon::parse($date)->addMinutes((int) round(fmod((float) $end, 1) * 1440));
        if ($e->lte($s)) {
            $e->addDay();
        }
        // No cleaning job takes longer than this; such a row is a data-entry slip and must not inflate the man hours
        if ($s->diffInMinutes($e) > config('kpi.max_job_hours', 14) * 60) {
            return [null, null];
        }

        return [$s->toDateTimeString(), $e->toDateTimeString()];
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function store(array $rows): void
    {
        $dates = array_column($rows, 'date');
        $now = now();

        $crew = new CrewWriter;

        DB::transaction(function () use ($rows, $dates, $now, $crew) {
            $old = AircraftCleaning::where('import_source', self::SOURCE)
                ->whereDate('date', '>=', min($dates))->whereDate('date', '<=', max($dates));
            $crew->forget('aircraft_cleaning', (clone $old)->pluck('id')->all());
            $old->delete();

            // rows get ascending ids in insert order, so the ids created by a chunk line up with the chunk
            foreach (array_chunk($rows, 400) as $chunk) {
                $before = (int) AircraftCleaning::max('id');
                AircraftCleaning::insert(array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk));
                $ids = AircraftCleaning::where('id', '>', $before)->orderBy('id')->pluck('id')->all();

                $pivot = [];
                foreach ($chunk as $i => $r) {
                    if (isset($ids[$i]) && $r['mp_ids']) {
                        array_push($pivot, ...$crew->rows('aircraft_cleaning', $ids[$i], CrewWriter::refs($r['mp_ids']), $r['man_hour'], $r['date'], $r['station']));
                    }
                }
                foreach (array_chunk($pivot, 500) as $part) {
                    JobCrew::insert($part);
                }
            }
        });
    }
}
