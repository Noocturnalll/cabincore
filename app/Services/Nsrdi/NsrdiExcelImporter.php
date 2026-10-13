<?php

namespace App\Services\Nsrdi;

use App\Models\CapacityTarget;
use App\Models\DailyJobAssignment;
use App\Models\NsrdiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NsrdiExcelImporter
{
    /**
     * Import NSRDI workbook (both DJA Planned and Unplanned sheets, plus targets).
     *
     * @return array{
     *     total: int,
     *     dja_count: int,
     *     dja_cbm: int,
     *     dja_painting: int,
     *     dja_closed: int,
     *     dja_open: int,
     *     unplanned_count: int,
     *     unplanned_cbm: int,
     *     unplanned_painting: int,
     *     unplanned_closed: int,
     *     unplanned_open: int,
     *     dates: array<string, int>,
     *     stations: array<string, int>,
     *     targets_updated: int
     * }
     */
    public function import(string $path, bool $dryRun = false, ?string $forcedCategory = null): array
    {
        @ini_set('memory_limit', '1024M');

        if (! file_exists($path)) {
            throw new \InvalidArgumentException("File Excel tidak ditemukan: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $availableSheets = $reader->listWorksheetNames($path);

        $spreadsheet = $reader->load($path);

        $djaSheet = $this->findSheet($spreadsheet, ['DJA NSRDI', 'DJA AOC NSRDIL R01', 'DJA', 'PLANNED']);
        $unplanSheet = $this->findSheet($spreadsheet, ['UNPLAN NSRDI', 'UNPLANNED', 'UNPLAN']);
        $targetSheet = $this->findSheet($spreadsheet, ['TARGET', 'TARGETS']);

        if (! $djaSheet && ! $unplanSheet) {
            // Fallback to active/first sheet
            $djaSheet = $spreadsheet->getActiveSheet();
        }

        $stats = [
            'total' => 0,
            'dja_count' => 0,
            'dja_cbm' => 0,
            'dja_painting' => 0,
            'dja_closed' => 0,
            'dja_open' => 0,
            'unplanned_count' => 0,
            'unplanned_cbm' => 0,
            'unplanned_painting' => 0,
            'unplanned_closed' => 0,
            'unplanned_open' => 0,
            'dates' => [],
            'stations' => [],
            'targets_updated' => 0,
        ];

        DB::beginTransaction();

        try {
            if ($djaSheet) {
                $this->importDjaSheet($djaSheet, $stats, $dryRun, $forcedCategory);
            }

            if ($unplanSheet) {
                $this->importUnplanSheet($unplanSheet, $stats, $dryRun, $forcedCategory);
            }

            if ($targetSheet && ! $dryRun) {
                $stats['targets_updated'] = $this->syncCapacityTargets($targetSheet);
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $stats['total'] = $stats['dja_count'] + $stats['unplanned_count'];
        ksort($stats['dates']);

        return $stats;
    }

    private function findSheet($spreadsheet, array $candidates): ?Worksheet
    {
        foreach ($candidates as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet !== null) {
                return $sheet;
            }
        }

        return null;
    }

    private function importDjaSheet(Worksheet $sheet, array &$stats, bool $dryRun, ?string $forcedCategory): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $nsrdiNo = $this->cleanStr($sheet->getCell('D'.$r)->getValue());
            $acReg = $this->cleanStr($sheet->getCell('C'.$r)->getValue());

            if (! $nsrdiNo && ! $acReg) {
                continue;
            }

            $rawPlan = $sheet->getCell('O'.$r)->getValue() ?: $sheet->getCell('A'.$r)->getValue();
            $planDate = $this->parseDate($rawPlan);

            $actSta = $this->cleanStr($sheet->getCell('S'.$r)->getValue());
            $planSta = $this->cleanStr($sheet->getCell('N'.$r)->getValue());
            $station = $actSta ?: ($planSta ?: 'Unknown');

            $desc = $this->cleanStr($sheet->getCell('E'.$r)->getValue());
            $wg = $this->cleanStr($sheet->getCell('B'.$r)->getValue());
            $cat = $forcedCategory ?: $this->cleanStr($sheet->getCell('F'.$r)->getValue());
            $category = NsrdiLog::determineCategory($cat, $desc, $wg);

            $statusRaw = strtoupper((string) ($sheet->getCell('Q'.$r)->getValue() ?? 'OPEN'));
            $status = str_contains($statusRaw, 'CLOSE') ? 'Closed' : 'Open';
            $rawClose = $sheet->getCell('R'.$r)->getValue();
            $closeDate = $this->parseDate($rawClose) ?: ($status === 'Closed' ? $planDate : null);

            $codeOpen = $this->cleanStr($sheet->getCell('U'.$r)->getValue());
            $reasonOpen = $this->cleanStr($sheet->getCell('T'.$r)->getValue());

            $stats['dja_count']++;
            if ($category === 'PAINTING') {
                $stats['dja_painting']++;
            } else {
                $stats['dja_cbm']++;
            }

            if ($status === 'Closed') {
                $stats['dja_closed']++;
            } else {
                $stats['dja_open']++;
            }

            if ($planDate) {
                $stats['dates'][$planDate] = ($stats['dates'][$planDate] ?? 0) + 1;
            }
            if ($station) {
                $stats['stations'][$station] = ($stats['stations'][$station] ?? 0) + 1;
            }

            if ($dryRun) {
                continue;
            }

            $djaId = null;
            if ($planDate && $acReg) {
                $dja = DailyJobAssignment::firstOrCreate(
                    [
                        'aircraft_registration' => $acReg,
                        'date' => $planDate,
                        'task_id' => $nsrdiNo ?: 'NSRDI-'.$r,
                    ],
                    [
                        'job_type' => 'AOC/NSRDI',
                        'station' => $station,
                        'description' => $desc,
                    ]
                );
                $djaId = $dja->id;
            }

            $data = [
                'dja_id' => $djaId,
                'work_group' => $wg,
                'aircraft_registration' => $acReg,
                'nsrdi_number' => $nsrdiNo,
                'description' => $desc,
                'category' => $category,
                'report_date' => $this->parseDate($sheet->getCell('G'.$r)->getValue()),
                'due_date' => $this->parseDate($sheet->getCell('H'.$r)->getValue()),
                'part_number' => $this->cleanStr($sheet->getCell('I'.$r)->getValue()),
                'part_description' => $this->cleanStr($sheet->getCell('J'.$r)->getValue()),
                'defer' => $this->cleanStr($sheet->getCell('K'.$r)->getValue()),
                'aoc' => $this->cleanStr($sheet->getCell('L'.$r)->getValue()),
                'type' => $this->cleanStr($sheet->getCell('M'.$r)->getValue()),
                'plan_station' => $planSta,
                'plan_date' => $planDate,
                'remarks' => $this->cleanStr($sheet->getCell('P'.$r)->getValue()),
                'status' => $status,
                'close_date' => $closeDate,
                'act_station' => $actSta,
                'hold_reason_category' => $codeOpen,
                'hold_remarks' => $reasonOpen,
                'code_open' => $codeOpen,
                'reason_open' => $reasonOpen,
                'is_submitted' => true,
                'import_source' => 'dja',
            ];

            if ($nsrdiNo) {
                $existing = NsrdiLog::where('nsrdi_number', $nsrdiNo)
                    ->when($planDate, fn ($q) => $q->where('plan_date', $planDate), fn ($q) => $q->whereNull('plan_date'))
                    ->first();

                if ($existing) {
                    $existing->update($data);

                    continue;
                }
            }

            NsrdiLog::create($data);
        }
    }

    private function importUnplanSheet(Worksheet $sheet, array &$stats, bool $dryRun, ?string $forcedCategory): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $nsrdiNo = $this->cleanStr($sheet->getCell('D'.$r)->getValue());
            $acReg = $this->cleanStr($sheet->getCell('C'.$r)->getValue());

            if (! $nsrdiNo && ! $acReg) {
                continue;
            }

            $refreshDate = $this->parseDate($sheet->getCell('A'.$r)->getValue());
            $planDate = $this->parseDate($sheet->getCell('O'.$r)->getValue());
            $reportDate = $this->parseDate($sheet->getCell('G'.$r)->getValue()) ?: $refreshDate;

            $actSta = $this->cleanStr($sheet->getCell('S'.$r)->getValue());
            $planSta = $this->cleanStr($sheet->getCell('N'.$r)->getValue());
            $station = $actSta ?: ($planSta ?: 'Unknown');

            $desc = $this->cleanStr($sheet->getCell('E'.$r)->getValue());
            $wg = $this->cleanStr($sheet->getCell('B'.$r)->getValue());
            $cat = $forcedCategory ?: $this->cleanStr($sheet->getCell('F'.$r)->getValue());
            $category = NsrdiLog::determineCategory($cat, $desc, $wg);

            $statusRaw = strtoupper((string) ($sheet->getCell('Q'.$r)->getValue() ?? 'CLOSED'));
            $status = str_contains($statusRaw, 'CLOSE') ? 'Closed' : 'Open';
            $rawClose = $sheet->getCell('R'.$r)->getValue();
            $closeDate = $this->parseDate($rawClose) ?: ($status === 'Closed' ? ($planDate ?: $refreshDate) : null);

            $codeOpen = $this->cleanStr($sheet->getCell('U'.$r)->getValue());
            $reasonOpen = $this->cleanStr($sheet->getCell('T'.$r)->getValue());

            $stats['unplanned_count']++;
            if ($category === 'PAINTING') {
                $stats['unplanned_painting']++;
            } else {
                $stats['unplanned_cbm']++;
            }

            if ($status === 'Closed') {
                $stats['unplanned_closed']++;
            } else {
                $stats['unplanned_open']++;
            }

            $effectiveDate = $planDate ?: $refreshDate;
            if ($effectiveDate) {
                $stats['dates'][$effectiveDate] = ($stats['dates'][$effectiveDate] ?? 0) + 1;
            }
            if ($station) {
                $stats['stations'][$station] = ($stats['stations'][$station] ?? 0) + 1;
            }

            if ($dryRun) {
                continue;
            }

            $data = [
                'dja_id' => null,
                'refresh_date' => $refreshDate,
                'work_group' => $wg,
                'aircraft_registration' => $acReg,
                'nsrdi_number' => $nsrdiNo,
                'description' => $desc,
                'category' => $category,
                'report_date' => $reportDate,
                'due_date' => $this->parseDate($sheet->getCell('H'.$r)->getValue()),
                'part_number' => $this->cleanStr($sheet->getCell('I'.$r)->getValue()),
                'part_description' => $this->cleanStr($sheet->getCell('J'.$r)->getValue()),
                'defer' => $this->cleanStr($sheet->getCell('K'.$r)->getValue()),
                'aoc' => $this->cleanStr($sheet->getCell('L'.$r)->getValue()),
                'type' => $this->cleanStr($sheet->getCell('M'.$r)->getValue()),
                'plan_station' => $planSta,
                'plan_date' => $planDate,
                'remarks' => $this->cleanStr($sheet->getCell('P'.$r)->getValue()),
                'status' => $status,
                'close_date' => $closeDate,
                'act_station' => $actSta,
                'hold_reason_category' => $codeOpen,
                'hold_remarks' => $reasonOpen,
                'code_open' => $codeOpen,
                'reason_open' => $reasonOpen,
                'is_submitted' => true,
                'import_source' => 'unplanned',
            ];

            if ($nsrdiNo) {
                $existing = NsrdiLog::where('nsrdi_number', $nsrdiNo)
                    ->where('import_source', 'unplanned')
                    ->when($refreshDate, fn ($q) => $q->where('refresh_date', $refreshDate))
                    ->first();

                if ($existing) {
                    $existing->update($data);

                    continue;
                }
            }

            NsrdiLog::create($data);
        }
    }

    private function syncCapacityTargets(Worksheet $sheet): int
    {
        $updated = 0;
        // Row 21 in TARGET contains the daily sums per AOC: C21=JT, D21=ID, E21=IU, F21=IW
        $row21C = (int) $sheet->getCell('C21')->getCalculatedValue();
        $row21D = (int) $sheet->getCell('D21')->getCalculatedValue();
        $row21E = (int) $sheet->getCell('E21')->getCalculatedValue();
        $row21F = (int) $sheet->getCell('F21')->getCalculatedValue();

        if ($row21C > 0) {
            CapacityTarget::updateOrCreate(['aoc' => 'JT'], ['target_nsrdi' => $row21C]);
            $updated++;
        }
        if ($row21D > 0) {
            CapacityTarget::updateOrCreate(['aoc' => 'ID'], ['target_nsrdi' => $row21D]);
            $updated++;
        }
        if ($row21E > 0) {
            CapacityTarget::updateOrCreate(['aoc' => 'IU'], ['target_nsrdi' => $row21E]);
            $updated++;
        }
        if ($row21F > 0) {
            CapacityTarget::updateOrCreate(['aoc' => 'IW'], ['target_nsrdi' => $row21F]);
            $updated++;
        }

        return $updated;
    }

    private function parseDate(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        try {
            if (is_numeric($val)) {
                return ExcelDate::excelToDateTimeObject((float) $val)->format('Y-m-d');
            }

            return Carbon::parse((string) $val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cleanStr(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $s = trim((string) $val);

        return $s === '' ? null : $s;
    }
}
