<?php

namespace App\Services\Sources;

use App\Models\MasterEntry;
use App\Services\Master\MasterSettings;

/**
 * Resume workbook, tab TARGET: closed per day target per station (rows) and AOC (columns JT, ID, IU, IW ...).
 * Becomes Data Master > Target Closed per Hari, entry "CGK-JT" and so on. A target someone edited by hand in the master
 * is overwritten on the next sync: the sheet is the source of truth for these numbers.
 */
class TargetSync
{
    public function __construct(private ?SheetFetcher $fetcher = null)
    {
        $this->fetcher ??= app(SheetFetcher::class);
    }

    /** @return array{ok: bool, message: string} */
    public function sync(string $spreadsheetId, string $tab = 'TARGET'): array
    {
        $values = $this->fetcher->values($spreadsheetId, $tab);
        if ($values === null) {
            return ['ok' => false, 'message' => $this->fetcher->lastError() ?? 'Tab TARGET tidak terbaca.'];
        }

        $header = null;
        foreach ($values as $i => $row) {
            $aocs = [];
            foreach ($row as $col => $cell) {
                if (preg_match('/^[A-Z]{2}$/', strtoupper(trim((string) $cell)))) {
                    $aocs[$col] = strtoupper(trim((string) $cell));
                }
            }
            if (count($aocs) >= 2 && str_contains(strtoupper(implode(' ', $row)), 'TARGET')) {
                $header = [$i, $aocs];
                break;
            }
        }
        if (! $header) {
            return ['ok' => false, 'message' => 'Header target (kolom AOC) tidak ditemukan di tab ini.'];
        }

        [$headerRow, $aocs] = $header;
        $saved = 0;
        foreach (array_slice($values, $headerRow + 1) as $row) {
            $station = strtoupper(trim((string) ($row[1] ?? '')));
            if (! preg_match('/^[A-Z]{3}$/', $station)) {
                continue;
            }
            foreach ($aocs as $col => $aoc) {
                $value = $row[$col] ?? null;
                if (! is_numeric($value)) {
                    continue;
                }
                MasterEntry::updateOrCreate(
                    ['type' => 'kpi_target', 'code' => "{$station}-{$aoc}"],
                    ['label' => "{$station} / {$aoc}", 'attrs' => ['station' => $station, 'aoc' => $aoc, 'target' => (float) $value + 0], 'is_active' => true]
                );
                $saved++;
            }
        }
        MasterSettings::flush();

        return ['ok' => $saved > 0, 'message' => $saved ? "{$saved} target station/AOC disimpan." : 'Tidak ada angka target yang terbaca.'];
    }
}
