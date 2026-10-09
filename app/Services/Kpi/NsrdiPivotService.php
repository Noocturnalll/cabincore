<?php

namespace App\Services\Kpi;

use App\Models\Aoc;
use App\Models\MasterEntry;
use App\Services\Audit\AocResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * The Thursday-meeting pivot of the resume workbook ("NSRDI IU - ACT PER STA CBM", "NSRDIL AOC - CBM"):
 * for each DJA day of a week, how many NSRDI were DEPLOYED to a station and how many of them ended CLOSED,
 * per AOC, station and defer type (CBM / PAINTING), against the target per day kept in Data Master.
 *
 * A job belongs to the day of the DJA it was deployed on, whenever it is closed. Unplanned NSRDI (no DJA) are not part
 * of this pivot, exactly like the workbook, which keeps them on their own tab.
 */
class NsrdiPivotService
{
    public const CATEGORIES = ['CBM', 'PAINTING'];

    /** Order the stations appear in the workbook; others follow alphabetically. */
    private const STATION_ORDER = ['CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC', 'BPN', 'AMQ', 'SRG', 'PLM', 'DPS', 'KOE', 'LOP', 'SOC', 'PDG', 'PKU', 'BTH', 'DJB', 'YIA'];

    /** First day of the pivot week that contains $date (Friday by default: DJA of 2 Oct runs through 8 Oct). */
    public static function weekStart(CarbonInterface $date): Carbon
    {
        $d = Carbon::instance($date)->startOfDay();
        $offset = ($d->dayOfWeek - (int) config('kpi.nsrdi_week_starts_on', 5) + 7) % 7;

        return $d->subDays($offset);
    }

    /**
     * @param  array<int, string>|null  $stations  null = all stations
     * @return array{days: array<int, string>, pending: array<int, bool>, aocs: array<string, array>, overview: array}
     */
    public function build(CarbonInterface $weekStart, ?array $stations = null): array
    {
        $start = Carbon::instance($weekStart)->startOfDay();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i)->toDateString();
        }
        // a day that has not finished yet shows blue zeros, like the workbook
        $pending = array_map(fn ($d) => $d >= now()->toDateString(), $days);

        $rows = DB::table('nsrdi_logs as n')
            ->join('daily_job_assignments as d', 'd.id', '=', 'n.dja_id')
            ->whereDate('d.date', '>=', $days[0])->whereDate('d.date', '<=', $days[6])
            ->select('d.date as dja_date', 'n.aircraft_registration', 'n.aoc', 'n.category', 'n.status', DB::raw('COALESCE(n.act_station, n.plan_station) as station'))
            ->get();

        $resolver = app(AocResolver::class);
        $cells = [];   // aoc => station => category => day index => [deploy, closed]
        foreach ($rows as $r) {
            $station = strtoupper(trim((string) $r->station));
            if ($station === '' || ($stations !== null && ! in_array($station, $stations, true))) {
                continue;
            }
            $aoc = $this->aocCode($resolver, $r->aircraft_registration, $r->aoc);
            if (! $aoc) {
                continue;
            }
            $cat = strtoupper((string) $r->category) === 'PAINTING' ? 'PAINTING' : 'CBM';
            $day = array_search(substr((string) $r->dja_date, 0, 10), $days, true);
            if ($day === false) {
                continue;
            }
            $cells[$aoc][$station][$cat][$day][0] = ($cells[$aoc][$station][$cat][$day][0] ?? 0) + 1;
            $cells[$aoc][$station][$cat][$day][1] = ($cells[$aoc][$station][$cat][$day][1] ?? 0) + (strcasecmp((string) $r->status, 'Closed') === 0 ? 1 : 0);
        }

        $targets = MasterEntry::where('type', 'kpi_target')->where('is_active', true)->get()
            ->groupBy(fn ($e) => strtoupper($e->attrs['aoc'] ?? ''))
            ->map(fn ($g) => $g->mapWithKeys(fn ($e) => [strtoupper($e->attrs['station'] ?? '') => (float) ($e->attrs['target'] ?? 0)]));

        $aocCodes = Aoc::where('include_in_report', true)->orderBy('sort_order')->pluck('name', 'code');
        $out = [];
        foreach ($aocCodes as $code => $name) {
            $cap = ($targets[$code] ?? collect())->filter(fn ($v, $sta) => $stations === null || in_array($sta, $stations, true));
            $stationsHere = collect(array_keys($cells[$code] ?? []))->merge($cap->filter(fn ($v) => $v > 0)->keys())->unique()
                ->sortBy(fn ($s) => [($i = array_search($s, self::STATION_ORDER, true)) === false ? 99 : $i, $s])->values();

            $rowsOut = [];
            foreach ($stationsHere as $station) {
                $cats = [];
                foreach (self::CATEGORIES as $cat) {
                    $perDay = [];
                    for ($i = 0; $i < 7; $i++) {
                        $perDay[$i] = $cells[$code][$station][$cat][$i] ?? [0, 0];
                    }
                    $deploy = array_sum(array_column($perDay, 0));
                    if ($cat === 'PAINTING' && $deploy === 0) {
                        continue;   // a painting row only exists where painting work was deployed
                    }
                    $cats[$cat] = ['days' => $perDay, 'deploy' => $deploy, 'closed' => array_sum(array_column($perDay, 1))];
                }
                $rowsOut[$station] = ['capacity' => (float) ($cap[$station] ?? 0), 'categories' => $cats];
            }

            $out[$code] = [
                'name' => $name, 'stations' => $rowsOut,
                'capacity' => (float) $cap->sum(),
                'totals' => $this->totals($rowsOut, null),
                'totals_by_category' => [
                    'CBM' => $this->totals($rowsOut, 'CBM'),
                    'PAINTING' => $this->totals($rowsOut, 'PAINTING'),
                ],
            ];
        }

        $overview = ['days' => array_fill(0, 7, [0, 0]), 'deploy' => 0, 'closed' => 0, 'capacity' => 0.0];
        foreach ($out as $a) {
            $overview['capacity'] += $a['capacity'];
            foreach ($a['totals']['days'] as $i => [$d, $c]) {
                $overview['days'][$i][0] += $d;
                $overview['days'][$i][1] += $c;
            }
            $overview['deploy'] += $a['totals']['deploy'];
            $overview['closed'] += $a['totals']['closed'];
        }

        return ['days' => $days, 'pending' => $pending, 'aocs' => $out, 'overview' => $overview];
    }

    private function aocCode(AocResolver $resolver, ?string $registration, ?string $aocText): ?string
    {
        return $resolver->resolve($registration, $aocText)?->code;
    }

    /** @param  array<string, array>  $rows */
    private function totals(array $rows, ?string $category): array
    {
        $days = array_fill(0, 7, [0, 0]);
        foreach ($rows as $row) {
            foreach ($row['categories'] as $cat => $data) {
                if ($category !== null && $cat !== $category) {
                    continue;
                }
                foreach ($data['days'] as $i => [$d, $c]) {
                    $days[$i][0] += $d;
                    $days[$i][1] += $c;
                }
            }
        }

        return ['days' => $days, 'deploy' => array_sum(array_column($days, 0)), 'closed' => array_sum(array_column($days, 1))];
    }
}
