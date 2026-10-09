<?php

namespace App\Services\Kpi;

use App\Models\DocumentAccuracy;
use App\Models\LgtRecord;
use App\Models\RosterEntry;
use App\Services\Compliance\ComplianceReport;
use App\Services\Master\MasterSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One row of KPIs per station (and a total), for a period and a team:
 *   capacity  = roster person-shifts x effective hours of the shift
 *   used      = man hours logged in WO, DMI, NSRDI, CML (CBM team), NSRDI painting (Painting team), cleaning (AIEC team)
 *   utilisation, document accuracy, LGT achievement (CBM and AIEC) and compliance (briefing / attlist / 5R).
 * Team: ALL, CBM, AIEC, PAINTING or IRREG. A team without work logs (IRREG) only shows capacity.
 */
class StationKpiService
{
    public const TEAMS = ['ALL' => 'Semua tim', 'CBM' => 'CBM', 'AIEC' => 'AIEC', 'PAINTING' => 'Painting', 'IRREG' => 'Irreg', 'FINISHING' => 'Finishing'];

    /** Division name => roster team, for users who may only see their own team. */
    public const DIVISION_TEAM = ['Cabin' => 'CBM', 'AIEC' => 'AIEC', 'AIC' => 'AIEC', 'Painting' => 'PAINTING', 'Team Irreg' => 'IRREG', 'Finishing' => 'FINISHING'];

    /**
     * @param  array<int, string>|null  $stations  null = all stations
     * @return array{rows: array<int, array>, total: array, teams: array<string, array>, coverage: array}
     */
    public function build(ReportPeriod $period, ?array $stations = null, string $team = 'ALL'): array
    {
        $team = array_key_exists($team, self::TEAMS) ? $team : 'ALL';
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();

        $roster = $this->roster($from, $to, $team, $stations, $period->days());
        $used = $this->usedHours($from, $to, $team, $stations);
        $accuracy = $this->accuracy($from, $to, $stations);
        $lgt = $this->lgt($from, $to, $stations);
        $compliance = $this->compliance($period, $stations);

        $codes = collect(array_keys($roster['stations']))->merge(array_keys($used['hours']))->merge(array_keys($accuracy))
            ->merge(array_keys($lgt))->merge(array_keys($compliance))->unique()
            ->when($stations !== null, fn (Collection $c) => $c->intersect($stations))->values();

        $rows = $codes->map(function (string $station) use ($roster, $used, $accuracy, $lgt, $compliance) {
            $capacity = $roster['stations'][$station]['hours'] ?? 0;
            $hours = $used['hours'][$station] ?? null;

            return [
                'station' => $station,
                'mp_avg' => $roster['stations'][$station]['mp_avg'] ?? null,
                'capacity' => round($capacity, 1),
                'used' => $hours === null ? null : round($hours, 1),
                'utilisation' => $capacity > 0 && $hours !== null ? round($hours / $capacity * 100, 1) : null,
                'accuracy' => $accuracy[$station] ?? null,
                'lgt_cbm' => $lgt[$station]['cbm'] ?? null,
                'lgt_aiec' => $lgt[$station]['aiec'] ?? null,
                'compliance' => $compliance[$station] ?? null,
            ];
        })->sortByDesc('capacity')->values()->all();

        return [
            'rows' => $rows,
            'total' => $this->total($rows, $roster, $used, $period),
            'teams' => $roster['teams'],
            'coverage' => $used['coverage'],
        ];
    }

    /** @return array{stations: array<string, array{hours: float, mp_avg: float}>, teams: array<string, array>} */
    private function roster(string $from, string $to, string $team, ?array $stations, int $days): array
    {
        $master = app(MasterSettings::class);
        $byShift = $master->shiftHours();
        $default = config('kpi.effective_hours');
        $capacityTeams = $team === 'ALL' ? $master->capacityTeams() : [$team];

        $entries = RosterEntry::query()
            ->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)
            ->whereNotNull('shift')->whereIn('team', $master->capacityTeams())
            ->when($stations !== null, fn ($q) => $q->whereIn('station', $stations))
            ->select('station', 'team', 'shift', DB::raw('COUNT(*) as n'))
            ->groupBy('station', 'team', 'shift')->get();

        $stationsOut = [];
        $teamsOut = [];
        foreach ($entries as $e) {
            $hours = $e->n * ($byShift[$e->shift] ?? $default);
            $teamsOut[$e->team]['person_shifts'] = ($teamsOut[$e->team]['person_shifts'] ?? 0) + $e->n;
            $teamsOut[$e->team]['hours'] = ($teamsOut[$e->team]['hours'] ?? 0) + $hours;

            if (in_array($e->team, $capacityTeams, true)) {
                $stationsOut[$e->station]['hours'] = ($stationsOut[$e->station]['hours'] ?? 0) + $hours;
                $stationsOut[$e->station]['person_shifts'] = ($stationsOut[$e->station]['person_shifts'] ?? 0) + $e->n;
            }
        }
        foreach ($stationsOut as $code => $row) {
            $stationsOut[$code]['mp_avg'] = round($row['person_shifts'] / max(1, $days), 1);
        }
        foreach ($teamsOut as $name => $row) {
            $teamsOut[$name]['mp_avg'] = round($row['person_shifts'] / max(1, $days), 1);
        }

        return ['stations' => $stationsOut, 'teams' => $teamsOut];
    }

    /** @return array{hours: array<string, float>, coverage: array<string, array{records: int, timed: int}>, by_team: array<string, float>} */
    private function usedHours(string $from, string $to, string $team, ?array $stations): array
    {
        $hours = [];
        $coverage = [];
        $byTeam = [];

        $add = function (string $label, string $teamName, $query, string $stationExpr) use (&$hours, &$coverage, &$byTeam, $stations) {
            $rows = $query->when($stations !== null, fn ($q) => $q->whereIn(DB::raw($stationExpr), $stations))
                ->select(DB::raw("$stationExpr as station"), DB::raw('COUNT(*) as records'), DB::raw('SUM(CASE WHEN man_hour > 0 THEN 1 ELSE 0 END) as timed'), DB::raw('COALESCE(SUM(man_hour),0) as hours'))
                ->groupBy(DB::raw($stationExpr))->get();
            foreach ($rows as $r) {
                if (! $r->station) {
                    continue;
                }
                $station = strtoupper($r->station);
                $hours[$station] = ($hours[$station] ?? 0) + (float) $r->hours;
                $coverage[$label]['records'] = ($coverage[$label]['records'] ?? 0) + (int) $r->records;
                $coverage[$label]['timed'] = ($coverage[$label]['timed'] ?? 0) + (int) $r->timed;
                $byTeam[$teamName] = ($byTeam[$teamName] ?? 0) + (float) $r->hours;
            }
        };

        $range = fn (string $table, string $col) => DB::table($table)->whereDate($col, '>=', $from)->whereDate($col, '<=', $to);
        $cbm = in_array($team, ['ALL', 'CBM'], true);
        $painting = in_array($team, ['ALL', 'PAINTING'], true);
        $aiec = in_array($team, ['ALL', 'AIEC'], true);

        if ($cbm) {
            $add('WO', 'CBM', $range('wo_logs', 'date'), 'COALESCE(act_station, plan_station)');
            $add('DMI', 'CBM', $range('dmi_logs', 'date'), 'COALESCE(act_station, plan_station)');
            $add('NSRDI', 'CBM', $range('nsrdi_logs', 'refresh_date')->whereRaw("COALESCE(category,'') <> 'PAINTING'"), 'COALESCE(act_station, plan_station)');
            $add('CML', 'CBM', $range('cml_logs', 'date'), 'station');
        }
        if ($painting) {
            $add('NSRDI Painting', 'PAINTING', $range('nsrdi_logs', 'refresh_date')->where('category', 'PAINTING'), 'COALESCE(act_station, plan_station)');
        }
        if ($aiec) {
            $add('Cleaning', 'AIEC', $range('aircraft_cleanings', 'date'), 'station');
        }

        return ['hours' => $hours, 'coverage' => $coverage, 'by_team' => $byTeam];
    }

    /** @return array<string, float> station => percent */
    private function accuracy(string $from, string $to, ?array $stations): array
    {
        return DocumentAccuracy::query()
            ->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)
            ->when($stations !== null, fn ($q) => $q->whereIn('station', $stations))
            ->select('station', DB::raw('SUM(cml_reported + nsrdi_reported) as reported'), DB::raw('SUM(cml_missed + nsrdi_missed) as missed'))
            ->groupBy('station')->get()
            ->filter(fn ($r) => $r->reported > 0)
            ->mapWithKeys(fn ($r) => [$r->station => round(($r->reported - $r->missed) / $r->reported * 100, 1)])->all();
    }

    /** @return array<string, array{cbm: ?float, aiec: ?float}> */
    private function lgt(string $from, string $to, ?array $stations): array
    {
        $rows = LgtRecord::query()
            ->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)
            ->when($stations !== null, fn ($q) => $q->whereIn('station', $stations))
            ->get(['station', 'cbm_status', 'aiec_status'])->groupBy('station');

        return $rows->map(function ($list) {
            $percent = function (string $field) use ($list) {
                $total = $list->whereNotNull($field)->count();

                return $total ? round($list->where($field, 'CLOSED')->count() / $total * 100, 1) : null;
            };

            return ['cbm' => $percent('cbm_status'), 'aiec' => $percent('aiec_status')];
        })->all();
    }

    /** @return array<string, float> station => percent of expected reports that arrived with all three photos */
    private function compliance(ReportPeriod $period, ?array $stations): array
    {
        $report = app(ComplianceReport::class)->build($period->from, $period->to, $stations);

        return collect($report['stations'])->filter(fn ($s) => $s['percent'] !== null)->map(fn ($s) => $s['percent'])->all();
    }

    private function total(array $rows, array $roster, array $used, ReportPeriod $period): array
    {
        $capacity = array_sum(array_column($rows, 'capacity'));
        $usedSum = array_sum(array_map(fn ($r) => (float) $r['used'], $rows));
        $avg = fn (string $key) => ($vals = array_filter(array_column($rows, $key), fn ($v) => $v !== null)) ? round(array_sum($vals) / count($vals), 1) : null;

        return [
            'mp_avg' => round(array_sum(array_column($rows, 'mp_avg')), 1),
            'capacity' => round($capacity, 1),
            'used' => round($usedSum, 1),
            'utilisation' => $capacity > 0 ? round($usedSum / $capacity * 100, 1) : null,
            'accuracy' => $avg('accuracy'),
            'lgt_cbm' => $avg('lgt_cbm'),
            'lgt_aiec' => $avg('lgt_aiec'),
            'compliance' => $avg('compliance'),
        ];
    }
}
