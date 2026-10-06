<?php

namespace App\Imports\Sheets;

use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DailyReportSheetImport implements OnEachRow
{
    private const DATE_COLUMNS = [
        'date', 'refresh_date', 'report_date', 'due_date', 'plan_date', 'close_date',
    ];

    private const STRING_ID_COLUMNS = ['wo', 'no_doc', 'dmi_no', 'nsrdi'];

    private array $headers = [];

    private array $stats = ['read' => 0, 'skipped' => 0, 'saved' => 0];

    public function __construct(private string $type) {}

    public function onRow(Row $row): void
    {
        if ($this->headers === []) {
            $this->readHeader($row);

            return;
        }

        $data = $this->readRow($row);

        $reg = $data['ac_reg'] ?? $data['reg'] ?? null;
        if ($reg === null) {
            $this->stats['skipped']++;

            return;
        }

        $this->stats['read']++;
        $this->store($this->resolveTarget($data), $data, $row->getIndex());
    }

    private function readHeader(Row $row): void
    {
        $it = $row->getDelegate()->getCellIterator();
        $it->setIterateOnlyExistingCells(true);

        $found = [];
        foreach ($it as $cell) {
            $text = trim((string) $cell->getValue());
            if ($text !== '') {
                $found[$cell->getColumn()] = Str::slug($text, '_');
            }
        }

        $values = array_values($found);
        $hasDate = array_intersect($values, ['date', 'refresh_date']) !== [];
        $hasReg = array_intersect($values, ['ac_reg', 'reg']) !== [];

        Log::info("Membaca baris ke-{$row->getIndex()} untuk header sheet {$this->type}", [
            'found' => $found,
            'hasDate' => $hasDate,
            'hasReg' => $hasReg,
        ]);

        if ($hasDate && $hasReg) {
            $this->headers = $found;
        }
    }

    private function readRow(Row $row): array
    {
        $data = [];
        $it = $row->getDelegate()->getCellIterator();
        $it->setIterateOnlyExistingCells(true);

        foreach ($it as $cell) {
            $key = $this->headers[$cell->getColumn()] ?? null;
            if ($key === null) {
                continue;
            }
            $data[$key] = $this->normalize($key, $this->cellValue($cell));
        }

        return $data;
    }

    private function cellValue(Cell $cell): mixed
    {
        if ($cell->isFormula()) {
            $cached = $cell->getOldCalculatedValue();
            if ($cached === null || (is_string($cached) && str_starts_with($cached, '#'))) {
                return null;
            }

            return $cached;
        }

        return $cell->getValue();
    }

    private function normalize(string $key, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if ($value === null) {
            return null;
        }

        if (in_array($key, self::DATE_COLUMNS, true)) {
            return $this->parseDate($value, $key);
        }

        if (in_array($key, self::STRING_ID_COLUMNS, true)) {
            return trim((string) $value);
        }

        if ($key === 'man_hours' && is_string($value)) {
            $number = str_replace(',', '.', $value);

            return is_numeric($number) ? (float) $number : null;
        }

        return $value;
    }

    private function parseDate(mixed $value, string $key): ?string
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->toDateString();
            }

            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            }

            $text = trim((string) $value);

            if (preg_match('/^\d{1,2}-[A-Za-z]{3}-(\d{2}|\d{4})$/', $text, $m)) {
                $format = strlen($m[1]) === 2 ? 'j-M-y' : 'j-M-Y';

                return Carbon::createFromFormat($format, $text)->toDateString();
            }

            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $text, $m)) {
                return Carbon::createFromFormat('d/m/Y', $text)->toDateString();
            }

            return Carbon::parse($text)->toDateString();
        } catch (\Throwable $e) {
            Log::warning("DailyReport import: tanggal tidak terbaca di kolom {$key}: ".json_encode($value));

            return null;
        }
    }

    private function resolveTarget(array $data): string
    {
        if ($this->type === 'UNPLANNED') {
            return 'unplanned_'.strtolower($data['doc_type'] ?? 'unknown');
        }

        return match ($this->type) {
            'WO' => 'wo',
            'DMI' => 'dmi',
            'NSRDI' => 'nsrdi',
            'CML' => 'cml',
        };
    }

    private array $clearedDates = [];

    private function store(string $target, array $data, int $rowIndex): void
    {
        match ($target) {
            'nsrdi' => $this->storeNsrdi($data, 'dja'),
            'unplanned_nsrdi' => $this->storeNsrdi($data, 'unplanned'),
            'wo' => $this->storeWo($data, false),
            'unplanned_wo' => $this->storeWo($data, true),
            'dmi' => $this->storeDmi($data, false),
            'unplanned_dmi' => $this->storeDmi($data, true),
            'cml' => $this->storeCml($data),
            default => $this->stats['unhandled'] = ($this->stats['unhandled'] ?? 0) + 1,
        };
    }

    private function storeNsrdi(array $d, string $source): void
    {
        if ($source === 'dja') {
            $planDate = $d['plan_date'] ?? null;
            $existing = NsrdiLog::where('nsrdi_no', $d['nsrdi'] ?? null)
                ->when($planDate, fn ($q) => $q->where('plan_date', $planDate), fn ($q) => $q->whereNull('plan_date'))
                ->first();

            $row = [
                'work_group' => $d['wg'] ?? null,
                'aircraft_registration' => $d['ac_reg'] ?? ($existing->aircraft_registration ?? null),
                'nsrdi_number' => $d['nsrdi'] ?? null,
                'description' => $d['finding_description'] ?? null,
                'category' => $d['category'] ?? null,
                'refresh_date' => $d['refresh_date'] ?? null,
                'report_date' => $d['report_date'] ?? null,
                'due_date' => $d['due_date'] ?? null,
                'part_number' => $d['part_number'] ?? null,
                'part_description' => $d['part_description'] ?? null,
                'defer' => $d['defer'] ?? null,
                'aoc' => $d['aoc'] ?? null,
                'type' => $d['type'] ?? null,
                'plan_station' => $d['plan_sta'] ?? null,
                'plan_date' => $planDate,
                'remarks' => $d['remarks'] ?? null,
                'status' => $this->status($d['status'] ?? null),
                'close_date' => $d['close_date'] ?? null,
                'act_station' => $d['act_sta'] ?? null,
                'reason_open' => $d['reason_open'] ?? null,
                'code_open' => $d['code_open'] ?? null,
                'hold_remarks' => $d['reason_open'] ?? null,
                'hold_reason_category' => $d['code_open'] ?? null,
            ];

            $acReg = $row['aircraft_registration'];
            if (empty($acReg)) {
                return;
            }

        } else {
            $planDate = $d['date'] ?? null;
            $existing = NsrdiLog::where('nsrdi_no', $d['no_doc'] ?? null)
                ->when($planDate, fn ($q) => $q->where('plan_date', $planDate), fn ($q) => $q->whereNull('plan_date'))
                ->first();

            $row = [
                'aircraft_registration' => $d['reg'] ?? ($existing->aircraft_registration ?? null),
                'nsrdi_number' => $d['no_doc'] ?? null,
                'description' => $d['deffect_description'] ?? null,
                'aoc' => $d['aoc'] ?? null,
                'defer' => $d['status_material'] ?? null,
                'act_station' => $d['sta'] ?? null,
                'plan_date' => $planDate,
                'status' => $this->status($d['status'] ?? null),
                'remarks' => $d['remarks'] ?? null,
            ];

            $acReg = $row['aircraft_registration'];
            if (empty($acReg)) {
                return;
            }
        }

        $djaId = null;
        if ($source === 'dja' && $planDate) {
            $dja = DailyJobAssignment::firstOrCreate(
                ['aircraft_registration' => $acReg, 'date' => $planDate],
                ['job_type' => 'AOC/NSRDI', 'station' => $row['act_station'] ?? 'Unknown', 'task_id' => 'IMPORTED']
            );
            $djaId = $dja->id;
        }

        if ($existing) {
            $row['dja_id'] = $existing->dja_id ?? $djaId;
            $row['is_submitted'] = true;
            $row['import_source'] = $source;
            $existing->update($row);
        } else {
            $row['dja_id'] = $djaId;
            $row['is_submitted'] = true;
            $row['import_source'] = $source;
            NsrdiLog::create($row);
        }
        $this->stats['saved']++;
    }

    private function storeWo(array $row, bool $isUnplanned): void
    {
        $woNumber = $row['wo'] ?? $row['no_doc'] ?? null;
        if (! $woNumber) {
            return;
        }

        $date = $row['date'] ?? null;
        $existing = WoLog::where('wo_number', $woNumber)
            ->when($date, fn ($q) => $q->where('date', $date), fn ($q) => $q->whereNull('date'))
            ->first();

        $acReg = $row['ac_reg'] ?? $row['reg'] ?? ($existing->aircraft_registration ?? null);
        if (empty($acReg)) {
            return;
        }
        $djaId = null;
        if (! $isUnplanned && $date) {
            $dja = DailyJobAssignment::firstOrCreate(
                ['aircraft_registration' => $acReg, 'date' => $date],
                ['job_type' => 'R01/WO', 'station' => $row['act_sta'] ?? $row['sta'] ?? 'Unknown', 'task_id' => 'IMPORTED']
            );
            $djaId = $dja->id;
        }

        $data = [
            'aircraft_registration' => $acReg,
            'act_station' => $row['act_sta'] ?? $row['sta'] ?? ($existing->act_station ?? null),
            'plan_station' => $row['plan_sta'] ?? ($existing->plan_station ?? null),
            'description' => $row['wo_description'] ?? $row['defect_description'] ?? ($existing->description ?? null),
            'status' => ucfirst(strtolower($row['status'] ?? 'Closed')),
            'date' => $date ?? ($existing->date ?? null),
            'operator' => $row['operator'] ?? $row['aoc'] ?? ($existing->operator ?? null),
            'work_group' => $row['wg'] ?? ($existing->work_group ?? null),
            'wo_category' => $row['wo_cat'] ?? ($existing->wo_category ?? null),
            'pn_picklist' => $row['pn_picklist_no_picklist'] ?? $row['pn_picklist'] ?? ($existing->pn_picklist ?? null),
            'man_hour' => $row['man_hours'] ?? ($existing->man_hour ?? null),
            'type' => $row['type'] ?? ($existing->type ?? null),
            'remarks_ppc_to_lm' => $row['remarks_ppc_to_lm'] ?? ($existing->remarks_ppc_to_lm ?? null),
            'hold_reason_category' => $row['code_open'] ?? ($existing->hold_reason_category ?? null),
            'hold_remarks' => $row['reason_open'] ?? $row['remarks'] ?? ($existing->hold_remarks ?? null),
        ];

        if ($existing) {
            $data['dja_id'] = $existing->dja_id ?? $djaId;
            $data['is_submitted'] = true;
            $existing->update($data);
        } else {
            $data['dja_id'] = $djaId;
            $data['wo_number'] = $woNumber;
            $data['is_submitted'] = true;
            WoLog::create($data);
        }
        $this->stats['saved']++;
    }

    private function storeDmi(array $row, bool $isUnplanned): void
    {
        $dmiNumber = $row['dmi_no'] ?? $row['no_doc'] ?? null;
        if (! $dmiNumber) {
            return;
        }

        $date = $row['date'] ?? null;
        $existing = DmiLog::where('dmi_number', $dmiNumber)
            ->when($date, fn ($q) => $q->where('date', $date), fn ($q) => $q->whereNull('date'))
            ->first();

        $acReg = $row['reg'] ?? $row['ac_reg'] ?? ($existing->aircraft_registration ?? null);
        if (empty($acReg)) {
            return;
        }
        $djaId = null;
        if (! $isUnplanned && $date) {
            $dja = DailyJobAssignment::firstOrCreate(
                ['aircraft_registration' => $acReg, 'date' => $date],
                ['job_type' => 'DMI', 'station' => $row['act_sta'] ?? $row['sta'] ?? 'Unknown', 'task_id' => 'IMPORTED']
            );
            $djaId = $dja->id;
        }

        $data = [
            'aircraft_registration' => $acReg,
            'plan_station' => $row['plan_sta'] ?? ($existing->plan_station ?? null),
            'act_station' => $row['sta'] ?? ($existing->act_station ?? null),
            'description' => $row['dmi_description'] ?? $row['defect_description'] ?? ($existing->description ?? null),
            'status' => ucfirst(strtolower($row['status'] ?? 'Closed')),
            'date' => $date ?? ($existing->date ?? null),
            'pn_required' => $row['pn_required'] ?? ($existing->pn_required ?? null),
            'dmi_category' => $row['dmi_cat'] ?? ($existing->dmi_category ?? null),
            'category' => $row['cat'] ?? ($existing->category ?? null),
            'remarks' => $row['remark'] ?? $row['remarks'] ?? ($existing->remarks ?? null),
        ];

        if ($existing) {
            $data['dja_id'] = $existing->dja_id ?? $djaId;
            $data['is_submitted'] = true;
            $existing->update($data);
        } else {
            $data['dja_id'] = $djaId;
            $data['dmi_number'] = $dmiNumber;
            $data['is_submitted'] = true;
            DmiLog::create($data);
        }
        $this->stats['saved']++;
    }

    private function storeCml(array $row): void
    {
        $acReg = $row['ac_reg'] ?? $row['reg'] ?? null;
        if (empty($acReg)) {
            return;
        }

        CmlLog::create([
            'dja_id' => null,
            'date' => $row['date'] ?? null,
            'operator' => $row['operator'] ?? null,
            'aircraft_registration' => $acReg,
            'ac_status' => $row['ac_stat'] ?? $row['a_c_stat'] ?? null,
            'station' => $row['sta'] ?? null,
            'doc_type' => 'CML',
            'no_doc' => $row['no_doc'] ?? null,
            'description' => $row['action_taken'] ?? $row['action_taken_reason'] ?? null,
            'status' => ucfirst(strtolower($row['status'] ?? 'Closed')),
        ]);
        $this->stats['saved']++;
    }

    private function status(?string $value): ?string
    {
        return $value === null ? null : ucfirst(strtolower($value));
    }

    public function __destruct()
    {
        if ($this->headers === []) {
            Log::warning("DailyReport import [{$this->type}]: baris header (DATE + REG) tidak ditemukan.");
        }
        Log::info("DailyReport import [{$this->type}] selesai", $this->stats);
    }
}
