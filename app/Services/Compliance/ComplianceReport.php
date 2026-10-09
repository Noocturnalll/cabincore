<?php

namespace App\Services\Compliance;

use App\Models\ComplianceEntry;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Compliance per station for a period: how many station-shift reports were expected (config/compliance.php),
 * how many arrived, and how many arrived with all three photos (briefing, attendant list, 5R).
 * Today is never counted as expected - its shifts may still be running.
 */
class ComplianceReport
{
    /**
     * @param  array<int, string>|null  $onlyStations  null = every configured station
     * @return array{stations: array<string, array>, total: array}
     */
    public function build(CarbonInterface $from, CarbonInterface $to, ?array $onlyStations = null): array
    {
        $from = Carbon::instance($from)->startOfDay();
        $lastExpected = Carbon::instance($to)->startOfDay()->min(now()->startOfDay()->subDay());

        $entries = ComplianceEntry::whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', Carbon::instance($to)->toDateString())
            ->when($onlyStations !== null, fn ($q) => $q->whereIn('station', $onlyStations))
            ->get()
            ->keyBy(fn ($e) => $e->station.'|'.$e->work_date->toDateString().'|'.$e->shift);

        $stations = [];
        foreach (config('compliance.stations') as $code => $cfg) {
            if ($onlyStations !== null && ! in_array($code, $onlyStations, true)) {
                continue;
            }

            $start = $from->copy()->max(Carbon::parse($cfg['since']));
            $row = ['expected' => 0, 'submitted' => 0, 'complete' => 0, 'docs' => ['brf' => 0, 'att' => 0, '5r' => 0], 'missing' => [], 'incomplete' => []];

            for ($day = $start->copy(); $day->lte($lastExpected); $day->addDay()) {
                foreach ($cfg['shifts'] as $shift) {
                    $row['expected']++;
                    $entry = $entries->get($code.'|'.$day->toDateString().'|'.$shift);
                    if (! $entry) {
                        $row['missing'][] = ['date' => $day->toDateString(), 'shift' => $shift];

                        continue;
                    }
                    $row['submitted']++;
                    foreach (['brf', 'att', '5r'] as $doc) {
                        $row['docs'][$doc] += $entry->{'url_'.$doc} ? 1 : 0;
                    }
                    if ($entry->isComplete()) {
                        $row['complete']++;
                    } else {
                        $row['incomplete'][] = [
                            'date' => $day->toDateString(), 'shift' => $shift,
                            'lacking' => array_keys(array_filter(['brf' => ! $entry->url_brf, 'att' => ! $entry->url_att, '5r' => ! $entry->url_5r])),
                        ];
                    }
                }
            }

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

        return ['stations' => $stations, 'total' => $total];
    }
}
