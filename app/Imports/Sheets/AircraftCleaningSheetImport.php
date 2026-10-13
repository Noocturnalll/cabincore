<?php

namespace App\Imports\Sheets;

use App\Models\AircraftCleaning;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AircraftCleaningSheetImport implements ToCollection, WithChunkReading
{
    protected string $sheetName;

    public function __construct(string $sheetName = 'DATABASE')
    {
        $this->sheetName = $sheetName;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Cari baris header (bisa baris 1 atau baris 2 jika baris 1 adalah banner judul)
        $headerIndex = null;
        $headerMap = [];

        foreach ($rows->take(3) as $idx => $row) {
            $rowValues = array_map(fn ($val) => strtoupper(trim((string) $val)), $row->toArray());
            if (in_array('DATE', $rowValues, true) || in_array('A/C REG', $rowValues, true) || in_array('AIRCRAFT_REGISTRATION', $rowValues, true)) {
                $headerIndex = $idx;
                foreach ($rowValues as $colIdx => $colName) {
                    if ($colName !== '') {
                        $headerMap[$colName] = $colIdx;
                    }
                }
                break;
            }
        }

        // Jika tidak menemukan header standar, asumsikan baris 0 adalah header default
        if ($headerIndex === null) {
            $headerIndex = 0;
            foreach ($rows->first() as $colIdx => $colName) {
                $clean = strtoupper(trim((string) $colName));
                if ($clean !== '') {
                    $headerMap[$clean] = $colIdx;
                }
            }
        }

        // Pemetaan indeks kolom
        $colDate = $this->findColIndex($headerMap, ['DATE', 'TANGGAL']);
        $colReg = $this->findColIndex($headerMap, ['A/C REG', 'AC REG', 'REG', 'AIRCRAFT_REGISTRATION', 'REGISTRATION']);
        $colAcType = $this->findColIndex($headerMap, ['A/C TYPE', 'AC TYPE', 'TYPE AC', 'AIRCRAFT_TYPE']);
        $colOperator = $this->findColIndex($headerMap, ['OPERATOR', 'AIRLINE']);
        $colStation = $this->findColIndex($headerMap, ['STA', 'STATION', 'BANDARA']);
        $colShift = $this->findColIndex($headerMap, ['SHIFT', 'REGU']);
        $colAction = $this->findColIndex($headerMap, ['ACTION TAKEN / REASON', 'ACTION TAKEN', 'TYPE', 'JENIS', 'TASK']);
        $colAcStatus = $this->findColIndex($headerMap, ['A/C STATUS', 'AC STATUS', 'STATUS AC']);
        $colTaskType = $this->findColIndex($headerMap, ['TASK TYPE', 'TIPE TASK']);
        $colDocNo = $this->findColIndex($headerMap, ['NO. DOC', 'NO DOC', 'REF NO', 'FLIGHT NO', 'WO']);
        $colStatus = $this->findColIndex($headerMap, ['STATUS']);
        $colStart = $this->findColIndex($headerMap, ['START PERFORM', 'START', 'MULAI']);
        $colFinish = $this->findColIndex($headerMap, ['FINISH PERFORM', 'FINISH', 'SELESAI']);
        $colMp = $this->findColIndex($headerMap, ['TOTAL MP', 'MAN POWER', 'MP']);
        $colMhrs = $this->findColIndex($headerMap, ['ACTUAL MHRS', 'MAN HOUR', 'MHRS', 'INDIVIDUAL MHRS']);

        $recordsToUpsert = [];

        foreach ($rows->slice($headerIndex + 1) as $row) {
            $rawReg = $colReg !== null ? trim((string) ($row[$colReg] ?? '')) : '';
            $rawDate = $colDate !== null ? ($row[$colDate] ?? null) : null;

            if ($rawReg === '' || $rawDate === null || $rawDate === '') {
                continue;
            }

            // Normalisasi Tanggal
            $parsedDate = $this->parseExcelDate($rawDate);
            if (! $parsedDate) {
                continue;
            }

            // Normalisasi Tipe Cleaning
            $rawAction = $colAction !== null ? trim((string) ($row[$colAction] ?? '')) : 'General';
            $type = $this->normalizeCleaningType($rawAction);

            // Normalisasi Shift (Pagi, Siang, Malam)
            $rawShift = $colShift !== null ? trim((string) ($row[$colShift] ?? '')) : null;
            $shift = $this->normalizeShift($rawShift);

            // Normalisasi Status (Open, Closed)
            $rawStatus = $colStatus !== null ? trim((string) ($row[$colStatus] ?? '')) : 'Closed';
            $status = $this->normalizeStatus($rawStatus, $type);

            // Operator & Station
            $operator = $colOperator !== null ? trim((string) ($row[$colOperator] ?? '')) : null;
            $station = $colStation !== null ? strtoupper(trim((string) ($row[$colStation] ?? ''))) : null;
            $acType = $colAcType !== null ? trim((string) ($row[$colAcType] ?? '')) : null;
            $acStatus = $colAcStatus !== null ? strtoupper(trim((string) ($row[$colAcStatus] ?? ''))) : null;
            $taskType = $colTaskType !== null ? strtoupper(trim((string) ($row[$colTaskType] ?? ''))) : null;
            $refNo = $colDocNo !== null ? trim((string) ($row[$colDocNo] ?? '')) : null;

            // Jam Mulai & Selesai
            $rawStartTime = $colStart !== null ? ($row[$colStart] ?? null) : null;
            $rawFinishTime = $colFinish !== null ? ($row[$colFinish] ?? null) : null;
            [$startAt, $endAt, $calcHours] = $this->parseWorkTimes($parsedDate, $rawStartTime, $rawFinishTime);

            // Manpower & Manhours (Sanitasi agar tidak negatif / jutaan jam)
            $manPower = $colMp !== null && is_numeric($row[$colMp] ?? null) ? (int) $row[$colMp] : null;
            $rawMhrs = $colMhrs !== null && is_numeric($row[$colMhrs] ?? null) ? (float) $row[$colMhrs] : null;

            if ($rawMhrs !== null && $rawMhrs >= 0 && $rawMhrs <= 24) {
                $manHour = round($rawMhrs, 2);
            } elseif ($calcHours !== null && $manPower) {
                $manHour = round(min(24, $calcHours * $manPower), 2);
            } else {
                $manHour = 0.0;
            }

            // Simpan / update record
            AircraftCleaning::updateOrCreate(
                [
                    'aircraft_registration' => $rawReg,
                    'date' => $parsedDate,
                    'type' => $type,
                    'ref_no' => $refNo ?: 'N/A',
                ],
                [
                    'aircraft_type' => $acType,
                    'station' => $station,
                    'shift' => $shift,
                    'operator' => $operator,
                    'status' => $status,
                    'ac_status' => $acStatus,
                    'task_type' => $taskType,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'man_power' => $manPower,
                    'man_hour' => $manHour,
                    'import_source' => $this->sheetName,
                    'remarks' => $rawAction !== $type ? $rawAction : null,
                ]
            );
        }
    }

    private function findColIndex(array $headerMap, array $candidates): ?int
    {
        foreach ($candidates as $cand) {
            if (isset($headerMap[$cand])) {
                return $headerMap[$cand];
            }
        }

        return null;
    }

    private function parseExcelDate(mixed $rawDate): ?string
    {
        if (is_numeric($rawDate)) {
            $num = (float) $rawDate;
            if ($num > 30000 && $num < 65000) {
                return Date::excelToDateTimeObject($num)->format('Y-m-d');
            }
        }

        if (is_string($rawDate) && trim($rawDate) !== '') {
            try {
                return Carbon::parse($rawDate)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function normalizeCleaningType(string $raw): string
    {
        $u = strtoupper(trim($raw));
        if (str_contains($u, 'TRANSIT')) {
            return 'Transit';
        }
        if (str_contains($u, 'DCI') || str_contains($u, 'DEEP CLEANING INTERIOR')) {
            return 'DCI';
        }
        if (str_contains($u, 'DCE') || str_contains($u, 'DEEP CLEANING EXTERIOR')) {
            return 'DCE';
        }
        if (str_contains($u, 'GCI')) {
            return 'GCI';
        }
        if (str_contains($u, 'GCE')) {
            return 'GCE';
        }
        if (str_contains($u, 'DBI') || str_contains($u, 'BEAUTIFICATION')) {
            return 'DBI';
        }
        if (str_contains($u, 'LGT')) {
            return 'LGT';
        }

        return 'General';
    }

    private function normalizeShift(?string $raw): string
    {
        if (! $raw) {
            return 'Pagi';
        }
        $u = strtoupper(trim($raw));
        if (str_contains($u, 'SIANG') || str_contains($u, 'AFTERNOON')) {
            return 'Siang';
        }
        if (str_contains($u, 'MALAM') || str_contains($u, 'NIGHT')) {
            return 'Malam';
        }

        return 'Pagi';
    }

    private function normalizeStatus(string $raw, string $type): string
    {
        $u = strtoupper(trim($raw));
        if (str_contains($u, 'CLOSED') || str_contains($u, 'CLOSE') || str_contains($u, 'SELESAI')) {
            return 'Closed';
        }
        if (str_contains($u, 'OPEN') || str_contains($u, 'AKTIF') || str_contains($u, 'PROGRESS')) {
            return 'Open';
        }

        // Default untuk Transit/General biasanya Closed
        return in_array($type, ['Transit', 'General']) ? 'Closed' : 'Open';
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?float}
     */
    private function parseWorkTimes(string $date, mixed $rawStart, mixed $rawEnd): array
    {
        $startTime = $this->formatTimeValue($rawStart);
        $endTime = $this->formatTimeValue($rawEnd);

        if (! $startTime || ! $endTime) {
            return [null, null, null];
        }

        try {
            $startAt = Carbon::parse("$date $startTime");
            $endAt = Carbon::parse("$date $endTime");

            // Jika selesai lewat tengah malam (contoh mulai 23:00 selesai 02:00)
            if ($endAt->lessThan($startAt)) {
                $endAt->addDay();
            }

            $diffMinutes = $startAt->diffInMinutes($endAt);
            $hours = round($diffMinutes / 60, 2);

            // Batasi durasi logis maks 8 jam per shift
            if ($hours > 8) {
                $hours = 8.0;
            }

            return [$startAt->format('Y-m-d H:i:s'), $endAt->format('Y-m-d H:i:s'), $hours];
        } catch (\Throwable) {
            return [null, null, null];
        }
    }

    private function formatTimeValue(mixed $val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        if (is_numeric($val)) {
            $num = (float) $val;
            // Jika desimal hari (0.0 s/d 1.0)
            if ($num >= 0 && $num < 1) {
                $seconds = (int) round($num * 86400);
                $h = floor($seconds / 3600);
                $m = floor(($seconds % 3600) / 60);

                return sprintf('%02d:%02d', $h, $m);
            }
        }

        $s = trim((string) $val);
        // Sanitasi typo umum seperti 22::28 atau 15;38
        $s = str_replace(['::', ';'], ':', $s);

        if (preg_match('/^\d{1,2}:\d{2}/', $s, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
