<?php

namespace App\Services\Kpi;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Open / closed / rate per station and per day for the work of the period:
 *   WO, DMI and NSRDI as planned by the DJA (deployed in the period), the unplanned ones next to them, CML, and
 *   Aircraft Cleaning split by type (Transit, General, DCI, DCE, DBI, GCI, GCE, ...).
 * A block is only built for a user who has the menu behind it. A figure that fails to compute leaves its block out.
 *
 * A cell is [total, closed, open, rate]; rate is null when there is nothing to measure.
 */
class OperationalPivot
{
    /** The order the cleaning types are shown in; any other type found in the data follows. */
    public const CLEANING_TYPES = ['Transit', 'General', 'DCI', 'DCE', 'DBI', 'GCI', 'GCE'];

    /** @return array{blocks: array<string, string>, cleaning_types: array<int, string>, rows: array<string, array>, total: array, days: array<string, array>} */
    public function build(User $user, ReportPeriod $period, ?array $stations = null): array
    {
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();
        $can = fn (string $perms) => collect(explode('|', $perms))->contains(fn ($p) => $user->can($p));

        $rows = [];       // station => block => cell
        $days = [];       // day => block => cell
        $blocks = [];     // block key => label, in the order they are shown
        $types = [];

        $tally = function (array &$bucket, string $k, string $block, $r): void {
            $c = $bucket[$k][$block] ?? ['total' => 0, 'closed' => 0];
            $bucket[$k][$block] = ['total' => $c['total'] + (int) $r->total, 'closed' => $c['closed'] + (int) $r->closed];
        };
        $put = function (string $block, $data) use (&$rows, &$days, $tally): void {
            foreach ($data as $r) {
                $tally($rows, strtoupper(trim((string) ($r->st ?? ''))) ?: '–', $block, $r);
                $tally($days, substr((string) $r->day, 0, 10), $block, $r);
            }
        };

        $stationFilter = fn ($q, string $expr) => $stations === null ? $q : $q->whereIn(DB::raw($expr), $stations);
        $closedSum = fn (string $col) => "SUM(CASE WHEN LOWER(COALESCE($col, '')) = 'closed' THEN 1 ELSE 0 END)";

        // ── WO / DMI / NSRDI planned by the DJA
        $planned = [
            'wo' => ['WO', 'wo_logs', $can('menu.production')],
            'dmi' => ['DMI', 'dmi_logs', $can('menu.production')],
            'nsrdi' => ['NSRDI', 'nsrdi_logs', $can('menu.production|menu.painting')],
        ];
        foreach ($planned as $key => [$label, $table, $allowed]) {
            if (! $allowed) {
                continue;
            }
            try {
                $q = DB::table("$table as l")->join('daily_job_assignments as d', 'd.id', '=', 'l.dja_id')
                    ->whereDate('d.date', '>=', $from)->whereDate('d.date', '<=', $to);
                $q = $stationFilter($q, 'COALESCE(l.act_station, l.plan_station)');
                $put($key, $q->selectRaw("COALESCE(l.act_station, l.plan_station) as st, DATE(d.date) as day, COUNT(*) as total, {$closedSum('l.status')} as closed")->groupBy('st', 'day')->get());
                $blocks[$key] = $label;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // ── Unplanned: the same logs when no DJA task is behind them
        if ($can('menu.production|menu.painting')) {
            try {
                $sources = $can('menu.production') ? [['wo_logs', 'date'], ['dmi_logs', 'date'], ['nsrdi_logs', 'refresh_date']] : [['nsrdi_logs', 'refresh_date']];
                foreach ($sources as [$table, $dateCol]) {
                    $q = DB::table($table)->whereNull('dja_id')->whereDate($dateCol, '>=', $from)->whereDate($dateCol, '<=', $to);
                    $q = $stationFilter($q, 'COALESCE(act_station, plan_station)');
                    $put('unplanned', $q->selectRaw("COALESCE(act_station, plan_station) as st, DATE($dateCol) as day, COUNT(*) as total, {$closedSum('status')} as closed")->groupBy('st', 'day')->get());
                }
                $blocks['unplanned'] = 'Unplanned';
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // ── CML
        if ($can('menu.production')) {
            try {
                $q = $stationFilter(DB::table('cml_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'station');
                $put('cml', $q->selectRaw("station as st, DATE(date) as day, COUNT(*) as total, {$closedSum('status')} as closed")->groupBy('st', 'day')->get());
                $blocks['cml'] = 'CML';
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // ── Aircraft Cleaning, split by type
        if ($can('menu.cleaning')) {
            try {
                $q = $stationFilter(DB::table('aircraft_cleanings')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'station');
                $data = $q->selectRaw("station as st, DATE(date) as day, type, COUNT(*) as total, {$closedSum('status')} as closed, COALESCE(SUM(man_hour), 0) as hours")->groupBy('st', 'day', 'type')->get();
                $types = $data->pluck('type')->filter()->unique()->values()->all();
                usort($types, fn ($a, $b) => [array_search($a, self::CLEANING_TYPES) === false ? 99 : array_search($a, self::CLEANING_TYPES), $a] <=> [array_search($b, self::CLEANING_TYPES) === false ? 99 : array_search($b, self::CLEANING_TYPES), $b]);
                foreach ($types as $type) {
                    $put("cleaning:$type", $data->where('type', $type));
                }
                $put('cleaning', $data);
                $blocks['cleaning'] = 'Aircraft Cleaning';
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $finish = function (array $set) {
            return collect($set)->map(fn ($cells) => collect($cells)->map(function ($c) {
                $open = $c['total'] - $c['closed'];

                return ['total' => $c['total'], 'closed' => $c['closed'], 'open' => $open, 'rate' => $c['total'] ? round($c['closed'] / $c['total'] * 100, 1) : null];
            })->all())->sortKeys()->all();
        };

        $rowsOut = $finish($rows);
        $daysOut = $finish($days);

        // the whole-scope line: add up the stations
        $totals = [];
        foreach ($rows as $cells) {
            foreach ($cells as $k => $c) {
                $t = $totals[$k] ?? ['total' => 0, 'closed' => 0];
                $totals[$k] = ['total' => $t['total'] + $c['total'], 'closed' => $t['closed'] + $c['closed']];
            }
        }

        return ['blocks' => $blocks, 'cleaning_types' => $types, 'rows' => $rowsOut, 'total' => $finish(['all' => $totals])['all'] ?? [], 'days' => $daysOut];
    }
}
