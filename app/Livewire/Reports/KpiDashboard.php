<?php

namespace App\Livewire\Reports;

use App\Helpers\RoleHelper;
use App\Models\Division;
use App\Services\Kpi\AttentionList;
use App\Services\Kpi\ModuleTiles;
use App\Services\Kpi\OperationalCharts;
use App\Services\Kpi\OperationalPivot;
use App\Services\Kpi\ReportPeriod;
use App\Services\Kpi\StationKpiService;
use App\Services\Master\MasterSettings;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * KPI dashboard per station and per team: man hours against roster capacity, document accuracy, LGT, compliance.
 * Managers (registry.view_all) see every station and pick the team; everyone else is held to their own station
 * and, when their division is a roster team, to that team.
 */
class KpiDashboard extends Component
{
    #[Url(as: 'p')]
    public string $kind = 'week';

    #[Url(as: 'd')]
    public string $date = '';

    #[Url(as: 'sta')]
    public string $station = '';

    #[Url(as: 'tim')]
    public string $teamPick = 'ALL';

    public string $sort = 'capacity';

    public string $dir = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kpi.view'), 403);
        $this->date = $this->date ?: now()->toDateString();
    }

    private function seesAll(): bool
    {
        return (bool) auth()->user()->can('registry.view_all');
    }

    /** The five PICs and the COD desk follow their division across every station, but only their own team. */
    private function leadsDivision(): bool
    {
        return auth()->user()->hasAnyRole([...RoleHelper::ALL_PIC, ...RoleHelper::COD_DESK]);
    }

    /** @return array{0: ?array<int, string>, 1: string} stations the user may see (null = all) and the team in force */
    private function scope(): array
    {
        $user = auth()->user();
        if ($this->seesAll()) {
            return [$this->station !== '' ? [strtoupper($this->station)] : null, $this->teamPick];
        }
        if ($this->leadsDivision()) {
            $team = app(MasterSettings::class)->teamForDivision($user->division_id) ?? 'ALL';

            return [$this->station !== '' ? [strtoupper($this->station)] : null, $team];
        }

        $team = app(MasterSettings::class)->teamForDivision($user->division_id) ?? 'ALL';

        return [$user->station ? [strtoupper($user->station)] : null, $team];
    }

    public function setKind(string $kind): void
    {
        $this->kind = in_array($kind, ['day', 'week', 'month'], true) ? $kind : 'week';
    }

    public function move(int $steps): void
    {
        $d = Carbon::parse($this->date);
        $next = match ($this->kind) {
            'day' => $d->addDays($steps),
            'week' => $d->addWeeks($steps),
            default => $d->addMonthsNoOverflow($steps),
        };
        $this->date = $next->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    public function sortBy(string $key): void
    {
        $this->dir = $this->sort === $key && $this->dir === 'desc' ? 'asc' : 'desc';
        $this->sort = $key;
    }

    public function render(StationKpiService $service)
    {
        $period = ReportPeriod::of($this->kind, Carbon::parse($this->date ?: now()));
        [$stations, $team] = $this->scope();

        $data = $service->build($period, $stations, $team);
        $tiles = app(ModuleTiles::class)->build(auth()->user(), $period, $stations);
        $attention = app(AttentionList::class)->build(auth()->user(), $stations);
        $pivot = app(OperationalPivot::class)->build(auth()->user(), $period, $stations);

        $sortable = ['station', 'mp_avg', 'capacity', 'used', 'utilisation', 'accuracy', 'lgt_cbm', 'compliance'];
        $key = in_array($this->sort, $sortable, true) ? $this->sort : 'capacity';
        $charts = app(OperationalCharts::class)->build($pivot, $data['rows']);
        $rows = collect($data['rows'])->sortBy(fn ($r) => $r[$key] ?? ($this->dir === 'asc' ? PHP_INT_MAX : -1), SORT_NATURAL, $this->dir === 'desc')->values();

        return view('livewire.reports.kpi-dashboard', [
            'period' => $period,
            'tiles' => $tiles,
            'attention' => $attention,
            'pivot' => $pivot,
            'charts' => $charts,
            'canPickStation' => $this->seesAll() || $this->leadsDivision(),
            'tileGroups' => ModuleTiles::GROUPS,
            'rows' => $rows,
            'total' => $data['total'],
            'teamsData' => $data['teams'],
            'coverage' => $data['coverage'],
            'team' => $team,
            'seesAll' => $this->seesAll(),
            'teamOptions' => ['ALL' => 'Semua tim'] + array_intersect_key(app(MasterSettings::class)->teamLabels(), array_flip(['CBM', 'AIEC', 'PAINTING', 'IRREG', 'FINISHING'])),
            'stationOptions' => collect(config('compliance.stations'))->keys()->merge(['UPG', 'DPS', 'PLM', 'PDG', 'SOC', 'SRG', 'LOP', 'PKU', 'YIA', 'KUL'])->unique()->sort()->values(),
            'lockedStation' => (! $this->seesAll() && ! $this->leadsDivision()) ? (auth()->user()->station ?: null) : null,
        ])->layout('components.layouts.app', ['title' => 'Dashboard KPI']);
    }
}
