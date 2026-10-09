<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Colour state of a document with an end date: ok / yellow / red / expired / none.
 * Windows are in months and come from config/hr.php.
 */
class ExpiryStatus
{
    public const NONE = 'none';

    public const OK = 'ok';

    public const YELLOW = 'yellow';

    public const RED = 'red';

    public const EXPIRED = 'expired';

    public static function for(?CarbonInterface $end, int $yellowMonths, int $redMonths, ?CarbonInterface $today = null): string
    {
        if (! $end) {
            return self::NONE;
        }
        $today = ($today ?? now())->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        if ($end->lt($today)) {
            return self::EXPIRED;
        }
        if ($end->lte($today->copy()->addMonthsNoOverflow($redMonths))) {
            return self::RED;
        }
        if ($end->lte($today->copy()->addMonthsNoOverflow($yellowMonths))) {
            return self::YELLOW;
        }

        return self::OK;
    }
}
