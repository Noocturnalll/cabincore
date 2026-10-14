<?php

namespace App\Support;

/**
 * Turns whatever was typed in a station cell into a station code, or null when it is not a station.
 *   "cgk" -> CGK        "BANTEK PNK" -> PNK (technician support at PNK)        "UNSCEDHULE" -> null
 *   "=IFNA(IFS(...))" (a formula that reached the cell as text) -> null
 * A station is 3 letters (IATA); a few outstations use 4. Nothing else is accepted, so a typo can not become a station.
 */
class StationNormalizer
{
    /** words that precede the code: "BANTEK NBX", "BANTUAN PNK", "SUPPORT BDJ" */
    private const PREFIXES = ['BANTEK', 'BANTUAN', 'BANTU', 'SUPPORT', 'STA', 'STN'];

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
        if ($v === '' || $v[0] === '=' || strlen($v) > 24) {
            return null;
        }

        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($v, $prefix.' ')) {
                $v = trim(substr($v, strlen($prefix) + 1));
                break;
            }
        }

        return preg_match('/^[A-Z]{3,4}$/', $v) ? $v : null;
    }
}
