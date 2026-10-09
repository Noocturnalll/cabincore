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

    private const DESCRIPTION = [['DESCRIPTION', 'WO DESCRIPTION', 'TASK CARD DESCRIPTION', 'DESC'], null];

    /** @var array<string, array<string, array{0: array<int, string>, 1: int|null}>> */
    private const FIELDS = [
        'wo' => [
            'work_group' => [['WG', 'WORK GROUP'], 1],
            'ac_reg' => [self::AC_REG[0], 2],
            'task_id' => [['TASK ID', 'WO NUMBER', 'NO WO', 'WO'], 3],
            'wo_category' => [['CATEGORY', 'TRADE', 'DIVISI', 'WO CAT'], 4],
            'description' => [self::DESCRIPTION[0], 5],
            'pn_picklist' => [['PN PICKLIST'], 6],
            'man_hour' => [['MAN HOUR', 'MH'], 7],
            'operator' => [['OPERATOR'], 8],
            'type' => [['TYPE'], 9],
            'plan_station' => [['PLAN STA', 'PLAN STATION', 'STATION'], 10],
            'remarks_ppc_to_lm' => [['REMARKS PPC TO LM'], 11],
            'act_station' => [['ACT STA', 'ACT STATION'], 12],
            'status' => [['STATUS'], 13],
            'code_open' => [['CODE REASON', 'CODE OPEN'], 14],
            'reason_open' => [['REASON OPEN'], 15],
        ],
        'dmi' => [
            'ac_reg' => [self::AC_REG[0], 1],
            'description' => [self::DESCRIPTION[0], 2],
            'pn_required' => [['PN REQUIRED', 'PN'], 3],
            'task_id' => [['TASK ID', 'DMI NO', 'DMI NUMBER'], 4],
            'dmi_category' => [['DMI CAT', 'DMI CATEGORY'], 5],
            'plan_station' => [['PLAN STA', 'PLAN STATION', 'STATION'], 6],
            'category' => [['CAT', 'CATEGORY'], 7],
            'status' => [['STATUS'], 8],
            'remarks' => [['REMARK', 'REMARKS'], 9],
        ],
        'nsrdi' => [
            'work_group' => [['WG', 'WORK GROUP'], 1],
            'ac_reg' => [self::AC_REG[0], 2],
            'task_id' => [['TASK ID', 'NSRDI', 'NSRDI NO', 'NSRDI NUMBER'], 3],
            'description' => [self::DESCRIPTION[0], 4],
            'category' => [['CATEGORY', 'TRADE', 'DIVISI'], 5],
            'report_date' => [['REPORT DATE'], 6],
            'due_date' => [['DUE DATE'], 7],
            'part_number' => [['PART NUMBER', 'PN'], 8],
            'part_description' => [['PART DESCRIPTION'], 9],
            'defer' => [['DEFER'], 10],
            'aoc' => [['AOC'], 11],
            'type' => [['TYPE'], 12],
            'plan_station' => [['PLAN STA', 'PLAN STATION', 'STATION'], 13],
            'remarks' => [['REMARKS', 'REMARK'], 14],
            'status' => [['STATUS'], 15],
            'close_date' => [['CLOSE DATE'], 16],
        ],
    ];

    /** Columns used only for classification / dates, never copied to a log. */
    private const SHARED = [
        'ata' => [['ATA', 'ATA CHAPTER', 'CHAPTER'], null],
        'task_card' => [['TASK CARD'], null],
        'task_card_description' => [['TASK CARD DESCRIPTION'], null],
        'refresh_date' => [['REFRESH DATE'], 20],
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
        array_push($names, 'ATA', 'REFRESH DATE');

        return array_values(array_unique($names));
    }

    public static function kindForTab(string $tab): ?string
    {
        return self::KIND_BY_TAB[strtoupper(trim($tab))] ?? null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $headerMap  header name => column index (empty = positional only)
     * @return array<string, mixed>|null null when the row is blank
     */
    public function map(string $tab, array $row, array $headerMap = []): ?array
    {
        $kind = self::kindForTab($tab);
        if (! $kind) {
            return null;
        }

        $get = function (array $spec) use ($row, $headerMap): ?string {
            $index = $this->locate($spec, $headerMap);

            return $index !== null && isset($row[$index]) ? $this->clean($row[$index]) : null;
        };

        $f = [];
        foreach (self::FIELDS[$kind] as $name => $spec) {
            $f[$name] = $get($spec);
        }
        $ata = $get(self::SHARED['ata']) ?? DjaClassifier::ataFromTaskCard($get(self::SHARED['task_card']));
        $taskCardDescription = $get(self::SHARED['task_card_description']);
        $refresh = $get(self::SHARED['refresh_date']);

        $acReg = $f['ac_reg'];
        $description = $f['description'];
        if ($acReg === null && $description === null && ($f['task_id'] ?? null) === null) {
            return null; // blank row
        }

        $date = GoogleSheetsReader::parseDate($refresh);
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
            'station' => $planStation,
            'log' => $this->logAttributes($kind, $f, $date),
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
        $spec = self::FIELDS[$kind][$field] ?? null;

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
    private function logAttributes(string $kind, array $f, ?string $date): array
    {
        $status = fn () => $this->status($f['status'] ?? null);
        $text = fn (?string $v, int $limit = 255) => $v === null ? null : mb_substr($v, 0, $limit);
        $code = fn (?string $v) => $v === null ? null : mb_substr(strtoupper($v), 0, 255); // station codes are always upper-case

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
                'act_station' => $code($f['act_station']),
                'status' => $status(),
                'code_open' => $text($f['code_open']),
                'reason_open' => $text($f['reason_open']),
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
                'status' => $status(),
                'remarks' => $f['remarks'],
            ],
            'nsrdi' => [
                'plan_date' => $date,
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
                'remarks' => $f['remarks'],
                'status' => $status(),
                'close_date' => GoogleSheetsReader::parseDate($f['close_date'] ?? null),
            ],
        };
    }

    private function status(?string $value): string
    {
        $value = ucfirst(strtolower(trim((string) $value)));

        return in_array($value, ['Open', 'Closed', 'Pending'], true) ? $value : 'Open';
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' ? null : $value;
    }
}
