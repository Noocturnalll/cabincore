<?php

namespace App\Support;

/**
 * Man hours from the CML sheet's START / FINISH PERFORM cells.
 *
 * The sheet writes a time as hours.minutes with a decimal comma: 17,4 = 17:40, 18,5 = 18:50, 20 = 20:00, 19,05 = 19:05.
 * "17:40" and Excel time fractions are understood too. A finish earlier than the start means the job crossed midnight.
 */
class ManHours
{
    /** @return int|null minutes since midnight */
    public static function minutes(mixed $value): ?int
    {
        $v = trim((string) $value);
        if ($v === '' || $v === '-') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $v, $m)) {
            return self::clock((int) $m[1], (int) $m[2]);
        }

        $v = str_replace(',', '.', $v);
        if (! is_numeric($v)) {
            return null;
        }

        // an Excel time fraction (0.7361 = 17:40) has many decimals; 0,3 is 00:30 in the sheet's notation
        if ((float) $v > 0 && (float) $v < 1 && strlen((string) substr(strstr($v, '.'), 1)) > 2) {
            return (int) round((float) $v * 1440) % 1440;
        }

        [$h, $frac] = array_pad(explode('.', $v, 2), 2, '');
        $min = $frac === '' ? 0 : (int) str_pad(substr($frac, 0, 2), 2, '0');

        return self::clock((int) $h, $min);
    }

    /** "HH:MM" for display, or null */
    public static function clockText(mixed $value): ?string
    {
        $m = self::minutes($value);

        return $m === null ? null : sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    /** @return float|null hours between start and finish */
    public static function duration(mixed $start, mixed $finish): ?float
    {
        $a = self::minutes($start);
        $b = self::minutes($finish);
        if ($a === null || $b === null) {
            return null;
        }
        $diff = $b - $a;
        if ($diff < 0) {
            $diff += 1440;
        }

        return round($diff / 60, 2);
    }

    private static function clock(int $h, int $m): ?int
    {
        return $h > 23 || $m > 59 ? null : $h * 60 + $m;
    }
}
