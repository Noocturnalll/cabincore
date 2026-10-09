<?php

namespace App\Services\Kpi;

/**
 * Chart configurations (plain arrays, rendered by <x-kd-chart>) built from the OperationalPivot and the station KPI rows.
 * Almost everything is a percentage: closed rate, share of a type in the volume, utilisation. A chart without data is left out.
 *
 * Config: type (bar|line|doughnut), labels, datasets [label, data, color|colors, extra], percent, max, stacked, horizontal, legend.
 */
class OperationalCharts
{
    private const PALETTE = ['#60a5fa', '#34d399', '#f59e0b', '#a78bfa', '#f472b6', '#2dd4bf', '#fb923c', '#94a3b8'];

    private const TONES = ['green' => '#34d399', 'yellow' => '#f59e0b', 'red' => '#ef4444', 'none' => '#94a3b8'];

    /** Stations drawn in the per-station charts (the busiest ones) so the chart stays readable. */
    private const TOP_STATIONS = 12;

    /**
     * @param  array  $pivot  OperationalPivot::build()
     * @param  array<int, array<string, mixed>>  $stationRows  StationKpiService rows (station, utilisation, accuracy, compliance, capacity)
     * @return array{production: array<int, array>, cleaning: array<int, array>, kpi: array<int, array>}
     */
    public function build(array $pivot, array $stationRows = []): array
    {
        return [
            'production' => $this->production($pivot),
            'cleaning' => $this->cleaning($pivot),
            'kpi' => $this->kpi($stationRows),
        ];
    }

    private function tone(?float $rate): string
    {
        return self::TONES[AchievementStatus::for($rate, 100)] ?? self::TONES['none'];
    }

    private function dayLabel(string $day): string
    {
        return substr($day, 8, 2).'/'.substr($day, 5, 2);
    }

    private function production(array $pivot): array
    {
        $charts = [];
        $blocks = $pivot['blocks'];

        // closed rate per module, the whole scope
        $labels = $data = $extra = $colors = [];
        foreach ($blocks as $key => $label) {
            $c = $pivot['total'][$key] ?? null;
            if (! $c || ! $c['total']) {
                continue;
            }
            $labels[] = $label;
            $data[] = $c['rate'];
            $extra[] = "{$c['closed']} / {$c['total']} closed";
            $colors[] = $this->tone($c['rate']);
        }
        if ($labels) {
            $charts[] = ['id' => 'rates', 'title' => 'Closed rate per modul', 'sub' => 'persen pekerjaan yang closed', 'type' => 'bar', 'labels' => $labels, 'percent' => true, 'max' => 100, 'legend' => false,
                'datasets' => [['label' => 'Closed rate', 'data' => $data, 'colors' => $colors, 'extra' => $extra]]];
        }

        // closed rate per station, one bar per module
        $ops = collect($blocks)->except(['cleaning', 'unplanned']);
        $stations = collect($pivot['rows'])->map(fn ($cells) => collect($ops->keys())->sum(fn ($b) => $cells[$b]['total'] ?? 0))
            ->filter(fn ($n) => $n > 0)->sortDesc()->take(self::TOP_STATIONS)->keys()->all();
        if ($stations && $ops->isNotEmpty()) {
            $datasets = [];
            foreach ($ops->keys()->values() as $i => $b) {
                $datasets[] = [
                    'label' => $ops[$b], 'color' => self::PALETTE[$i % count(self::PALETTE)],
                    'data' => array_map(fn ($s) => $pivot['rows'][$s][$b]['rate'] ?? null, $stations),
                    'extra' => array_map(fn ($s) => isset($pivot['rows'][$s][$b]) ? "{$pivot['rows'][$s][$b]['closed']} / {$pivot['rows'][$s][$b]['total']}" : null, $stations),
                ];
            }
            $charts[] = ['id' => 'by_station', 'title' => 'Closed rate per station', 'sub' => 'WO, DMI, NSRDI, CML · station tersibuk', 'type' => 'bar', 'labels' => $stations, 'percent' => true, 'max' => 100, 'legend' => true, 'datasets' => $datasets];
        }

        // day by day closed rate
        if (count($pivot['days']) > 1 && $blocks) {
            $days = array_keys($pivot['days']);
            $datasets = [];
            foreach (array_keys($blocks) as $i => $b) {
                $series = array_map(fn ($d) => $pivot['days'][$d][$b]['rate'] ?? null, $days);
                if (count(array_filter($series, fn ($v) => $v !== null)) === 0) {
                    continue;
                }
                $datasets[] = ['label' => $blocks[$b], 'color' => self::PALETTE[$i % count(self::PALETTE)], 'data' => $series];
            }
            if ($datasets) {
                $charts[] = ['id' => 'trend', 'title' => 'Tren closed rate harian', 'sub' => 'semua station yang dipilih', 'type' => 'line', 'labels' => array_map(fn ($d) => $this->dayLabel($d), $days), 'percent' => true, 'max' => 100, 'legend' => true, 'datasets' => $datasets];
            }
        }

        return $charts;
    }

    private function cleaning(array $pivot): array
    {
        $types = $pivot['cleaning_types'];
        if (! isset($pivot['blocks']['cleaning']) || ! $types) {
            return [];
        }
        $charts = [];
        $totals = collect($types)->mapWithKeys(fn ($t) => [$t => $pivot['total']["cleaning:$t"] ?? ['total' => 0, 'closed' => 0, 'rate' => null]])->filter(fn ($c) => $c['total'] > 0);
        if ($totals->isEmpty()) {
            return [];
        }
        $sum = max(1, $totals->sum('total'));
        $colorOf = fn (string $t) => self::PALETTE[array_search($t, $types) % count(self::PALETTE)];

        // how the cleaning volume divides over the types
        $charts[] = ['id' => 'cl_share', 'title' => 'Porsi tipe cleaning', 'sub' => 'persen dari seluruh pekerjaan cleaning', 'type' => 'doughnut', 'labels' => $totals->keys()->all(), 'percent' => true, 'legend' => true,
            'datasets' => [['label' => 'Porsi', 'data' => $totals->map(fn ($c) => round($c['total'] / $sum * 100, 1))->values()->all(), 'colors' => $totals->keys()->map($colorOf)->all(), 'extra' => $totals->map(fn ($c) => number_format($c['total']).' pekerjaan')->values()->all()]]];

        // closed rate per type
        $charts[] = ['id' => 'cl_rate', 'title' => 'Closed rate per tipe', 'sub' => 'Transit, General, DCI, DCE, DBI, GCI, GCE', 'type' => 'bar', 'labels' => $totals->keys()->all(), 'percent' => true, 'max' => 100, 'legend' => false,
            'datasets' => [['label' => 'Closed rate', 'data' => $totals->pluck('rate')->values()->all(), 'colors' => $totals->map(fn ($c) => $this->tone($c['rate']))->values()->all(), 'extra' => $totals->map(fn ($c) => "{$c['closed']} / {$c['total']} closed")->values()->all()]]];

        // the mix of types at each station, 100% stacked
        $stations = collect($pivot['rows'])->map(fn ($cells) => $cells['cleaning']['total'] ?? 0)->filter()->sortDesc()->take(self::TOP_STATIONS)->keys()->all();
        if ($stations) {
            $datasets = [];
            foreach ($totals->keys() as $t) {
                $datasets[] = [
                    'label' => $t, 'color' => $colorOf($t),
                    'data' => array_map(fn ($s) => ($tot = $pivot['rows'][$s]['cleaning']['total'] ?? 0) ? round(($pivot['rows'][$s]["cleaning:$t"]['total'] ?? 0) / $tot * 100, 1) : 0, $stations),
                    'extra' => array_map(fn ($s) => number_format($pivot['rows'][$s]["cleaning:$t"]['total'] ?? 0).' pekerjaan', $stations),
                ];
            }
            $charts[] = ['id' => 'cl_station', 'title' => 'Komposisi tipe per station', 'sub' => 'persen per station · station tersibuk', 'type' => 'bar', 'horizontal' => true, 'stacked' => true, 'labels' => $stations, 'percent' => true, 'max' => 100, 'legend' => true, 'datasets' => $datasets];
        }

        // volume per day, split by type
        if (count($pivot['days']) > 1) {
            $days = array_keys($pivot['days']);
            $datasets = [];
            foreach ($totals->keys() as $t) {
                $datasets[] = ['label' => $t, 'color' => $colorOf($t), 'data' => array_map(fn ($d) => $pivot['days'][$d]["cleaning:$t"]['total'] ?? 0, $days)];
            }
            $charts[] = ['id' => 'cl_trend', 'title' => 'Volume cleaning harian', 'sub' => 'jumlah pekerjaan per tipe', 'type' => 'bar', 'stacked' => true, 'labels' => array_map(fn ($d) => $this->dayLabel($d), $days), 'percent' => false, 'legend' => true, 'datasets' => $datasets];
        }

        return $charts;
    }

    private function kpi(array $rows): array
    {
        $rows = collect($rows)->filter(fn ($r) => ($r['capacity'] ?? 0) > 0 || ($r['compliance'] ?? null) !== null)->sortByDesc('capacity')->take(self::TOP_STATIONS)->values();
        if ($rows->isEmpty()) {
            return [];
        }
        $series = [['utilisation', 'Utilisasi man hours', '#60a5fa'], ['accuracy', 'Document accuracy', '#34d399'], ['compliance', 'Compliance', '#f59e0b']];
        $datasets = [];
        foreach ($series as [$key, $label, $color]) {
            $data = $rows->map(fn ($r) => $r[$key] ?? null)->all();
            if (count(array_filter($data, fn ($v) => $v !== null)) === 0) {
                continue;
            }
            $datasets[] = ['label' => $label, 'color' => $color, 'data' => $data];
        }

        return $datasets ? [['id' => 'kpi_station', 'title' => 'KPI per station', 'sub' => 'utilisasi, accuracy, compliance · persen', 'type' => 'bar', 'labels' => $rows->pluck('station')->all(), 'percent' => true, 'max' => 100, 'legend' => true, 'datasets' => $datasets]] : [];
    }
}
