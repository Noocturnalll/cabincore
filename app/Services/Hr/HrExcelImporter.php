<?php

namespace App\Services\Hr;

use App\Models\Division;
use App\Models\Employee;
use App\Models\RegistryRecord;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One-off / repeatable imports from the HR Excel files.
 *
 *  employees  "DATABASE KARYAWAN ..." sheet "DATABASE": id, name, station, job title, gender, supervisor, start date.
 *             Only fields the app uses are read: ID card, family card, tax and insurance numbers are NOT imported.
 *  pasban     "Form Pendataan Pas Bandara (Responses)": the latest answer of each person becomes a PAS record;
 *             a passport number and expiry also go onto the employee.
 *
 * Re-running updates in place (matched on employee ID), so a refreshed file is safe to import again.
 */
class HrExcelImporter
{
    /** @var array<string, int|null> division name => id */
    private array $divisionIds = [];

    /** @return array{read: int, created: int, updated: int, no_division: array<string, int>} */
    public function importEmployees(string $path, bool $dryRun = false): array
    {
        $ws = $this->sheet($path, ['DATABASE', 'DATABASE ']);
        $rows = $this->table($ws, ['NO ID', 'NAMA']);

        $stats = ['read' => 0, 'created' => 0, 'updated' => 0, 'no_division' => []];
        foreach ($rows as $r) {
            $id = $this->id($r['no_id'] ?? null);
            $name = trim((string) ($r['nama'] ?? ''));
            if (! $id || $name === '') {
                continue;
            }
            $stats['read']++;

            $title = trim((string) ($r['jabatan'] ?? ''));
            $divisionId = $this->divisionFor($title);
            if (! $divisionId) {
                $stats['no_division'][$title ?: '(kosong)'] = ($stats['no_division'][$title ?: '(kosong)'] ?? 0) + 1;
            }

            $employee = Employee::withTrashed()->firstOrNew(['nik' => $id]);
            $isNew = ! $employee->exists;
            $employee->fill([
                'name' => $name,
                'station' => strtoupper(trim((string) ($r['station'] ?? ''))) ?: null,
                'job_title' => $title ?: null,
                'gender' => in_array(strtoupper(trim((string) ($r['jenis_kelamin'] ?? ''))), ['L', 'P'], true) ? strtoupper(trim((string) $r['jenis_kelamin'])) : null,
                'directorate' => trim((string) ($r['direktorat'] ?? '')) ?: null,
                'supervisor_name' => trim((string) ($r['atasan_langsung_pic'] ?? $r['atasan_langsung'] ?? '')) ?: null,
                'join_date' => $this->date($r['tmt'] ?? null),
                'division_id' => $divisionId ?? $employee->division_id,
                'status' => 'Aktif',
            ]);
            if ($isNew) {
                $employee->contract_type = 'PKWT';   // the file has no contract data: to be filled in
            }

            if (! $dryRun) {
                $employee->deleted_at = null;
                $employee->save();
            }
            $stats[$isNew ? 'created' : 'updated']++;
        }
        arsort($stats['no_division']);

        return $stats;
    }

    /** @return array{read: int, people: int, pas: int, passports: int, unknown_employees: int} */
    public function importPasban(string $path, bool $dryRun = false): array
    {
        $ws = $this->sheet($path, null);
        $rows = $this->table($ws, ['ID', 'NAMA']);

        // latest answer per person
        $latest = [];
        foreach ($rows as $r) {
            $id = $this->id($r['id'] ?? null);
            if (! $id) {
                continue;
            }
            $stamp = $this->timestamp($r['timestamp'] ?? null);
            if (! isset($latest[$id]) || $stamp >= $latest[$id]['stamp']) {
                $latest[$id] = ['stamp' => $stamp, 'row' => $r];
            }
        }

        $stats = ['read' => count($rows), 'people' => count($latest), 'pas' => 0, 'passports' => 0, 'unknown_employees' => 0];
        foreach ($latest as $id => ['row' => $r]) {
            $employee = Employee::where('nik', $id)->first();
            if (! $employee) {
                $stats['unknown_employees']++;
            }

            $codes = $this->pasCodes($r['kode_pas_bandara'] ?? null);
            $until = $this->date($r['tanggal_exp_pas_bandara'] ?? null);
            if ($codes || $until) {
                $stats['pas']++;
                if (! $dryRun) {
                    $this->savePas($id, $r, $codes, $until, $employee);
                }
            }

            $passportNo = trim((string) ($r['nomor_passport'] ?? ''));
            $passportExpiry = $this->date($r['tanggal_exp_passport'] ?? null);
            if ($employee && $passportNo !== '' && $passportExpiry) {
                $stats['passports']++;
                if (! $dryRun) {
                    $employee->update(['passport_no' => $passportNo, 'passport_expiry' => $passportExpiry]);
                }
            }
        }

        return $stats;
    }

    private function savePas(string $id, array $r, string $codes, ?string $until, ?Employee $employee): void
    {
        $note = trim((string) ($r['status_pasban'] ?? ''));
        $data = [
            'holder' => trim((string) ($r['nama'] ?? '')),
            'nik' => $id,
            'airport' => strtoupper(trim((string) ($r['sta'] ?? ''))),
            'codes' => $codes ?: null,
            'valid_until' => $until,
            'notes' => $note ?: null,
        ];

        $record = RegistryRecord::where('module', 'pas')->where('data->nik', $id)->first() ?? new RegistryRecord(['module' => 'pas']);
        $record->fill([
            'division_id' => $employee?->division_id ?? $record->division_id,
            'title' => $data['holder'],
            'due_date' => $until,
            'data' => array_merge($record->data ?? [], $data),
        ])->save();
    }

    /** "Ad,P" / "AdP" / "A, P" / "P U" -> "AD, P" / "AD, P" / "A, P" / "PU" */
    public function pasCodes(?string $raw): string
    {
        $text = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $raw));
        if ($text === '') {
            return '';
        }

        $codes = config('hr.pasban_codes');
        $found = [];
        $i = 0;
        while ($i < strlen($text)) {
            $matched = false;
            foreach ($codes as $code) {
                if (substr($text, $i, strlen($code)) === $code) {
                    $found[] = $code;
                    $i += strlen($code);
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return strtoupper(trim((string) $raw));   // unknown letters: keep what the person typed
            }
        }

        return implode(', ', array_values(array_unique($found)));
    }

    public function divisionFor(string $jobTitle): ?int
    {
        $title = strtoupper($jobTitle);
        foreach (config('hr.division_rules') as $division => $words) {
            foreach ($words as $word) {
                if (preg_match('/(?<![A-Z0-9])'.preg_quote($word, '/').'(?![A-Z0-9])/', $title)) {
                    return $this->divisionIds[$division] ??= Division::where('name', $division)->value('id');
                }
            }
        }

        return null;
    }

    private function sheet(string $path, ?array $names): Worksheet
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if ($names) {
            $reader->setLoadSheetsOnly($names);
        }
        $book = $reader->load($path);

        return $book->getSheet(0);
    }

    /**
     * Rows as header-slug => value, using the first row (within the top 10) that holds all $mustHave headers.
     *
     * @param  array<int, string>  $mustHave
     * @return array<int, array<string, mixed>>
     */
    private function table(Worksheet $ws, array $mustHave): array
    {
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        $need = array_map(fn ($h) => Str::slug($h, '_'), $mustHave);

        for ($r = 1; $r <= min(10, $ws->getHighestDataRow()); $r++) {
            $map = [];
            for ($c = 1; $c <= $highestCol; $c++) {
                $v = $ws->getCell([$c, $r])->getValue();
                if (is_string($v) && trim($v) !== '') {
                    $map[$c] = Str::slug(preg_replace('/\(.*$/', '', $v), '_');
                }
            }
            if (! array_diff($need, $map)) {
                $out = [];
                for ($row = $r + 1; $row <= $ws->getHighestDataRow(); $row++) {
                    $item = [];
                    foreach ($map as $c => $slug) {
                        $item[$slug] ??= $ws->getCell([$c, $row])->getValue();
                    }
                    $out[] = $item;
                }

                return $out;
            }
        }

        throw new \RuntimeException('Header tabel tidak ditemukan (dicari: '.implode(', ', $mustHave).').');
    }

    private function id(mixed $value): ?string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }
        $id = strtoupper(trim((string) $value));

        return $id === '' ? null : $id;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return $value > 20000 ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString() : null;
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function timestamp(mixed $value): string
    {
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateTimeString();
        }

        return (string) $value;
    }
}
