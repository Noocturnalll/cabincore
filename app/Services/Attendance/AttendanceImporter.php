<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\RosterEntry;
use App\Models\ShiftCode;
use App\Services\Master\MasterSettings;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports attendance (presensi) from Excel. The real export format is not fixed yet, so two layouts are read:
 *
 *  list   one row per employee per day: ID, name, date, status, clock in, clock out (columns found by header name)
 *  matrix one row per employee, one column per date, each cell a status code (H, S, C, I, A, OFF ...)
 *
 * Lateness is worked out against the shift the roster gave that person that day (code -> start time of the
 * Shift Codes list), beyond the tolerance set in Data Master > Aturan Disiplin. Re-importing replaces the same person/day.
 */
class AttendanceImporter
{
    private const ALIASES = [
        'nik' => ['nik', 'id', 'no_id', 'id_karyawan', 'nomor_id', 'nip', 'employee_id', 'id_no'],
        'name' => ['nama', 'name', 'employee', 'karyawan', 'nama_karyawan'],
        'date' => ['tanggal', 'date', 'tgl'],
        'status' => ['status', 'keterangan', 'ket', 'kehadiran', 'status_kehadiran'],
        'in' => ['jam_masuk', 'masuk', 'check_in', 'clock_in', 'in', 'scan_masuk', 'jam_datang'],
        'out' => ['jam_pulang', 'pulang', 'check_out', 'clock_out', 'out', 'scan_pulang'],
        'station' => ['sta', 'station', 'stasiun', 'lokasi'],
    ];

    private const SYNONYMS = [
        'H' => ['HADIR', 'PRESENT', 'MASUK', 'HDR'],
        'S' => ['SAKIT', 'SICK', 'SKT'],
        'C' => ['CUTI', 'LEAVE', 'CT'],
        'I' => ['IZIN', 'IJIN', 'PERMIT'],
        'A' => ['ALPA', 'ALPHA', 'ABSEN', 'ABSENT', 'MANGKIR', 'TANPA KETERANGAN'],
        'OFF' => ['LIBUR', 'OFF DAY', 'DAY OFF'],
        'P00' => ['TRAINING', 'DIKLAT', 'PELATIHAN'],
    ];

    /** @var array<string, array{start: ?string, end: ?string}> shift code => time range parts */
    private array $times = [];

    /** @return array{read: int, saved: int, skipped: int, unknown_status: array<string, int>, from: ?string, to: ?string, late: int} */
    public function import(string $path, bool $dryRun = false): array
    {
        @ini_set('memory_limit', '1G');

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $ws = $reader->load($path)->getSheet(0);

        foreach (ShiftCode::all() as $c) {
            [$s, $e] = array_pad(explode('-', (string) $c->time_range), 2, null);
            $this->times[$c->code] = ['start' => $s ? $this->hm($s) : null, 'end' => $e ? $this->hm($e) : null];
        }

        $rows = $this->readList($ws) ?? $this->readMatrix($ws);
        if ($rows === null) {
            throw new \RuntimeException('Format presensi tidak dikenali: butuh kolom ID, tanggal, dan status/jam masuk (daftar), atau baris karyawan dengan kolom tanggal (matriks).');
        }

        $groups = app(MasterSettings::class)->attendanceGroups();
        $tolerance = (int) app(MasterSettings::class)->rule('LATE_TOLERANCE_MIN', 10);
        $earlyTolerance = (int) app(MasterSettings::class)->rule('EARLY_LEAVE_TOLERANCE_MIN', 10);

        $stats = ['read' => 0, 'saved' => 0, 'skipped' => 0, 'unknown_status' => [], 'from' => null, 'to' => null, 'late' => 0];
        $out = [];
        foreach ($rows as $r) {
            $stats['read']++;
            $code = $this->statusCode($r['status'], $r['in'], $groups);
            if ($code === null) {
                $stats['skipped']++;
                $label = strtoupper(trim((string) $r['status'])) ?: '(kosong)';
                $stats['unknown_status'][$label] = ($stats['unknown_status'][$label] ?? 0) + 1;

                continue;
            }

            $roster = RosterEntry::where('employee_id', $r['nik'])->whereDate('work_date', $r['date'])->first();
            $times = $roster ? ($this->times[$roster->shift_code] ?? null) : null;
            $late = $earlyMin = 0;
            if ($groups[$code] === 'hadir' && $times) {
                $late = $this->minutesLate($times['start'], $r['in'], $tolerance);
                $earlyMin = $this->minutesEarly($times['start'], $times['end'], $r['out'], $earlyTolerance);
            }
            if ($late > 0) {
                $stats['late']++;
            }

            $out[] = [
                'employee_nik' => $r['nik'], 'employee_name' => $r['name'] ?: $roster?->employee_name,
                'station' => $r['station'] ?: $roster?->station, 'work_date' => $r['date'],
                'status_code' => $code, 'status_group' => $groups[$code] ?? 'hadir',
                'scheduled_start' => $times['start'] ?? null, 'clock_in' => $r['in'], 'clock_out' => $r['out'],
                'late_minutes' => $late, 'early_leave_minutes' => $earlyMin, 'source' => 'import',
            ];
            $stats['saved']++;
            $stats['from'] = $stats['from'] === null ? $r['date'] : min($stats['from'], $r['date']);
            $stats['to'] = $stats['to'] === null ? $r['date'] : max($stats['to'], $r['date']);
        }

        if (! $dryRun && $out) {
            $now = now();
            foreach (array_chunk($out, 400) as $chunk) {
                AttendanceRecord::upsert(
                    array_map(fn ($o) => $o + ['created_at' => $now, 'updated_at' => $now], $chunk),
                    ['employee_nik', 'work_date'],
                    ['employee_name', 'station', 'status_code', 'status_group', 'scheduled_start', 'clock_in', 'clock_out', 'late_minutes', 'early_leave_minutes', 'source', 'updated_at']
                );
            }
        }
        arsort($stats['unknown_status']);

        return $stats;
    }

    /** @return array<int, array<string, mixed>>|null null when the sheet is not in list layout */
    private function readList(Worksheet $ws): ?array
    {
        [$row, $map] = $this->header($ws, ['nik', 'date']);
        if (! $map) {
            return null;
        }
        if (! isset($map['status']) && ! isset($map['in'])) {
            return null;
        }

        $out = [];
        for ($r = $row + 1, $last = $ws->getHighestDataRow(); $r <= $last; $r++) {
            $get = fn (string $k) => isset($map[$k]) ? $ws->getCell([$map[$k], $r])->getValue() : null;
            $nik = $this->id($get('nik'));
            $date = $this->date($get('date'));
            if (! $nik || ! $date) {
                continue;
            }
            $out[] = [
                'nik' => $nik, 'name' => trim((string) $get('name')) ?: null, 'date' => $date, 'status' => $get('status'),
                'in' => $this->clock($get('in')), 'out' => $this->clock($get('out')),
                'station' => strtoupper(trim((string) $get('station'))) ?: null,
            ];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>>|null */
    private function readMatrix(Worksheet $ws): ?array
    {
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        for ($r = 1; $r <= min(8, $ws->getHighestDataRow()); $r++) {
            $dates = [];
            for ($c = 1; $c <= $highestCol; $c++) {
                $raw = $ws->getCell([$c, $r])->getValue();
                $isDate = (is_numeric($raw) && $raw > 30000) || (is_string($raw) && preg_match('/^\d{4}-\d{2}-\d{2}|^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}$/', trim($raw)));
                if ($isDate && ($d = $this->date($raw))) {
                    $dates[$c] = $d;
                }
            }
            if (count($dates) < 7) {
                continue;
            }

            $idCol = $nameCol = $staCol = null;
            for ($c = 1; $c < min(min(array_keys($dates)), 8); $c++) {
                $slug = Str::slug((string) $ws->getCell([$c, $r])->getValue(), '_');
                $idCol ??= in_array($slug, self::ALIASES['nik'], true) ? $c : null;
                $nameCol ??= in_array($slug, self::ALIASES['name'], true) ? $c : null;
                $staCol ??= in_array($slug, self::ALIASES['station'], true) ? $c : null;
            }
            if (! $idCol) {
                continue;
            }

            $out = [];
            for ($row = $r + 1, $last = $ws->getHighestDataRow(); $row <= $last; $row++) {
                $nik = $this->id($ws->getCell([$idCol, $row])->getValue());
                if (! $nik) {
                    continue;
                }
                foreach ($dates as $c => $date) {
                    $v = trim((string) $ws->getCell([$c, $row])->getValue());
                    if ($v === '') {
                        continue;
                    }
                    $out[] = [
                        'nik' => $nik, 'name' => $nameCol ? (trim((string) $ws->getCell([$nameCol, $row])->getValue()) ?: null) : null,
                        'date' => $date, 'status' => $v, 'in' => null, 'out' => null,
                        'station' => $staCol ? (strtoupper(trim((string) $ws->getCell([$staCol, $row])->getValue())) ?: null) : null,
                    ];
                }
            }

            return $out;
        }

        return null;
    }

    /** @return array{0: int, 1: array<string, int>} header row and field => column; empty map when $need is not all found */
    private function header(Worksheet $ws, array $need): array
    {
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        for ($r = 1; $r <= min(8, $ws->getHighestDataRow()); $r++) {
            $map = [];
            for ($c = 1; $c <= $highestCol; $c++) {
                $slug = Str::slug((string) $ws->getCell([$c, $r])->getValue(), '_');
                foreach (self::ALIASES as $field => $aliases) {
                    if (! isset($map[$field]) && in_array($slug, $aliases, true)) {
                        $map[$field] = $c;
                    }
                }
            }
            if (! array_diff($need, array_keys($map))) {
                return [$r, $map];
            }
        }

        return [0, []];
    }

    private function statusCode(mixed $status, ?string $clockIn, array $groups): ?string
    {
        $text = strtoupper(trim((string) $status));
        if ($text === '') {
            return $clockIn ? 'H' : null;
        }
        if (isset($groups[$text])) {
            return $text;
        }
        foreach (self::SYNONYMS as $code => $words) {
            if (in_array($text, $words, true) && isset($groups[$code])) {
                return $code;
            }
        }
        // a shift code in the cell (P40, M21 ...) means the person worked that shift
        if (preg_match('/^[PSM]\d{1,2}$/', $text) || isset($this->times[$text])) {
            return 'H';
        }

        return null;
    }

    private function minutesLate(?string $start, ?string $in, int $tolerance): int
    {
        if (! $start || ! $in) {
            return 0;
        }
        $diff = $this->diff($in, $start);   // positive: arrived after the start
        if ($diff > 12 * 60 || $diff < -12 * 60) {
            return 0;   // a different shift than scheduled; not a lateness
        }

        return $diff > $tolerance ? $diff : 0;
    }

    private function minutesEarly(?string $start, ?string $end, ?string $out, int $tolerance): int
    {
        if (! $end || ! $out || ! $start || $end <= $start) {
            return 0;   // overnight shifts are not judged on the way out
        }
        $diff = $this->diff($end, $out);   // positive: left before the end
        if ($diff > 6 * 60 || $diff < -12 * 60) {
            return 0;
        }

        return $diff > $tolerance ? $diff : 0;
    }

    /** Minutes from $b to $a (a - b), wrapping across midnight. */
    private function diff(string $a, string $b): int
    {
        [$ah, $am] = array_map('intval', explode(':', $a));
        [$bh, $bm] = array_map('intval', explode(':', $b));
        $d = ($ah * 60 + $am) - ($bh * 60 + $bm);
        if ($d > 12 * 60) {
            $d -= 24 * 60;
        } elseif ($d < -12 * 60) {
            $d += 24 * 60;
        }

        return $d;
    }

    private function hm(string $value): ?string
    {
        return preg_match('/(\d{1,2}):?(\d{2})/', trim($value), $m) ? sprintf('%02d:%02d', (int) $m[1], (int) $m[2]) : null;
    }

    private function clock(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }
        if (is_numeric($value)) {
            $f = fmod((float) $value, 1.0);
            $m = (int) round($f * 1440);

            return sprintf('%02d:%02d', intdiv($m, 60) % 24, $m % 60);
        }

        return $this->hm((string) $value);
    }

    private function id(mixed $value): ?string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }
        $id = strtoupper(trim((string) $value));

        return $id !== '' && preg_match('/\d/', $id) ? $id : null;
    }

    private function date(mixed $value): ?string
    {
        try {
            if (is_numeric($value) && $value > 30000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }
            if (is_string($value) && trim($value) !== '') {
                return Carbon::parse(trim($value))->toDateString();
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
