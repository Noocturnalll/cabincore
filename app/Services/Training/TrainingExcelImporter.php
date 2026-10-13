<?php

namespace App\Services\Training;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingExcelImporter
{
    /**
     * Import training records from Excel workbook.
     *
     * @return array{
     *     employees_processed: int,
     *     trainings_recorded: int,
     *     valid_count: int,
     *     expiring_soon: int,
     *     expired: int,
     *     scheduled: int,
     *     unscheduled: int
     * }
     */
    public function import(string $path, bool $dryRun = false): array
    {
        @ini_set('memory_limit', '512M');

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $availableSheets = $reader->listWorksheetNames($path);
        $targetSheets = array_intersect(
            $availableSheets,
            ['AIRCRAFT PERFORMEN TRAINING (2', 'AIRCRAFT PERFORMEN TRAINING', 'DATABASE', 'schedule', 'unschedule', 'BAT']
        );
        if (! empty($targetSheets)) {
            $reader->setLoadSheetsOnly(array_values($targetSheets));
        }

        $spreadsheet = $reader->load($path);

        // Find primary training matrix sheet
        $matrixSheet = $this->findMatrixSheet($spreadsheet);
        if (! $matrixSheet) {
            throw new \RuntimeException('Sheet data pelatihan utama tidak ditemukan di file Excel.');
        }

        // Schedule & Unschedule lookup
        $scheduleMap = $this->extractScheduleMap($spreadsheet);
        $unscheduleMap = $this->extractUnscheduleMap($spreadsheet);

        // Employee ID map
        $employeeIdMap = Employee::pluck('id', 'nik')
            ->mapWithKeys(fn ($id, $nik) => [strtoupper(trim((string) $nik)) => $id])
            ->all();

        // Extract course column definitions from rows 1-3
        $courses = $this->extractCourseColumns($matrixSheet);

        $stats = [
            'employees_processed' => 0,
            'trainings_recorded' => 0,
            'valid_count' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'scheduled' => 0,
            'unscheduled' => 0,
        ];

        $highestRow = $matrixSheet->getHighestRow();
        $batch = [];
        $now = now()->startOfDay();

        for ($r = 4; $r <= $highestRow; $r++) {
            $nikRaw = $matrixSheet->getCell([3, $r])->getValue();
            $nik = $this->normalizeId($nikRaw);
            $nama = trim((string) $matrixSheet->getCell([2, $r])->getValue());

            if (! $nik || $nama === '') {
                continue;
            }

            $stats['employees_processed']++;
            $employeeId = $employeeIdMap[$nik] ?? null;
            $jabatan = trim((string) $matrixSheet->getCell([4, $r])->getValue()) ?: null;
            $station = strtoupper(trim((string) $matrixSheet->getCell([5, $r])->getValue())) ?: null;
            $company = strtoupper(trim((string) $matrixSheet->getCell([6, $r])->getValue())) ?: null;

            $isScheduled = isset($scheduleMap[$nik]);
            $unschedNote = $unscheduleMap[$nik] ?? null;

            foreach ($courses as $cName => $cInfo) {
                $courseDate = $cInfo['course_date_col'] ? $this->parseDate($matrixSheet->getCell([$cInfo['course_date_col'], $r])->getValue()) : null;
                $lastRec = $cInfo['last_rec_col'] ? $this->parseDate($matrixSheet->getCell([$cInfo['last_rec_col'], $r])->getValue()) : null;
                $expRec = $cInfo['exp_rec_col'] ? $this->parseDate($matrixSheet->getCell([$cInfo['exp_rec_col'], $r])->getValue()) : null;
                $recDate = $cInfo['rec_date_col'] ? $this->parseDate($matrixSheet->getCell([$cInfo['rec_date_col'], $r])->getValue()) : null;

                $rawCell = $cInfo['course_date_col'] ? trim((string) $matrixSheet->getCell([$cInfo['course_date_col'], $r])->getValue()) : '';

                // Calculate expiry for recurrent courses if last recurrent is given
                if (! $expRec && $lastRec) {
                    try {
                        $expRec = Carbon::parse($lastRec)->addYears(2)->toDateString();
                    } catch (\Throwable) {
                        $expRec = null;
                    }
                }

                $hasTaken = ($courseDate !== null) || ($lastRec !== null) || ($expRec !== null) || in_array(strtoupper($rawCell), ['X', 'V', 'CHECK', 'YA'], true);

                if (! $hasTaken && ! $isScheduled && ! $unschedNote) {
                    continue; // Skip courses with zero record for this person to save memory and clean tables
                }

                // Determine status
                $status = 'valid';
                if ($expRec) {
                    $expCarbon = Carbon::parse($expRec)->startOfDay();
                    if ($expCarbon->lt($now)) {
                        $status = 'expired';
                        $stats['expired']++;
                    } elseif ($expCarbon->lte($now->copy()->addMonthsNoOverflow(2))) {
                        $status = 'expiring_soon';
                        $stats['expiring_soon']++;
                    } else {
                        $status = 'valid';
                        $stats['valid_count']++;
                    }
                } elseif ($hasTaken) {
                    $status = 'valid';
                    $stats['valid_count']++;
                } elseif ($isScheduled) {
                    $status = 'scheduled';
                    $stats['scheduled']++;
                } elseif ($unschedNote) {
                    $status = 'unscheduled';
                    $stats['unscheduled']++;
                }

                $scheduleStatus = null;
                if ($isScheduled) {
                    $scheduleStatus = 'TERJADWAL';
                } elseif ($unschedNote) {
                    $scheduleStatus = $unschedNote;
                }

                $stats['trainings_recorded']++;

                if (! $dryRun) {
                    $batch[] = [
                        'employee_id' => $employeeId,
                        'employee_nik' => $nik,
                        'employee_name' => $nama,
                        'station' => $station,
                        'job_title' => $jabatan,
                        'company' => $company,
                        'category' => $cInfo['category'],
                        'course_name' => $cName,
                        'course_date' => $courseDate,
                        'last_recurrent_date' => $lastRec ?? $recDate,
                        'expiry_date' => $expRec,
                        'status' => $status,
                        'schedule_status' => $scheduleStatus,
                        'raw_value' => $rawCell !== '' ? substr($rawCell, 0, 100) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        DB::transaction(function () use (&$batch) {
                            DB::table('employee_trainings')->upsert(
                                $batch,
                                ['employee_nik', 'course_name'],
                                ['employee_id', 'employee_name', 'station', 'job_title', 'company', 'category', 'course_date', 'last_recurrent_date', 'expiry_date', 'status', 'schedule_status', 'raw_value', 'updated_at']
                            );
                        });
                        $batch = [];
                    }
                }
            }
        }

        if (! $dryRun && ! empty($batch)) {
            DB::transaction(function () use (&$batch) {
                DB::table('employee_trainings')->upsert(
                    $batch,
                    ['employee_nik', 'course_name'],
                    ['employee_id', 'employee_name', 'station', 'job_title', 'company', 'category', 'course_date', 'last_recurrent_date', 'expiry_date', 'status', 'schedule_status', 'raw_value', 'updated_at']
                );
            });
            $batch = [];
        }

        return $stats;
    }

    private function findMatrixSheet($spreadsheet): ?Worksheet
    {
        $names = ['AIRCRAFT PERFORMEN TRAINING (2', 'DATABASE', 'AIRCRAFT PERFORMEN TRAINING', 'TRAINING', 'Sheet1'];
        foreach ($names as $name) {
            $s = $spreadsheet->getSheetByName($name);
            if ($s && $s->getHighestRow() >= 10) {
                return $s;
            }
        }

        // Fallback: sheet with highest row & col
        foreach ($spreadsheet->getAllSheets() as $s) {
            if ($s->getHighestRow() > 50 && Coordinate::columnIndexFromString($s->getHighestColumn()) > 20) {
                return $s;
            }
        }

        return $spreadsheet->getSheet(0);
    }

    private function extractScheduleMap($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('schedule');
        if (! $sheet) {
            return [];
        }

        $map = [];
        $max = $sheet->getHighestRow();
        for ($r = 3; $r <= $max; $r++) {
            $nik = $this->normalizeId($sheet->getCell([4, $r])->getValue());
            if ($nik) {
                $map[$nik] = true;
            }
        }

        return $map;
    }

    private function extractUnscheduleMap($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('unschedule');
        if (! $sheet) {
            return [];
        }

        $map = [];
        $max = $sheet->getHighestRow();
        for ($r = 3; $r <= $max; $r++) {
            $nik = $this->normalizeId($sheet->getCell([3, $r])->getValue());
            if ($nik) {
                $note = trim((string) $sheet->getCell([19, $r])->getValue());
                $map[$nik] = $note ?: 'BELUM DAPAT SCHEDULE';
            }
        }

        return $map;
    }

    private function extractCourseColumns(Worksheet $sheet): array
    {
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $courses = [];
        $currentCategory = 'MANDATORY TRAINING';
        $currentCourse = '';

        for ($c = 7; $c <= $highestColIndex; $c++) {
            $cat = $sheet->getCell([$c, 1])->getValue();
            if ($cat !== null && trim((string) $cat) !== '' && ! str_starts_with(trim((string) $cat), '=')) {
                $currentCategory = trim((string) $cat);
            }

            $cName = $sheet->getCell([$c, 2])->getValue();
            if ($cName !== null && trim((string) $cName) !== '') {
                $currentCourse = preg_replace('/\s+/', ' ', trim((string) $cName));
            }

            $subHeader = strtoupper(trim((string) $sheet->getCell([$c, 3])->getValue()));

            if ($currentCourse === '') {
                continue;
            }

            if (! isset($courses[$currentCourse])) {
                $courses[$currentCourse] = [
                    'category' => $this->cleanCategory($currentCategory),
                    'name' => $currentCourse,
                    'course_date_col' => null,
                    'last_rec_col' => null,
                    'exp_rec_col' => null,
                    'rec_date_col' => null,
                ];
            }

            if (str_contains($subHeader, 'LAST REC')) {
                $courses[$currentCourse]['last_rec_col'] = $c;
            } elseif (str_contains($subHeader, 'EXP REC')) {
                $courses[$currentCourse]['exp_rec_col'] = $c;
            } elseif (str_contains($subHeader, 'REC COURSE') || str_contains($subHeader, 'REC .COURSE')) {
                $courses[$currentCourse]['rec_date_col'] = $c;
            } elseif (str_contains($subHeader, 'COURSE DATE') || str_contains($subHeader, 'LAST COURSE')) {
                if ($courses[$currentCourse]['course_date_col'] === null) {
                    $courses[$currentCourse]['course_date_col'] = $c;
                }
            } else {
                if ($courses[$currentCourse]['course_date_col'] === null) {
                    $courses[$currentCourse]['course_date_col'] = $c;
                }
            }
        }

        return $courses;
    }

    private function cleanCategory(string $cat): string
    {
        $upper = strtoupper(trim($cat));
        if (str_contains($upper, 'MANDATORY')) {
            return 'Mandatory Training';
        }
        if (str_contains($upper, 'TYPE RATING')) {
            return 'Type Rating & Systems';
        }
        if (str_contains($upper, 'ADDITONAL') || str_contains($upper, 'ADDITIONAL')) {
            return 'Additional Training';
        }

        return ucwords(strtolower(trim($cat)));
    }

    private function normalizeId(mixed $value): ?string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }
        $id = strtoupper(trim((string) $value));

        return $id === '' ? null : $id;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '-' || strtolower((string) $value) === 'x') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return (float) $value > 20000
                    ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString()
                    : null;
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
