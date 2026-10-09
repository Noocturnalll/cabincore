<?php

namespace App\Services\Kpi;

/** green = target met, yellow = short by up to yellow_gap_percent, red = short by more; none = no target or no data. */
class AchievementStatus
{
    public const GREEN = 'green';

    public const YELLOW = 'yellow';

    public const RED = 'red';

    public const NONE = 'none';

    public static function for(?float $actual, ?float $target): string
    {
        if ($actual === null || $target === null || $target <= 0) {
            return self::NONE;
        }
        $achievement = $actual / $target * 100;

        if ($achievement >= 100) {
            return self::GREEN;
        }

        return (100 - $achievement) <= (float) config('kpi.yellow_gap_percent') ? self::YELLOW : self::RED;
    }
}
