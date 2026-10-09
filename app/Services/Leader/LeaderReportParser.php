<?php

namespace App\Services\Leader;

use App\Services\Crew\CrewWriter;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reads a group leader's Excel report into normalised task rows (one per document).
 * Each sheet is scanned for a header row; columns are recognised by name, so the sheets of the daily workbook
 * (WO, DMI CBM, NSRDI, UNPLANNED, DAILY REPORT, ...) and a plain leader sheet all work. Sheets without
 * a document-number column (SUMMARY, ...) are ignored.
 *
 * A document that appears on several lines (e.g. one WO with two task lines) is merged into one row:
 * man hours add up, man power takes the highest value, start is the earliest, end the latest, and
 * the document is Open if any line is Open.
 */
class LeaderReportParser
{
    /** header slug => canonical field */
    private const ALIASES = [
        'doc_no' => ['no_doc', 'nodoc', 'doc_no', 'no_dokumen', 'nomor_dokumen', 'nomor_doc', 'finding_no'],
        'wo' => ['wo', 'wo_no', 'wo_number', 'no_wo'],
        'dmi' => ['dmi_no', 'dmi_number', 'no_dmi'],
        'nsrdi' => ['nsrdi', 'nsrdi_no', 'nsrdi_number', 'no_nsrdi'],
        'doc_type' => ['doc_type', 'tipe_dokumen', 'jenis_dokumen', 'type_doc'],
        'reg' => ['ac_reg', 'a_c_reg', 'reg', 'registration', 'aircraft_registration', 'a_c_reg', 'registrasi'],
        'station' => ['act_sta', 'sta', 'station', 'act_station', 'stasiun'],
        'plan_station' => ['plan_sta', 'plan_station'],
        'status' => ['status'],
        'description' => ['wo_description', 'dmi_description', 'finding_description', 'defect_description', 'deffect_description', 'description', 'action_taken', 'deskripsi'],
        'man_power' => ['total_mp', 'mp', 'man_power', 'manpower', 'jumlah_mp', 'jml_mp', 'jumlah_orang', 'jml_orang'],
        'start' => ['start_perform', 'start', 'start_time', 'mulai', 'jam_mulai', 'jam_start', 'time_start', 'start_job'],
        'end' => ['finish_perform', 'end', 'end_time', 'finish', 'finish_time', 'selesai', 'jam_selesai', 'time_end', 'stop', 'end_job'],
        'man_hours' => ['actual_mhrs', 'man_hours', 'man_hour', 'mh', 'mhrs'],
        'date' => ['date', 'tanggal', 'refresh_date'],
        'reason_open' => ['reason_open', 'reason'],
        'code_open' => ['code_open', 'code_reason', 'code'],
        'operator' => ['operator', 'aoc'],
        'ata' => ['ata', 'ata_chapter', 'ata_code', 'chapter'],
    ];

    /** @return array<int, array<string, mixed>> */
    public function parse(string $path, Carbon $defaultDate): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $rows = [];
        foreach ($book->getAllSheets() as $sheet) {
            foreach ($this->parseSheet($sheet, $defaultDate) as $row) {
                $rows[] = $row;
            }
        }

        return $this->merge($rows);
    }

    /** For sheets that were not read from a file (a Google Sheet turned into a worksheet). */
    public function parseWorksheet(Worksheet $sheet, Carbon $defaultDate): array
    {
        return $this->merge($this->parseSheet($sheet, $defaultDate));
    }

    private function parseSheet(Worksheet $sheet, Carbon $defaultDate): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        if ($highestRow < 2) {
            return [];
        }

        $map = null;
        $headerRow = null;
        for ($r = 1; $r <= min(20, $highestRow); $r++) {
            $found = $this->mapHeader($sheet, $r, $highestCol);
            $hasDoc = isset($found['doc_no']) || isset($found['wo']) || isset($found['dmi']) || isset($found['nsrdi']);
            if ($hasDoc && isset($found['reg'])) {
                $map = $found;
                $headerRow = $r;
                break;
            }
        }
        if (! $map) {
            return [];
        }

        $out = [];
        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $get = fn (string $field) => isset($map[$field]) ? $this->value($sheet->getCell([$map[$field], $r])) : null;

            [$docNo, $type] = $this->document($get, $sheet->getTitle());
            $reg = $this->text($get('reg'));
            if ($docNo === null || $reg === null) {
                continue;
            }

            $date = $this->date($get('date')) ?? $defaultDate->copy()->startOfDay();
            [$start, $end] = $this->window($date, $get('start'), $get('end'));
            $crew = [];
            $manPower = is_numeric($get('man_power')) ? (int) $get('man_power') : null;
            // the people named in MP 1..n are the crew, whether or not a Total MP column is also filled in
            foreach ($map['mp_names'] ?? [] as $col) {
                $name = trim((string) $this->value($sheet->getCell([$col, $r])));
                if ($name !== '') {
                    $crew[] = is_numeric($name) ? (string) (int) $name : $name;
                }
            }
            if ($manPower === null && $crew) {
                $manPower = count($crew);
            }

            $manHours = $this->number($get('man_hours'));
            if ($manPower && $start && $end) {
                $manHours = round($manPower * $start->diffInMinutes($end) / 60, 2);
            }

            $out[] = [
                'sheet' => $sheet->getTitle(),
                'doc_type' => $type,
                'doc_no' => $docNo,
                'aircraft_registration' => strtoupper($reg),
                'station' => $this->text($get('station')),
                'plan_station' => $this->text($get('plan_station')),
                'operator' => $this->text($get('operator')),
                'ata' => $this->text($get('ata')),
                'description' => $this->text($get('description')),
                'status' => $this->status($get('status')),
                'reason_open' => $this->text($get('reason_open')),
                'code_open' => $this->text($get('code_open')),
                'work_date' => $date->toDateString(),
                'man_power' => $manPower,
                'crew' => $crew ? implode(', ', $crew) : null,
                'start_at' => $start?->toDateTimeString(),
                'end_at' => $end?->toDateTimeString(),
                'man_hour' => $manHours,
            ];
        }

        return $out;
    }

    /** A formula cell (Total MP, Actual MHRS, ...) is read as the value Excel last calculated, never as formula text. */
    private function value(Cell $cell): mixed
    {
        if (! $cell->isFormula()) {
            return $cell->getValue();
        }
        $cached = $cell->getOldCalculatedValue();

        return $cached === null || (is_string($cached) && str_starts_with($cached, '#')) ? null : $cached;
    }

    /** @return array<string, int|array<int, int>> canonical field => 1-based column index (mp_names: several) */
    private function mapHeader(Worksheet $sheet, int $row, int $highestCol): array
    {
        $slugs = [];
        for ($c = 1; $c <= $highestCol; $c++) {
            $value = $sheet->getCell([$c, $row])->getValue();
            if (is_string($value) && trim($value) !== '') {
                $slugs[Str::slug($value, '_')] = $c;
            }
        }

        $map = [];
        // "MP 1" ... "MP 5" hold the names of the people who did the job: their count is the man power
        foreach ($slugs as $slug => $col) {
            if (preg_match('/^mp_\d+$/', $slug)) {
                $map['mp_names'][] = $col;
            }
        }
        foreach (self::ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (isset($slugs[$alias]) && ! isset($map[$field])) {
                    $map[$field] = $slugs[$alias];
                }
            }
        }

        return $map;
    }

    /** @return array{0: ?string, 1: ?string} document number and WO / DMI / NSRDI / CML */
    private function document(callable $get, string $sheetName): array
    {
        foreach (['wo' => 'WO', 'dmi' => 'DMI', 'nsrdi' => 'NSRDI'] as $field => $type) {
            $no = $this->docNo($get($field));
            if ($no !== null) {
                return [$no, $type];
            }
        }

        $no = $this->docNo($get('doc_no'));
        if ($no === null) {
            return [null, null];
        }

        $declared = strtoupper((string) $this->text($get('doc_type')));
        $type = match (true) {
            str_contains($declared, 'NSRDI') => 'NSRDI',
            str_contains($declared, 'DMI') => 'DMI',
            str_contains($declared, 'CML') => 'CML',
            $declared === 'WO' || str_contains($declared, 'WORK') => 'WO',
            default => $this->typeFromSheet($sheetName),
        };

        return [$no, $type];
    }

    private function typeFromSheet(string $name): ?string
    {
        $n = strtoupper($name);

        return match (true) {
            str_contains($n, 'NSRDI') => 'NSRDI',
            str_contains($n, 'DMI') => 'DMI',
            str_contains($n, 'CML') => 'CML',
            (bool) preg_match('/\bWO\b/', $n) => 'WO',
            default => null,
        };
    }

    /** Document numbers: trimmed, upper case, and no ".0" tail when Excel stored them as numbers. */
    public static function normalizeDocNo(mixed $value): ?string
    {
        return (new self)->docNo($value);
    }

    private function docNo(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }
        $text = strtoupper(trim(preg_replace('/\s+/', '', (string) $value)));
        $text = ltrim($text, "'");
        if (preg_match('/^\d+\.0+$/', $text)) {
            $text = preg_replace('/\.0+$/', '', $text);
        }

        return $text === '' || in_array($text, ['-', '--', '---', 'N/A', 'NA'], true) ? null : $text;
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' || $text === '---' ? null : $text;
    }

    private function number(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $n = str_replace(',', '.', trim($value));

            return is_numeric($n) ? (float) $n : null;
        }

        return null;
    }

    private function status(mixed $value): ?string
    {
        $s = strtoupper(trim((string) $value));
        if ($s === '') {
            return null;
        }

        return match (true) {
            str_contains($s, 'CLOSE'), $s === 'DONE', $s === 'SELESAI' => 'Closed',
            str_contains($s, 'OPEN'), str_contains($s, 'HOLD'), str_contains($s, 'PENDING') => 'Open',
            default => null,
        };
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            }

            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} a finish at or before the start rolls into the next day */
    private function window(Carbon $date, mixed $start, mixed $end): array
    {
        $s = $this->clock($start);
        $e = $this->clock($end);
        if ($s === null || $e === null) {
            return [null, null];
        }

        $startAt = $date->copy()->setTime($s[0], $s[1]);
        $endAt = $date->copy()->setTime($e[0], $e[1]);
        if ($endAt->lte($startAt)) {
            $endAt->addDay();
        }

        return [$startAt, $endAt];
    }

    /** @return array{0: int, 1: int}|null hour and minute from an Excel time, a datetime or text like 08:30 / 08.30 / 0830 */
    private function clock(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return [(int) $value->format('H'), (int) $value->format('i')];
        }
        if (is_numeric($value)) {
            $f = (float) $value;
            if ($f >= 0 && $f < 1.0) {            // Excel time of day
                $minutes = (int) round($f * 1440);

                return [intdiv($minutes, 60) % 24, $minutes % 60];
            }
            if ($f >= 1 && $f < 24) {                 // typed as 8.3 / 22.00 / 14.45 (hour.minutes)
                $h = (int) floor($f);

                return $this->hm($h, (int) round(($f - $h) * 100));
            }
            if ($f >= 100 && $f < 2400 && floor($f) === $f) {   // 830 / 1730
                return $this->hm(intdiv((int) $f, 100), (int) $f % 100);
            }
            if ($f > 2400) {                       // Excel datetime
                $minutes = (int) round(($f - floor($f)) * 1440);

                return [intdiv($minutes, 60) % 24, $minutes % 60];
            }

            return null;
        }
        if (preg_match('/^(\d{1,2})\s*[:.]\s*(\d{2})/', trim((string) $value), $m)) {
            return $this->hm((int) $m[1], (int) $m[2]);
        }
        if (preg_match('/^(\d{2})(\d{2})$/', trim((string) $value), $m)) {
            return $this->hm((int) $m[1], (int) $m[2]);
        }

        return null;
    }

    private function hm(int $h, int $m): ?array
    {
        return $h <= 23 && $m <= 59 ? [$h, $m] : null;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function merge(array $rows): array
    {
        $merged = [];
        foreach ($rows as $row) {
            if ($row['doc_type'] === null) {
                $merged[] = $row;

                continue;
            }
            $key = $row['doc_type'].'|'.$row['doc_no'];
            if (! isset($merged[$key])) {
                $merged[$key] = $row;

                continue;
            }
            $m = &$merged[$key];
            $m['man_hour'] = ($m['man_hour'] !== null || $row['man_hour'] !== null) ? round((float) $m['man_hour'] + (float) $row['man_hour'], 2) : null;
            $m['man_power'] = max((int) $m['man_power'], (int) $row['man_power']) ?: null;
            $m['crew'] = implode(', ', CrewWriter::refs(trim(($m['crew'] ?? '').', '.($row['crew'] ?? ''), ', '))) ?: null;
            $m['start_at'] = collect([$m['start_at'], $row['start_at']])->filter()->min();
            $m['end_at'] = collect([$m['end_at'], $row['end_at']])->filter()->max();
            if ($row['status'] === 'Open' || $m['status'] === null) {
                $m['status'] = $row['status'] ?? $m['status'];
            }
            foreach (['station', 'plan_station', 'operator', 'ata', 'description', 'reason_open', 'code_open'] as $f) {
                $m[$f] ??= $row[$f];
            }
            unset($m);
        }

        return array_values($merged);
    }
}
