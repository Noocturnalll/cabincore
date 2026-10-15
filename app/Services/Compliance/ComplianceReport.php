<?php

namespace App\Services\Compliance;

use App\Models\ComplianceEntry;
use App\Models\RosterEntry;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Compliance per station for a period and a unit: how many station-shift reports were expected, how many arrived,
 * and how many arrived with all three photos (briefing, attendant list, 5R).
 *
 * What is "expected" depends on the unit (config/compliance.php):
 *   AIEC      the stations and shifts in the config (the sheet's form);
 *   CBM / PAINTING   the shifts that have people of that team in the roster that day;
 *   EXTRA     nothing is expected; the report only lists what was uploaded.
 * Today is never counted as expected - its shifts may still be running.
 */
class ComplianceReport
{
    /**
     * @param  array<int, string>|null  $onlyStations  null = every station of the unit
     * @return array{stations: array<string, array>, total: array, unit: string}
     */
    public function build(CarbonInterface $from, CarbonInterface $to, ?array $onlyStations = null, string $unit = 'AIEC'): array
    {
        $from = Carbon::instance($from)->startOfDay();
        $toDay = Carbon::instance($to)->startOfDay();
        $lastExpected = $toDay->copy()->min(now()->startOfDay()->subDay());

        $entries = $this->entries($unit, $from, $toDay, $onlyStations);
        $expected = $this->expected($unit, $from, $lastExpected, $onlyStations);

        // free units list whatever has been uploaded; the others list their expected stations
        $codes = $unit === 'EXTRA'
            ? $entries->map(fn ($e) => $e->station)->unique()->sort()->values()->all()
            : array_keys($expected);

        $stations = [];
        foreach ($codes as $code) {
            $row = ['expected' => 0, 'submitted' => 0, 'complete' => 0, 'docs' => ['brf' => 0, 'att' => 0, '5r' => 0], 'missing' => [], 'incomplete' => [], 'shifts' => [], 'slots' => []];

            if ($unit === 'EXTRA') {
                foreach ($entries->filter(fn ($e) => $e->station === $code) as $entry) {
                    $row['submitted']++;
                    $this->countDocs($row, $entry);
                    $entry->isComplete() ? $row['complete']++ : null;
                }
                $row['percent'] = null;
                $stations[$code] = $row;

                continue;
            }

            foreach ($expected[$code] as [$date, $shift]) {
                $row['expected']++;
                $row['shifts'][$shift] = true;
                $row['slots'][] = [$date, $shift];
                $entry = $entries->get($code.'|'.$date.'|'.$shift);
                if (! $entry || ! ($entry->url_brf || $entry->url_att || $entry->url_5r)) {
                    $row['missing'][] = ['date' => $date, 'shift' => $shift];

                    continue;
                }
                $row['submitted']++;
                $this->countDocs($row, $entry);
                if ($entry->isComplete()) {
                    $row['complete']++;
                } else {
                    $row['incomplete'][] = [
                        'date' => $date, 'shift' => $shift,
                        'lacking' => array_keys(array_filter(['brf' => ! $entry->url_brf, 'att' => ! $entry->url_att, '5r' => ! $entry->url_5r])),
                    ];
                }
            }

            $row['shifts'] = array_keys($row['shifts']);
            $row['percent'] = $row['expected'] ? round($row['complete'] / $row['expected'] * 100, 1) : null;
            $stations[$code] = $row;
        }

        $total = ['expected' => 0, 'submitted' => 0, 'complete' => 0];
        foreach ($stations as $row) {
            foreach ($total as $k => $_) {
                $total[$k] += $row[$k];
            }
        }
        $total['percent'] = $total['expected'] ? round($total['complete'] / $total['expected'] * 100, 1) : null;

        return ['stations' => $stations, 'total' => $total, 'unit' => $unit];
    }

    /**
     * The (date, shift) lines of one station that can carry evidence, today included (today is not counted as
     * expected in the report, but its evidence may be uploaded while the shift runs).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function slots(string $unit, string $station, CarbonInterface $from, CarbonInterface $to): array
    {
        $expected = $this->expected($unit, Carbon::instance($from)->startOfDay(), Carbon::instance($to)->startOfDay(), [$station]);

        return $expected[strtoupper($station)] ?? $expected[$station] ?? [];
    }

    private function countDocs(array &$row, ComplianceEntry $entry): void
    {
        foreach (['brf', 'att', '5r'] as $doc) {
            $row['docs'][$doc] += $entry->{'url_'.$doc} ? 1 : 0;
        }
    }

    /**
     * One entry per station|date|shift. The sheet and an upload can both describe the same shift: their documents are
     * combined, so evidence is never hidden by a second row.
     *
     * @return Collection<string, ComplianceEntry>
     */
    private function entries(string $unit, Carbon $from, Carbon $to, ?array $onlyStations): Collection
    {
        $rows = ComplianceEntry::where('unit', $unit)
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->when($onlyStations !== null, fn ($q) => $q->whereIn('station', $onlyStations))
            ->orderBy('id')->get();

        $merged = collect();
        foreach ($rows as $e) {
            $key = $e->station.'|'.$e->work_date->toDateString().'|'.$e->shift;
            if (! $merged->has($key)) {
                $merged->put($key, $e);

                continue;
            }
            $kept = $merged->get($key);
            foreach (['url_brf', 'url_att', 'url_5r'] as $col) {
                $kept->{$col} = $kept->{$col} ?: $e->{$col};
            }
        }

        // an extra unit has several lines a day; keep every row for it
        return $unit === 'EXTRA' ? $rows->keyBy('id') : $merged;
    }

    /**
     * @return array<string, array<int, array{0: string, 1: string}>> station => [[date, shift], ...]
     */
    private function expected(string $unit, Carbon $from, Carbon $lastExpected, ?array $onlyStations): array
    {
        $cfg = config("compliance.units.$unit", []);
        $out = [];

        if (($cfg['source'] ?? '') === 'config') {
            foreach (config('compliance.stations') as $code => $station) {
                if ($onlyStations !== null && ! in_array($code, $onlyStations, true)) {
                    continue;
                }
                $start = $from->copy()->max(Carbon::parse($station['since']));
                for ($day = $start->copy(); $day->lte($lastExpected); $day->addDay()) {
                    foreach ($station['shifts'] as $shift) {
                        $out[$code][] = [$day->toDateString(), $shift];
                    }
                }
                $out[$code] ??= [];
            }

            return $out;
        }

        if (($cfg['source'] ?? '') === 'roster' && $lastExpected->gte($from)) {
            $rows = RosterEntry::query()
                ->whereIn('team', (array) ($cfg['teams'] ?? []))
                ->whereIn(DB::raw('UPPER(shift)'), array_map('strtoupper', config('compliance.shifts')))
                ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $lastExpected->toDateString())
                ->when($onlyStations !== null, fn ($q) => $q->whereIn('station', $onlyStations))
                ->select('station', DB::raw('DATE(work_date) as d'), DB::raw('UPPER(shift) as s'))->distinct()
                ->orderBy('station')->orderBy('d')->get();

            foreach ($rows as $r) {
                $out[strtoupper($r->station)][] = [$r->d, ucfirst(strtolower($r->s))];
            }
            ksort($out);
        }

        return $out;
    }
}
