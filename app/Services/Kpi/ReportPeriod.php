<?php

namespace App\Services\Kpi;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/** A closed date range [from, to] (both inclusive, start of day) for daily / weekly / monthly reporting. */
class ReportPeriod
{
    public function __construct(public readonly CarbonInterface $from, public readonly CarbonInterface $to, public readonly string $kind) {}

    public static function day(CarbonInterface $date): self
    {
        $d = Carbon::instance($date)->startOfDay();

        return new self($d, $d->copy(), 'day');
    }

    /** Thursday through Wednesday (config kpi.week_starts_on) containing $date. */
    public static function week(CarbonInterface $date, ?int $startsOn = null): self
    {
        $d = Carbon::instance($date)->startOfDay();
        $offset = ($d->dayOfWeek - ($startsOn ?? (int) config('kpi.week_starts_on')) + 7) % 7;
        $from = $d->copy()->subDays($offset);

        return new self($from, $from->copy()->addDays(6), 'week');
    }

    public static function month(CarbonInterface $date): self
    {
        $d = Carbon::instance($date);

        return new self($d->copy()->startOfMonth(), $d->copy()->endOfMonth()->startOfDay(), 'month');
    }

    public static function of(string $kind, CarbonInterface $date, ?int $weekStartsOn = null): self
    {
        return match ($kind) {
            'week' => self::week($date, $weekStartsOn),
            'month' => self::month($date),
            default => self::day($date),
        };
    }

    /** The period right before this one (yesterday, last week, last month) for "vs periode lalu" comparisons. */
    public function previous(): self
    {
        return match ($this->kind) {
            'week' => self::week($this->from->copy()->subDay()),
            'month' => self::month($this->from->copy()->subDay()),
            default => self::day($this->from->copy()->subDay()),
        };
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    public function label(): string
    {
        return $this->kind === 'day'
            ? $this->from->translatedFormat('d M Y')
            : $this->from->translatedFormat('d M').' - '.$this->to->translatedFormat('d M Y');
    }
}
