<?php

namespace App\Services\Kpi;

use App\Models\DocumentAccuracy;
use App\Models\LgtRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports the two Excel KPI books that stations keep today:
 *  "KPI Document Accuracy"  sheet DATA   per station and day: CML / NSRDI reported and missed (recorded = reported - missed)
 *  "LGT Monitoring"         sheet DATA   one row per long-ground-time aircraft, CBM and AIEC result
 * Both are re-runnable: rows are matched on their natural key and updated.
 */
class KpiExcelImporter
{
    /** The LGT sheet is formatted down to row 1,048,576; only rows up to the last filled date are read. */
    private const MAX_ROWS = 60000;

    /** @return array{read: int, saved: int, from: ?string, to: ?string} */
    public function importDocumentAccuracy(string $path, bool $dryRun = false): array
    {
        $ws = $this->sheet($path, 'DATA');
        $stats = ['read' => 0, 'saved' => 0, 'from' => null, 'to' => null];
        $days = [];

        for ($r = 1, $last = $ws->getHighestDataRow(); $r <= $last; $r++) {
            $date = $this->date($ws->getCell("A$r")->getValue());
            $station = strtoupper(trim((string) $ws->getCell("B$r")->getValue()));
            if (! $date || $station === '' || ! is_numeric($ws->getCell("C$r")->getValue()) && ! is_numeric($ws->getCell("D$r")->getValue())) {
                continue;
            }
            $cml = (int) $ws->getCell("C$r")->getValue();
            $nsrdi = (int) $ws->getCell("D$r")->getValue();
            if ($cml + $nsrdi === 0 && ! trim((string) $ws->getCell("M$r")->getValue())) {
                continue;   // pre-filled future days
            }
            $stats['read']++;

            $row = [
                'cml_reported' => $cml,
                'nsrdi_reported' => $nsrdi,
                'cml_missed' => min($cml, max(0, (int) $this->plain($ws, "G$r"))),
                'nsrdi_missed' => min($nsrdi, max(0, (int) $this->plain($ws, "H$r"))),
                'remarks' => trim((string) $ws->getCell("M$r")->getValue()) ?: null,
            ];
            // The sheet can list a station twice on one day; its own SUMIFS adds them up, so do the same
            $key = $date.'|'.$station;
            if (isset($days[$key])) {
                foreach (['cml_reported', 'nsrdi_reported', 'cml_missed', 'nsrdi_missed'] as $k) {
                    $days[$key][$k] += $row[$k];
                }
                $days[$key]['remarks'] = collect([$days[$key]['remarks'], $row['remarks']])->filter()->unique()->implode('; ') ?: null;
            } else {
                $days[$key] = ['work_date' => $date, 'station' => $station] + $row;
            }
            $stats['from'] = $stats['from'] === null ? $date : min($stats['from'], $date);
            $stats['to'] = $stats['to'] === null ? $date : max($stats['to'], $date);
        }

        $stats['saved'] = count($days);
        if (! $dryRun) {
            // Plain upsert with Y-m-d strings, so the date column keeps one format (queries compare it as text)
            $now = now();
            foreach (array_chunk(array_values($days), 500) as $chunk) {
                DocumentAccuracy::upsert(
                    array_map(fn ($d) => $d + ['created_at' => $now, 'updated_at' => $now], $chunk),
                    ['work_date', 'station'],
                    ['cml_reported', 'nsrdi_reported', 'cml_missed', 'nsrdi_missed', 'remarks', 'updated_at']
                );
            }
        }

        return $stats;
    }

    /** @return array{read: int, saved: int, from: ?string, to: ?string, skipped: int} */
    public function importLgt(string $path, bool $dryRun = false, string $sheet = 'DATA'): array
    {
        $ws = $this->sheet($path, $sheet, self::MAX_ROWS);
        $stats = ['read' => 0, 'saved' => 0, 'skipped' => 0, 'from' => null, 'to' => null];

        $cols = $this->lgtColumns($ws);
        $batch = [];
        $get = fn (string $key, int $row, int $nth = 0) => isset($cols[$key][$nth]) ? $ws->getCell([$cols[$key][$nth], $row])->getValue() : null;

        for ($r = $cols['_header'] + 1, $last = $ws->getHighestDataRow(); $r <= $last; $r++) {
            $date = $this->date($get('date', $r));
            $reg = strtoupper(trim((string) $get('reg', $r)));
            $station = strtoupper(trim((string) $get('station', $r)));
            if (! $date || $reg === '' || $station === '') {
                continue;
            }
            $stats['read']++;

            $cbmStatus = $this->status($get('status', $r, 0));
            $aiecStatus = $this->status($get('status', $r, 1));
            if (! $cbmStatus && ! $aiecStatus) {
                $stats['skipped']++;

                continue;
            }

            $sta = $this->clock($get('sta', $r));
            $row = [
                'aoc' => trim((string) $get('aoc', $r)) ?: null,
                'std_time' => $this->clock($get('std', $r)),
                'cbm_action' => $this->text($get('action', $r, 0)),
                'cbm_mp' => $this->text($get('mp', $r, 0)),
                'cbm_status' => $cbmStatus,
                'aiec_action' => $this->text($get('action', $r, 1)),
                'aiec_mp' => $this->text($get('mp', $r, 1)),
                'aiec_status' => $aiecStatus,
                'reason' => $this->text($get('reason', $r, 0)),
            ];
            $batch[] = ['work_date' => $date, 'station' => $station, 'aircraft_registration' => $reg, 'sta_time' => $sta] + $row;
            $stats['saved']++;
            $stats['from'] = $stats['from'] === null ? $date : min($stats['from'], $date);
            $stats['to'] = $stats['to'] === null ? $date : max($stats['to'], $date);
        }

        // A task row has no natural key (one aircraft can have several tasks in the same long ground time), so a re-import
        // replaces the whole date range found in the file instead of adding to it.
        if (! $dryRun && $batch) {
            DB::transaction(function () use ($batch, $stats) {
                LgtRecord::whereDate('work_date', '>=', $stats['from'])->whereDate('work_date', '<=', $stats['to'])->delete();
                $now = now();
                foreach (array_chunk($batch, 500) as $chunk) {
                    LgtRecord::insert(array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk));
                }
            });
        }

        return $stats;
    }

    /**
     * Column positions by header name; names that repeat (MP INCH, STATUS, ACTION TAKEN ...) keep every position in order:
     * the first is the CBM block, the second the AIEC block.
     *
     * @return array<string, mixed> key => list of 1-based columns; '_header' => header row
     */
    private function lgtColumns(Worksheet $ws): array
    {
        $highest = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        for ($row = 1; $row <= 6; $row++) {
            $cols = ['_header' => $row];
            for ($c = 1; $c <= $highest; $c++) {
                $name = strtoupper(trim((string) $ws->getCell([$c, $row])->getValue()));
                $key = match (true) {
                    $name === 'DATE' => 'date',
                    $name === 'AC REG' => 'reg',
                    $name === 'AOC' => 'aoc',
                    $name === 'STA' => 'sta',
                    $name === 'STD' => 'std',
                    $name === 'STATION' => 'station',
                    str_starts_with($name, 'ACTION TAKEN') => 'action',
                    str_starts_with($name, 'MP') => 'mp',
                    str_starts_with($name, 'STATUS') => 'status',
                    str_starts_with($name, 'REASON') => 'reason',
                    default => null,
                };
                if ($key) {
                    $cols[$key][] = $c;
                }
            }
            // The first header cell is the date; the station is the column right after it when it has no title
            if (isset($cols['date'], $cols['reg'])) {
                if (! isset($cols['station']) && $ws->getCell([$cols['date'][0] + 1, $row])->getValue() === null) {
                    $cols['station'] = [$cols['date'][0] + 1];
                }
                // In the DATA sheet column "STA" is the arrival time and a second column holds the station name
                if (isset($cols['station'])) {
                    return $cols;
                }
            }
        }

        throw new \RuntimeException('Header sheet LGT tidak dikenali (butuh DATE, AC REG, STATUS).');
    }

    private function sheet(string $path, string $name, ?int $maxRows = null): Worksheet
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$name]);
        if ($maxRows) {
            $reader->setReadFilter(new class($maxRows) implements IReadFilter
            {
                public function __construct(private int $max) {}

                public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                {
                    return $row <= $this->max;
                }
            });
        }

        return $reader->load($path)->getSheetByName($name);
    }

    /** A formula cell is read as the value Excel last calculated. */
    private function plain(Worksheet $ws, string $address): mixed
    {
        $cell = $ws->getCell($address);

        return $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
    }

    private function date(mixed $value): ?string
    {
        if (is_numeric($value) && $value > 30000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function clock(mixed $value): ?string
    {
        if (is_numeric($value) && $value >= 0 && $value < 1) {
            $minutes = (int) round($value * 1440);

            return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
        }

        return null;
    }

    private function status(mixed $value): ?string
    {
        $s = strtoupper(trim((string) $value));

        return in_array($s, ['CLOSED', 'CANCEL', 'OPEN'], true) ? $s : null;
    }

    private function text(mixed $value): ?string
    {
        $t = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $t === '' ? null : mb_substr($t, 0, 250);
    }
}
