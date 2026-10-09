<?php

namespace App\Services\Sources;

use App\Models\Aircraft;

/**
 * AMM workbook, tab WORKING GROUP: "WG 5" next to a list of registrations. Fills aircrafts.wg for the registrations
 * that exist in the aircraft master; everything else about the aircraft is left alone.
 */
class AmmSync
{
    public function __construct(private ?SheetFetcher $fetcher = null)
    {
        $this->fetcher ??= app(SheetFetcher::class);
    }

    /** @return array{ok: bool, message: string} */
    public function sync(string $spreadsheetId, string $tab = 'WORKING GROUP'): array
    {
        $values = $this->fetcher->values($spreadsheetId, $tab);
        if ($values === null) {
            return ['ok' => false, 'message' => $this->fetcher->lastError() ?? 'Tab WORKING GROUP tidak terbaca.'];
        }

        $updated = 0;
        $unknown = [];
        $groups = 0;
        foreach ($values as $row) {
            if (! preg_match('/^\s*WG\s*(\d+)\s*$/i', (string) ($row[0] ?? ''), $m)) {
                continue;
            }
            $wg = sprintf('WG %02d', (int) $m[1]);
            preg_match_all('/\b([A-Z]{2}-[A-Z0-9]{3})\b/', strtoupper((string) ($row[1] ?? '')), $regs);
            if (! $regs[1]) {
                continue;
            }
            $groups++;
            foreach (array_unique($regs[1]) as $reg) {
                $aircraft = Aircraft::where('registration', $reg)->first();
                if (! $aircraft) {
                    $unknown[] = $reg;

                    continue;
                }
                if ($aircraft->wg !== $wg) {
                    $aircraft->update(['wg' => $wg]);
                    $updated++;
                }
            }
        }

        $message = "{$groups} WG dibaca, {$updated} pesawat diperbarui.";
        if ($unknown) {
            $message .= ' Tidak ada di master pesawat: '.implode(', ', array_slice($unknown, 0, 8)).(count($unknown) > 8 ? ' dst. ('.count($unknown).')' : '').'.';
        }

        return ['ok' => $groups > 0, 'message' => $groups ? $message : 'Tidak ada baris "WG n" di tab ini.'];
    }
}
