<?php

namespace App\Services\Dja;

use App\Services\GoogleSheetsReader;

/**
 * Turns one planner-sheet row into a normalised array, for both the Google Sheets sync and the Excel import.
 *
 * Each field is looked up by header name first. The positional index is only a fallback and is ignored when the
 * sheet has a header that labels that column as something else, so a re-ordered sheet cannot fill a field with
 * the wrong data.
 */
class DjaRowMapper
{
    public const KIND_BY_TAB = [
        'DJA' => 'wo',
        'DJA DMI' => 'dmi',
        'DJA NSRD' => 'nsrdi',
        'DJA NSRDI' => 'nsrdi',
    ];

    public const JOB_TYPE = ['wo' => 'R01/WO', 'dmi' => 'DMI', 'nsrdi' => 'AOC/NSRDI'];

    private const AC_REG = [['AC REG', 'A/C REG', 'AIRCRAFT', 'REG', 'AIRCRAFT REGISTRATION'], null];

    private const DESCRIPTION_ALIASES = [
        'DESCRIPTION', 'WO DESCRIPTION', 'DMI DESCRIPTION', 'FINDING DESCRIPTION',
        'FINDING DESC', 'FINDING_DESCRIPTION', 'NSRDI DESCRIPTION',
        'TASK CARD DESCRIPTION', 'TASK CARD DESC', 'TASK DESCRIPTION', 'JOB DESCRIPTION',
        'WORK DESCRIPTION', 'ITEM DESCRIPTION', 'ACTION TAKEN', 'DEFECT DESCRIPTION',
        'DISCREPANCY', 'PROBLEM DESCRIPTION', 'FINDING', 'DEFECT', 'DESC', 'TASK',
        'ACTION TAKEN / REASON', 'ACTION TAKEN/REASON', 'URAIAN PEKERJAAN', 'DESKRIPSI',
    ];

    private const DESCRIPTION = [self::DESCRIPTION_ALIASES, null];

    private const CODE_OPEN_ALIASES = ['CODE OPEN', 'CODE REASON', 'CODE', 'REASON CODE', 'HOLD REASON CATEGORY', 'KODE ALASAN', 'KD REASON', 'REASON CODE OPEN'];

    private const REASON_OPEN_ALIASES = ['REASON OPEN', 'REASON', 'KETERANGAN OPEN', 'HOLD REMARKS', 'ALASAN', 'REASON CODE REMARKS', 'REASON KET'];

    private const PLAN_DATE_ALIASES = ['PLAN DATE', 'DATE PLAN', 'PLANNING DATE', 'PLAN DATE DJA', 'TGL PLAN', 'TANGGAL PLAN'];

    private const REFRESH_DATE_ALIASES = ['REFRESH DATE', 'INPUT DATE', 'DATE REFRESH', 'REFRESH', 'TGL REFRESH', 'TANGGAL REFRESH'];

    /** @var array<string, array<string, array{0: array<int, string>, 1: int|null}>> */
    private const FIELDS = [
        'wo' => [
            'refresh_date' => [self::REFRESH_DATE_ALIASES, 0],
            'work_group' => [['WG', 'WORK GROUP'], 1],
            'ac_reg' => [self::AC_REG[0], 2],
            'task_id' => [['WO', 'WO NUMBER', 'NO WO', 'TASK ID'], 3],
            'wo_category' => [['WO CAT', 'CATEGORY', 'TRADE', 'DIVISI'], 4],
            'description' => [self::DESCRIPTION[0], 5],
            'task_card' => [['TASK CARD'], 6],
            'task_card_description' => [['TASK CARD DESCRIPTION', 'TASK CARD DESC'], 7],
            'rii' => [['RII / ETOPS / ERU / IERA', 'RII/ETOPS/ERU/IERA', 'RII', 'ETOPS', 'ERU', 'IERA'], 8],
            'man_hour' => [['MAN HOURS', 'MAN HOUR', 'MH'], 9],
            'site' => [['SITE'], 10],
            'due_date_wo' => [['DUE DATE WO', 'DUE DATE'], 11],
            'class_wo' => [['CLASS WO', 'CLASS'], 12],
            'materials' => [['MATERIALS', 'MATERIAL'], 13],
            'tools' => [['TOOLS', 'TOOL'], 14],
            'pn_picklist' => [['PN PICKLIST (NO. PICKLIST)', 'PN PICKLIST (NO PICKLIST)', 'PN PICKLIST'], 15],
            'operator' => [['OPERATOR'], 16],
            'type' => [['TYPE'], 17],
            'plan_station' => [['PLAN STA', 'PLAN STATION', 'PLAN STN', 'STATION', 'STA'], 18],
            'plan_date' => [self::PLAN_DATE_ALIASES, 19],
            'remarks_ppc_to_lm' => [['REMARKS PPC WG TO LM', 'REMARKS PPC TO LM', 'REMARKS PPC'], 20],
            'planner' => [['PLANNER'], 21],
            'ppc_onduty' => [['PPC ONDUTY', 'PPC ON DUTY', 'PPC ON-DUTY'], 22],
            'status' => [['STATUS WO', 'STATUS', 'STAT', 'STATUS DJA', 'STATUS JOB', 'STATE', 'CLOSED / OPEN', 'OPEN/CLOSED'], 23],
            'close_date' => [['CLOSE DATE', 'CLOSED DATE', 'DATE CLOSED'], 24],
            'act_station' => [['ACT STA', 'ACT STATION', 'ACT STN', 'ACTUAL STA', 'ACTUAL STATION'], 25],
            'reason_open' => [self::REASON_OPEN_ALIASES, 26],
            'code_open' => [self::CODE_OPEN_ALIASES, 27],
            'highlight' => [['HIGHLIGHT', 'HIHLIGHT'], 28],
        ],
        'dmi' => [
            'refresh_date' => [['INPUT DATE', ...self::REFRESH_DATE_ALIASES], 0],
            'work_group' => [['WG', 'WORK GROUP'], 1],
            'ac_reg' => [self::AC_REG[0], 2],
            'task_id' => [['DMI NO', 'DMI NUMBER', 'TASK ID'], 3],
            'dmi_category' => [['DMI CAT', 'DMI CATEGORY'], 4],
            'defer_type' => [['DEFER TYPE'], 5],
            'status' => [['STATUS DMI', 'STATUS', 'STAT', 'STATUS DJA', 'CLOSED / OPEN', 'OPEN/CLOSED'], 6],
            'wo_number' => [['WO', 'WO NUMBER', 'NO WO'], 7],
            'description' => [['DMI DESCRIPTION', ...self::DESCRIPTION[0]], 8],
            'ata' => [['ATA CHAPTER', 'ATA', 'CHAPTER'], 9],
            'due_date_dmi' => [['DUE DATE DMI', 'DUE DATE'], 10],
            'pn_required' => [['PN REQUIRED', 'PN'], 11],
            'picklist_number' => [['PICKLIST NUMBER', 'PICKLIST NO', 'PICKLIST'], 12],
            'aoc' => [['AOC'], 13],
            'type' => [['TYPE'], 14],
            'plan_station' => [['PLAN STA', 'PLAN STATION', 'PLAN STN', 'STATION', 'STA'], 15],
            'plan_date' => [self::PLAN_DATE_ALIASES, 16],
            'remarks' => [['REMARKS', 'REMARK'], 17],
            'status_wo' => [['STATUS WO'], 18],
            'close_date' => [['CLOSE DATE', 'CLOSED DATE', 'DATE CLOSED'], 19],
            'act_station' => [['ACT STA', 'ACT STATION', 'ACT STN', 'ACTUAL STA', 'ACTUAL STATION'], 20],
            'reason_open' => [self::REASON_OPEN_ALIASES, 21],
            'code_open' => [self::CODE_OPEN_ALIASES, 22],
            'highlight' => [['HIHLIGHT', 'HIGHLIGHT'], 23],
            'category' => [['CAT', 'CATEGORY'], null],
            'operator' => [['OPERATOR'], null],
        ],
        'nsrdi' => [
            'refresh_date' => [self::REFRESH_DATE_ALIASES, 0],
            'work_group' => [['WG', 'WORK GROUP'], 1],
            'ac_reg' => [self::AC_REG[0], 2],
            'task_id' => [['NSRDI', 'NSRDI NO', 'NSRDI NUMBER', 'TASK ID'], 3],
            'description' => [['FINDING DESCRIPTION', ...self::DESCRIPTION[0]], 4],
            'category' => [['CATEGORY', 'CAT', 'KATEGORI', 'TRADE', 'DIVISI'], 5],
            'report_date' => [['REPORT DATE'], 6],
            'due_date' => [['DUE DATE'], 7],
            'part_number' => [['PART NUMBER', 'PN'], 8],
            'part_description' => [['PART DESCRIPTION', 'PART DESC', 'PART NAME', 'P/N DESC'], 9],
            'defer' => [['DEFER'], 10],
            'aoc' => [['AOC'], 11],
            'type' => [['TYPE'], 12],
            'plan_station' => [['PLAN STA', 'PLAN STATION', 'PLAN STN', 'STATION', 'STA'], 13],
            'plan_date' => [self::PLAN_DATE_ALIASES, 14],
            'remarks' => [['REMARKS', 'REMARK'], 15],
            'status' => [['STATUS', 'STAT', 'STATUS NSRDI', 'STATUS NSRD', 'STATUS DJA', 'CLOSED / OPEN', 'OPEN/CLOSED'], 16],
            'close_date' => [['CLOSE DATE', 'CLOSED DATE', 'DATE CLOSED'], 17],
            'act_station' => [['ACT STA', 'ACT STATION', 'ACT STN', 'ACTUAL STA', 'ACTUAL STATION'], 18],
            'reason_open' => [self::REASON_OPEN_ALIASES, 19],
            'code_open' => [self::CODE_OPEN_ALIASES, 20],
        ],
    ];

    /** Columns used only for classification / dates, never copied to a log. */
    private const SHARED = [
        'ata' => [['ATA', 'ATA CHAPTER', 'CHAPTER'], null],
        'task_card' => [['TASK CARD'], null],
        'task_card_description' => [['TASK CARD DESCRIPTION'], null],
        'plan_date' => [self::PLAN_DATE_ALIASES, null],
        'refresh_date' => [self::REFRESH_DATE_ALIASES, 20],
        'code_open' => [self::CODE_OPEN_ALIASES, null],
        'reason_open' => [self::REASON_OPEN_ALIASES, null],
    ];

    /** Header names the reader should look for to find the header row. */
    public static function knownHeaders(): array
    {
        $names = ['STATUS'];
        foreach (self::FIELDS as $fields) {
            foreach ($fields as [$aliases]) {
                array_push($names, ...$aliases);
            }
        }
        foreach (self::SHARED as [$aliases]) {
            array_push($names, ...$aliases);
        }

        return array_values(array_unique($names));
    }

    public static function kindForTab(string $tab): ?string
    {
        $normalized = strtoupper(trim($tab));

        if (isset(self::KIND_BY_TAB[$normalized])) {
            return self::KIND_BY_TAB[$normalized];
        }

        // Fuzzy matching for tab name variations in actual workbooks
        if (str_contains($normalized, 'NSRD') || str_contains($normalized, 'AOC')) {
            return 'nsrdi';
        }

        if (str_contains($normalized, 'DMI')) {
            return 'dmi';
        }

        if (
            str_contains($normalized, 'WO') ||
            str_contains($normalized, 'R01') ||
            str_contains($normalized, 'DJA') ||
            $normalized === 'SHEET1' ||
            $normalized === 'SHEET 1'
        ) {
            return 'wo';
        }

        return null;
    }

    private const LEGACY_FALLBACKS = [
        'wo' => [
            'work_group' => 1,
            'ac_reg' => 2,
            'task_id' => 3,
            'wo_category' => 4,
            'description' => 5,
            'man_hour' => 7,
            'operator' => 8,
            'type' => 9,
            'plan_station' => 10,
            'remarks_ppc_to_lm' => 11,
            'act_station' => 12,
            'status' => 13,
            'code_open' => 14,
            'reason_open' => 15,
        ],
        'dmi' => [
            'ac_reg' => 1,
            'description' => 2,
            'pn_required' => 3,
            'task_id' => 4,
            'dmi_category' => 5,
            'plan_station' => 6,
            'category' => 7,
            'status' => 8,
            'remarks' => 9,
        ],
        'nsrdi' => [
            'work_group' => 1,
            'ac_reg' => 2,
            'task_id' => 3,
            'description' => 4,
            'category' => 5,
            'report_date' => 6,
            'due_date' => 7,
            'part_number' => 8,
            'part_description' => 9,
            'defer' => 10,
            'aoc' => 11,
            'type' => 12,
            'plan_station' => 13,
            'remarks' => 14,
            'status' => 15,
            'close_date' => 16,
        ],
    ];

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $headerMap  header name => column index (empty = positional only)
     * @param  string|null  $targetDate  optional operational date from spreadsheet context
     * @return array<string, mixed>|null null when the row is blank
     */
    public function map(string $tab, array $row, array $headerMap = [], ?string $targetDate = null): ?array
    {
        $kind = self::kindForTab($tab);
        if (! $kind) {
            return null;
        }

        $isLegacyPositional = empty($headerMap) && count($row) <= 17;

        $get = function (array $spec, ?string $name = null) use ($row, $headerMap, $kind, $isLegacyPositional): ?string {
            $index = null;
            if ($isLegacyPositional && $name !== null && isset(self::LEGACY_FALLBACKS[$kind][$name])) {
                $index = self::LEGACY_FALLBACKS[$kind][$name];
            } else {
                $index = $this->locate($spec, $headerMap);
            }

            return $index !== null && isset($row[$index]) ? $this->clean($row[$index]) : null;
        };

        $f = [];
        foreach (self::FIELDS[$kind] as $name => $spec) {
            $f[$name] = $get($spec, $name);
        }
        $ata = ($isLegacyPositional && $kind === 'wo' && isset($row[6]))
            ? $this->clean($row[6])
            : ($get(self::SHARED['ata']) ?? DjaClassifier::ataFromTaskCard($get(self::SHARED['task_card'])));
        $taskCardDescription = $get(self::SHARED['task_card_description']);

        $planDateRaw = $f['plan_date'] ?? $get(self::SHARED['plan_date']);
        $refreshDateRaw = $f['refresh_date'] ?? $get(self::SHARED['refresh_date']);

        $planDate = GoogleSheetsReader::parseDate($planDateRaw);
        $refreshDate = GoogleSheetsReader::parseDate($refreshDateRaw);

        // Code reason and reason open fallbacks from shared columns
        $codeOpen = $f['code_open'] ?? $get(self::SHARED['code_open']);
        $reasonOpen = $f['reason_open'] ?? $get(self::SHARED['reason_open']);
        $f['code_open'] = $codeOpen;
        $f['reason_open'] = $reasonOpen;
        $f['ata'] = $ata;
        $f['task_card'] = $get(self::SHARED['task_card']);
        $f['task_card_description'] = $taskCardDescription;

        $acReg = $f['ac_reg'];
        $description = $f['description']
            ?: ($taskCardDescription
            ?: ($f['part_description'] ?? ($f['remarks'] ?? ($f['remarks_ppc_to_lm'] ?? null))));
        $f['description'] = $description;

        if ($acReg === null && $description === null && ($f['task_id'] ?? null) === null) {
            return null; // blank row
        }

        // Operational date rule: use plan_date as the current operational date for the record.
        // Never use refresh_date as the operational date (refresh_date is only recorded as metadata).
        $date = $planDate ?? $targetDate;
        $planStation = $f['plan_station'] ? strtoupper($f['plan_station']) : null;

        return [
            'kind' => $kind,
            'tab' => $tab,
            'job_type' => self::JOB_TYPE[$kind],
            'ac_reg' => $acReg,
            'task_id' => $f['task_id'],
            'description' => $description,
            'ata' => $ata,
            'task_card' => $get(self::SHARED['task_card']),
            // what the classifier reads: the job text plus the task card text, which often names the part
            'classify_text' => trim((string) $description.' '.($taskCardDescription !== $description ? (string) $taskCardDescription : '')),
            'category' => $kind === 'nsrdi' ? $f['category'] : ($f['wo_category'] ?? $f['dmi_category'] ?? null),
            'date' => $date,
            'plan_date' => $planDate,
            'refresh_date' => $refreshDate,
            'station' => $planStation,
            'log' => $this->logAttributes($kind, $f, $date, $planDate, $refreshDate),
        ];
    }

    /**
     * Column index of a field in a sheet, or null when the sheet has no such column.
     * Used by the sync to write back into the right cell (status, reason, close date).
     *
     * @param  array<string, int>  $headerMap
     */
    public function columnFor(string $kind, string $field, array $headerMap = []): ?int
    {
        $spec = self::FIELDS[$kind][$field] ?? self::SHARED[$field] ?? null;

        return $spec ? $this->locate($spec, $headerMap) : null;
    }

    /**
     * @param  array{0: array<int, string>, 1: int|null}  $spec
     * @param  array<string, int>  $headerMap
     */
    private function locate(array $spec, array $headerMap): ?int
    {
        [$aliases, $fallback] = $spec;

        foreach ($aliases as $alias) {
            if (isset($headerMap[$alias])) {
                return $headerMap[$alias];
            }
        }

        if ($fallback === null) {
            return null;
        }

        // Positional fallback only when the header row does not label that column as something else
        $labelHere = array_flip($headerMap)[$fallback] ?? null;

        return $labelHere === null || in_array($labelHere, $aliases, true) ? $fallback : null;
    }

    /** Attributes for wo_logs / dmi_logs / nsrdi_logs, in the same shape the Excel import always produced. */
    private function logAttributes(string $kind, array $f, ?string $date, ?string $planDate = null, ?string $refreshDate = null): array
    {
        $text = fn (?string $v, int $limit = 255) => ($v === null || str_starts_with(trim($v), '=')) ? null : mb_substr(trim($v), 0, $limit);
        $code = fn (?string $v) => ($v === null || str_starts_with(trim($v), '=')) ? null : mb_substr(strtoupper(trim($v)), 0, 255); // station codes are always upper-case

        return match ($kind) {
            'wo' => [
                'date' => $date,
                'work_group' => $text($f['work_group']),
                'aircraft_registration' => $text($f['ac_reg']),
                'wo_number' => $text($f['task_id']),
                'wo_category' => $text($f['wo_category']),
                'description' => $f['description'],
                'pn_picklist' => $text($f['pn_picklist']),
                'man_hour' => is_numeric($f['man_hour'] ?? null) ? (float) $f['man_hour'] : null,
                'operator' => $text($f['operator']),
                'type' => $text($f['type']),
                'plan_station' => $code($f['plan_station']),
                'remarks_ppc_to_lm' => $f['remarks_ppc_to_lm'],
                'act_station' => $code($f['act_station'] ?? null) ?: $code($f['plan_station']),
                'status' => $this->status($f['status'] ?? null),
                'code_open' => $text($f['code_open']),
                'reason_open' => $text($f['reason_open']),
                'hold_reason_category' => $text($f['code_open']),
                'hold_remarks' => $text($f['reason_open']),
                'ata' => $text($f['ata'] ?? null),
                'task_card' => $text($f['task_card'] ?? null),
                'task_card_description' => $f['task_card_description'] ?? null,
                'rii' => $text($f['rii'] ?? null),
                'etops' => $text($f['etops'] ?? null),
                'eru' => $text($f['eru'] ?? null),
                'iera' => $text($f['iera'] ?? null),
                'site' => $text($f['site'] ?? null),
                'due_date_wo' => GoogleSheetsReader::parseDate($f['due_date_wo'] ?? null),
                'class_wo' => $text($f['class_wo'] ?? null),
                'materials' => $f['materials'] ?? null,
                'tools' => $f['tools'] ?? null,
                'plan_date' => $planDate,
                'planner' => $text($f['planner'] ?? null),
                'ppc_onduty' => $text($f['ppc_onduty'] ?? null),
                'close_date' => GoogleSheetsReader::parseDate($f['close_date'] ?? null),
                'highlight' => $text($f['highlight'] ?? null),
                'refresh_date' => $refreshDate,
            ],
            'dmi' => [
                'date' => $date,
                'aircraft_registration' => $text($f['ac_reg']),
                'description' => $f['description'],
                'pn_required' => $text($f['pn_required']),
                'dmi_number' => $text($f['task_id']),
                'dmi_category' => $text($f['dmi_category']),
                'plan_station' => $code($f['plan_station']),
                'category' => $text($f['category']),
                'act_station' => $code($f['act_station'] ?? null) ?: $code($f['plan_station']),
                'status' => $this->status($f['status'] ?? null),
                'remarks' => $f['remarks'],
                'hold_reason_category' => $text($f['code_open'] ?? null),
                'hold_remarks' => $text($f['reason_open'] ?? null) ?: $f['remarks'],
                'work_group' => $text($f['work_group'] ?? null),
                'defer_type' => $text($f['defer_type'] ?? null),
                'status_wo' => $text($f['status_wo'] ?? null),
                'wo_number' => $text($f['wo_number'] ?? null),
                'ata' => $text($f['ata'] ?? null),
                'due_date_dmi' => GoogleSheetsReader::parseDate($f['due_date_dmi'] ?? null),
                'picklist_number' => $text($f['picklist_number'] ?? null),
                'aoc' => $text($f['aoc'] ?? null),
                'type' => $text($f['type'] ?? null),
                'plan_date' => $planDate,
                'close_date' => GoogleSheetsReader::parseDate($f['close_date'] ?? null),
                'highlight' => $text($f['highlight'] ?? null),
                'operator' => $text($f['operator'] ?? null),
                'refresh_date' => $refreshDate,
            ],
            'nsrdi' => [
                'plan_date' => $planDate ?? $date,
                'refresh_date' => $refreshDate,
                'work_group' => $text($f['work_group']),
                'aircraft_registration' => $text($f['ac_reg']),
                'nsrdi_number' => $text($f['task_id']),
                'description' => $f['description'],
                'category' => $text($f['category']),
                'report_date' => GoogleSheetsReader::parseDate($f['report_date'] ?? null) ?? $date,
                'due_date' => GoogleSheetsReader::parseDate($f['due_date'] ?? null),
                'part_number' => $text($f['part_number']),
                'part_description' => $f['part_description'],
                'defer' => $text($f['defer']),
                'aoc' => $text($f['aoc']),
                'type' => $text($f['type']),
                'plan_station' => $code($f['plan_station']),
                'act_station' => $code($f['act_station'] ?? null) ?: $code($f['plan_station']),
                'remarks' => $f['remarks'],
                'status' => $this->status($f['status'] ?? null, $f['close_date'] ?? null),
                'close_date' => GoogleSheetsReader::parseDate($f['close_date'] ?? null),
                'code_open' => $text($f['code_open'] ?? null),
                'reason_open' => $text($f['reason_open'] ?? null),
                'hold_reason_category' => $text($f['code_open'] ?? null),
                'hold_remarks' => $text($f['reason_open'] ?? null) ?: $f['remarks'],
            ],
        };
    }

    public static function normalizeStatus(?string $value, ?string $closeDate = null): string
    {
        $v = strtoupper(trim((string) $value));

        if (
            str_starts_with($v, 'CLOSE') ||
            str_starts_with($v, 'CLS') ||
            $v === 'C' ||
            str_starts_with($v, 'DONE') ||
            str_starts_with($v, 'COMPLETE') ||
            str_starts_with($v, 'SELESAI')
        ) {
            return 'Closed';
        }

        if (! empty($closeDate) && GoogleSheetsReader::parseDate($closeDate) !== null) {
            return 'Closed';
        }

        if (in_array($v, ['PENDING', 'HOLD', 'WAITING'], true) || str_starts_with($v, 'HOLD')) {
            return 'Pending';
        }

        return 'Open';
    }

    private function status(?string $value, ?string $closeDate = null): string
    {
        return self::normalizeStatus($value, $closeDate);
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' ? null : $value;
    }
}
