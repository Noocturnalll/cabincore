<?php

namespace App\Services;

use App\Models\AcRon;
use App\Models\AcStandby;
use App\Models\SyncSetting;
use App\Models\TerminalMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcMovementSyncService
{
    public const Tabs = ['TERMINAL 1', 'TERMINAL 2', 'AC RON', 'AC STBY'];

    public function __construct(protected GoogleSheetsReader $reader) {}

    /**
     * Pulls every AC Movement tab from the spreadsheet and replaces local data per tab.
     *
     * @return array{success: bool, message: string, counts: array<string, int>}
     */
    public function pullSync(string $spreadsheetId): array
    {
        $result = $this->runSync($spreadsheetId);

        SyncSetting::recordResult(SyncSetting::AcMovement, $result['success'], $result['message']);

        return $result;
    }

    /**
     * @return array{success: bool, message: string, counts: array<string, int>}
     */
    protected function runSync(string $spreadsheetId): array
    {
        if (! $this->reader->isReady()) {
            return $this->failure('File kredensial Google (storage/app/google-credentials.json) tidak ditemukan.');
        }

        try {
            $tabs = $this->reader->resolveTabs(self::Tabs, $this->reader->tabTitles($spreadsheetId));
        } catch (\Throwable $e) {
            Log::error("AC Movement sync: cannot open spreadsheet {$spreadsheetId}: ".$e->getMessage());

            return $this->failure('Spreadsheet tidak bisa dibuka. Pastikan sheet sudah di-share ke email service account. Detail: '.$this->shortError($e));
        }

        if ($tabs === []) {
            return $this->failure('Tidak ada tab TERMINAL 1 / TERMINAL 2 / AC RON / AC STBY di spreadsheet ini.');
        }

        $counts = [];
        $errors = [];

        foreach ($tabs as $wanted => $actualTitle) {
            try {
                $rows = $this->reader->values($spreadsheetId, $actualTitle);
                $counts[$wanted] = $this->syncTab($wanted, $rows);
            } catch (\Throwable $e) {
                Log::error("AC Movement sync failed on tab {$actualTitle}: ".$e->getMessage());
                $errors[] = "{$wanted}: ".$this->shortError($e);
            }
        }

        $missing = array_diff(self::Tabs, array_keys($tabs));
        $summary = collect($counts)->map(fn ($count, $tab) => "{$tab}={$count}")->implode(', ');
        $message = 'AC Movement tersinkron ('.$summary.')';

        if ($missing) {
            $message .= '. Tab tidak ditemukan: '.implode(', ', $missing);
        }

        if ($errors) {
            $message .= '. Gagal: '.implode('; ', $errors);
        }

        return ['success' => $counts !== [], 'message' => $message, 'counts' => $counts];
    }

    /**
     * Replaces the local records of a single tab with the given sheet rows. Returns rows saved.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function syncTab(string $tab, array $rows): int
    {
        return match ($tab) {
            'TERMINAL 1', 'TERMINAL 2' => $this->syncTerminal($tab, $rows),
            'AC RON' => $this->syncRon($rows),
            'AC STBY' => $this->syncStandby($rows),
            default => 0,
        };
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function syncTerminal(string $terminal, array $rows): int
    {
        $header = $this->reader->locateHeader($rows, ['DATE', 'NO', 'REGISTRASI', 'REGISTRATION', 'STA', 'ETA', 'STD', 'ATD', 'PLAN PS', 'FLIGHT NO (IN)', 'FLIGHT NO (OUT)']);
        $col = $this->columnResolver($header['map'] ?? []);
        $dataRows = $this->dataRows($rows, $header);

        $records = [];
        $currentDate = null;

        foreach ($dataRows as $row) {
            $cell = fn (array $names, int $fallback) => GoogleSheetsReader::cleanString($row[$col($names, $fallback)] ?? null);

            $date = GoogleSheetsReader::parseDate($cell(['DATE', 'TANGGAL', 'TGL'], 0));
            $currentDate = $date ?? $currentDate;

            $registration = $cell(['REGISTRASI', 'REGISTRATION', 'REG', 'A/C REG'], 2);
            $flightIn = $cell(['FLIGHT NO (IN)', 'FLIGHT NO IN', 'FLT IN', 'FLIGHT IN'], 3);
            $flightOut = $cell(['FLIGHT NO (OUT)', 'FLIGHT NO OUT', 'FLT OUT', 'FLIGHT OUT'], 7);

            if (! $registration && ! $flightIn && ! $flightOut) {
                continue;
            }

            $records[] = [
                'terminal_name' => $terminal,
                'flight_date' => $currentDate,
                'no_seq' => GoogleSheetsReader::parseInteger($cell(['NO', 'NO.'], 1)),
                'registration' => $registration,
                'flight_no_in' => $flightIn,
                'sta' => $cell(['STA'], 4),
                'eta' => $cell(['ETA'], 5),
                'plan_ps' => $cell(['PLAN PS', 'PLAN P/S', 'PS'], 6),
                'flight_no_out' => $flightOut,
                'std' => $cell(['STD'], 8),
                'atd' => $cell(['ATD'], 9),
                'engineer_handle' => $cell(['MECHANIC / ENGINEER HANDLE', 'ENGINEER HANDLE', 'MECHANIC', 'ENGINEER'], 10),
                'input_afml' => $cell(['INPUT AFML', 'AFML'], 11),
                'actual_registration' => $cell(['ACTUAL REGISTRATION', 'ACTUAL REG'], 12),
                'tear_off_afml_pink' => $cell(['TEAR OFF AFML PINK', 'TEAR OFF AFML', 'PINK AFML'], 13),
            ];
        }

        DB::transaction(function () use ($terminal, $records) {
            TerminalMovement::where('terminal_name', $terminal)->delete();
            foreach ($records as $record) {
                TerminalMovement::create($record);
            }
        });

        return count($records);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function syncRon(array $rows): int
    {
        $header = $this->reader->locateHeader($rows, ['TGL', 'DATE', 'NO', 'REG FLT', 'REG', 'EX FLT', 'STA/ATA', 'STAND', 'FLT NO', 'ROUTE', 'STD', 'REMARKS']);
        $col = $this->columnResolver($header['map'] ?? []);
        $dataRows = $this->dataRows($rows, $header);

        $records = [];
        $currentDate = null;

        foreach ($dataRows as $row) {
            $cell = fn (array $names, int $fallback) => GoogleSheetsReader::cleanString($row[$col($names, $fallback)] ?? null);

            $date = GoogleSheetsReader::parseDate($cell(['TGL', 'DATE', 'TANGGAL'], 0));
            $currentDate = $date ?? $currentDate;

            $registration = $cell(['REG FLT', 'REG', 'REGISTRASI', 'A/C REG'], 2);
            if (! $registration) {
                continue;
            }

            $records[] = [
                'ron_date' => $currentDate,
                'no_seq' => GoogleSheetsReader::parseInteger($cell(['NO', 'NO.'], 1)),
                'reg_flt' => $registration,
                'ex_flt' => $cell(['EX FLT', 'EX FLIGHT', 'EX'], 3),
                'sta_ata' => $cell(['STA/ATA', 'STA ATA', 'ATA', 'STA'], 4),
                'stand' => $cell(['STAND', 'PARKING', 'PARKING STAND'], 5),
                'flt_no' => $cell(['FLT NO', 'FLIGHT NO', 'FLT'], 6),
                'route' => $cell(['ROUTE', 'RUTE'], 7),
                'std' => $cell(['STD'], 8),
                'remarks' => $cell(['REMARKS', 'REMARK'], 9),
                'note' => $cell(['NOTE', 'NOTES', 'CATATAN'], 10),
            ];
        }

        DB::transaction(function () use ($records) {
            AcRon::query()->delete();
            foreach ($records as $record) {
                AcRon::create($record);
            }
        });

        return count($records);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function syncStandby(array $rows): int
    {
        $header = $this->reader->locateHeader($rows, ['AIRLINE', 'CATEGORY', 'NO', 'REG FLT', 'REG', 'PARKING', 'STAND', 'PLAN RTS', 'RTS', 'REMARKS']);
        $map = $header['map'] ?? [];
        $col = $this->columnResolver($map);
        $dataRows = $this->dataRows($rows, $header);
        $hasAirlineColumn = isset($map['AIRLINE']) || isset($map['CATEGORY']);

        $records = [];
        $currentAirline = 'General';

        foreach ($dataRows as $row) {
            $cell = fn (array $names, int $fallback) => GoogleSheetsReader::cleanString($row[$col($names, $fallback)] ?? null);

            $filled = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $row), fn ($value) => $value !== ''));
            $registration = $cell(['REG FLT', 'REG', 'REGISTRASI', 'A/C REG'], 2);

            // Section rows such as "CITILINK" / "GARUDA" (a single filled cell) set the airline for the rows below.
            if (! $registration && count($filled) === 1) {
                $currentAirline = $filled[0];

                continue;
            }

            if (! $registration) {
                continue;
            }

            $airline = $hasAirlineColumn ? ($cell(['AIRLINE', 'CATEGORY'], 0) ?? $currentAirline) : $currentAirline;
            $currentAirline = $airline;

            $records[] = [
                'airline_category' => $airline,
                'no_seq' => GoogleSheetsReader::parseInteger($cell(['NO', 'NO.'], 1)),
                'reg_flt' => $registration,
                'parking' => $cell(['PARKING', 'STAND', 'PARKING STAND'], 3),
                'plan_rts' => $cell(['PLAN RTS', 'RTS', 'PLAN'], 4),
                'remarks' => $cell(['REMARKS', 'REMARK'], 5),
            ];
        }

        DB::transaction(function () use ($records) {
            AcStandby::query()->delete();
            foreach ($records as $record) {
                AcStandby::create($record);
            }
        });

        return count($records);
    }

    /**
     * @param  array<string, int>  $headerMap
     */
    protected function columnResolver(array $headerMap): \Closure
    {
        return function (array $names, int $fallback) use ($headerMap): int {
            foreach ($names as $name) {
                $key = GoogleSheetsReader::normalizeName($name);
                if (isset($headerMap[$key])) {
                    return $headerMap[$key];
                }
            }

            return $headerMap === [] ? $fallback : -1;
        };
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array{index: int, map: array<string, int>}|null  $header
     * @return array<int, array<int, mixed>>
     */
    protected function dataRows(array $rows, ?array $header): array
    {
        return array_slice($rows, ($header['index'] ?? 0) + 1);
    }

    /**
     * @return array{success: bool, message: string, counts: array<string, int>}
     */
    protected function failure(string $message): array
    {
        return ['success' => false, 'message' => $message, 'counts' => []];
    }

    protected function shortError(\Throwable $e): string
    {
        $decoded = json_decode($e->getMessage(), true);

        return mb_substr($decoded['error']['message'] ?? $e->getMessage(), 0, 200);
    }
}
