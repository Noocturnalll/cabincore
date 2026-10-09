<?php

namespace App\Services\Roster;

use App\Models\RosterEntry;
use App\Models\ShiftCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports the monthly roster workbook ("ROSTER CLUSTER CBM ..."): one sheet per station, one row per employee,
 * one column per date, each cell a shift code (P40, M21, S12 ...). Column "DIV" holds the team.
 * The "Code" sheet maps codes to PAGI / SIANG / MALAM. Re-running replaces the dates found in the file per station.
 */
class RosterImporter
{
    /** Sheets that are summaries, not stations. */
    private const NOT_STATIONS = ['DAILY REPORT MP', 'MP SHIFT', 'MP DAILY', 'CODE'];

    /** @var array<string, array{shift: ?string, time: ?string, label: ?string}> */
    private array $codes = [];

    /** @return array{stations: int, entries: int, duplicates: int, unknown_codes: array<string, int>, from: ?string, to: ?string} */
    public function import(string $path, bool $dryRun = false): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $codeSheet = $book->getSheetByName('Code');
        $this->codes = $codeSheet ? $this->readCodes($codeSheet) : [];
        if (! $dryRun) {
            $this->saveCodes();
        }

        $stats = ['stations' => 0, 'entries' => 0, 'duplicates' => 0, 'unknown_codes' => [], 'from' => null, 'to' => null];
        foreach ($book->getAllSheets() as $ws) {
            if (in_array(strtoupper(trim($ws->getTitle())), self::NOT_STATIONS, true)) {
                continue;
            }
            $rows = $this->readStation($ws, $stats);
            if (! $rows) {
                continue;
            }
            $stats['stations']++;
            $stats['entries'] += count($rows);
            if (! $dryRun) {
                $this->store(strtoupper(trim($ws->getTitle())), $rows);
            }
        }
        arsort($stats['unknown_codes']);

        return $stats;
    }

    /** Shift for a roster code; a code missing from the Code sheet is guessed from its first letter. */
    public function shiftOf(string $code): ?string
    {
        $code = strtoupper(trim($code));
        if (isset($this->codes[$code])) {
            return $this->codes[$code]['shift'];
        }

        return match (true) {
            (bool) preg_match('/^P\d/', $code) && $code !== 'P00' => 'PAGI',
            (bool) preg_match('/^S\d/', $code) => 'SIANG',
            (bool) preg_match('/^M\d/', $code) => 'MALAM',
            default => null,
        };
    }

    /** @return array<string, array{shift: ?string, time: ?string, label: ?string}> */
    private function readCodes(Worksheet $ws): array
    {
        $codes = [];
        // Columns: A/B morning, C/D afternoon, E/F night, G/H other (P00 = training, OFF ...)
        foreach ([['A', 'B', 'PAGI'], ['C', 'D', 'SIANG'], ['E', 'F', 'MALAM'], ['G', 'H', null]] as [$codeCol, $timeCol, $shift]) {
            for ($r = 2, $last = $ws->getHighestDataRow(); $r <= $last; $r++) {
                $code = strtoupper(trim((string) $ws->getCell("$codeCol$r")->getValue()));
                if ($code === '' || isset($codes[$code])) {
                    continue;
                }
                $time = trim((string) $ws->getCell("$timeCol$r")->getValue());
                $codes[$code] = [
                    'shift' => $shift,
                    'time' => preg_match('/^\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/', $time) ? str_replace(' ', '', $time) : null,
                    'label' => $shift === null || ! preg_match('/\d/', $time) ? ($time ?: null) : null,
                ];
            }
        }
        // "S" (sick) and "C" (leave) sit under the morning column of the Code sheet but are absences
        foreach (['S', 'C'] as $absence) {
            $codes[$absence] = ['shift' => null, 'time' => null, 'label' => $absence === 'S' ? 'SAKIT' : 'CUTI'];
        }

        return $codes;
    }

    private function saveCodes(): void
    {
        foreach ($this->codes as $code => $c) {
            ShiftCode::updateOrCreate(['code' => $code], ['shift' => $c['shift'], 'time_range' => $c['time'], 'label' => $c['label']]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function readStation(Worksheet $ws, array &$stats): array
    {
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        $highestRow = $ws->getHighestDataRow();

        $dates = [];
        $teamCol = null;
        for ($c = 5; $c <= $highestCol; $c++) {
            $v = $ws->getCell([$c, 2])->getValue();
            if (is_numeric($v) && $v > 40000) {
                $dates[$c] = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString();
            }
            // the team column is titled DIV, on row 2 or 3 depending on the sheet
            foreach ([2, 3] as $titleRow) {
                if (strtoupper(trim((string) $ws->getCell([$c, $titleRow])->getValue())) === 'DIV') {
                    $teamCol = $c;
                }
            }
        }
        if (! $dates) {
            return [];
        }
        // Some sheets do not title the team column: it is the one right after the last date
        $teamCol ??= max(array_keys($dates)) + 1;

        $rows = [];
        $seen = [];
        for ($r = 4; $r <= $highestRow; $r++) {
            $id = $this->id($ws->getCell([3, $r])->getValue());
            if (! $id) {
                continue;
            }
            $name = trim((string) $ws->getCell([2, $r])->getValue());
            $position = trim((string) $ws->getCell([4, $r])->getValue());
            $team = $teamCol ? strtoupper(trim((string) $ws->getCell([$teamCol, $r])->getValue())) : '';

            foreach ($dates as $c => $date) {
                $code = strtoupper(trim((string) $ws->getCell([$c, $r])->getValue()));
                if ($code === '') {
                    continue;   // empty cell = no entry (not a working day)
                }
                // The same person can be listed twice on one sheet; the first row wins so nobody counts double
                if (isset($seen[$date.'|'.$id])) {
                    $stats['duplicates']++;

                    continue;
                }
                $seen[$date.'|'.$id] = true;
                if (! isset($this->codes[$code]) && $this->shiftOf($code) === null && ! in_array($code, ['OFF', 'P00'], true)) {
                    $stats['unknown_codes'][$code] = ($stats['unknown_codes'][$code] ?? 0) + 1;
                }
                $rows[] = [
                    'work_date' => $date, 'employee_id' => $id, 'employee_name' => $name ?: null, 'position' => $position ?: null,
                    'team' => $team ?: null, 'shift_code' => $code, 'shift' => $this->shiftOf($code),
                ];
                $stats['from'] = $stats['from'] === null ? $date : min($stats['from'], $date);
                $stats['to'] = $stats['to'] === null ? $date : max($stats['to'], $date);
            }
        }

        return $rows;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function store(string $station, array $rows): void
    {
        $dates = array_column($rows, 'work_date');
        $now = now();

        DB::transaction(function () use ($station, $rows, $dates, $now) {
            RosterEntry::where('station', $station)
                ->whereDate('work_date', '>=', min($dates))->whereDate('work_date', '<=', max($dates))->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                RosterEntry::insert(array_map(fn ($r) => $r + ['station' => $station, 'created_at' => $now, 'updated_at' => $now], $chunk));
            }
        });
    }

    private function id(mixed $value): ?string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }
        $id = strtoupper(trim((string) $value));

        return $id === '' || ! preg_match('/\d/', $id) ? null : $id;
    }
}
