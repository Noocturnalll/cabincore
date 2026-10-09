<?php

namespace App\Livewire\Reports;

use App\Models\LgtRecord;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Long ground time monitoring: tasks planned during LGT, how many were conducted (CLOSED) and cancelled, for the
 * CBM team and the AIEC (cleaning) team. Weeks run Monday - Sunday here, like the LGT workbook (W41 = 6-12 Oct).
 */
class Lgt extends Component
{
    public string $kind = 'week';

    public string $date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kpi.view'), 403);
        $this->date = now()->toDateString();
    }

    public function setKind(string $kind): void
    {
        $this->kind = in_array($kind, ['day', 'week', 'month'], true) ? $kind : 'week';
    }

    public function shift(int $steps): void
    {
        $d = Carbon::parse($this->date);
        $next = match ($this->kind) {
            'day' => $d->addDays($steps),
            'week' => $d->addWeeks($steps),
            default => $d->addMonthsNoOverflow($steps),
        };
        $this->date = $next->toDateString();
    }

    public function render()
    {
        $period = ReportPeriod::of($this->kind, Carbon::parse($this->date ?: now()), Carbon::MONDAY);
        $user = auth()->user();
        $stationOnly = ! $user->can('registry.view_all') && $user->station ? strtoupper($user->station) : null;

        $records = LgtRecord::query()
            ->whereDate('work_date', '>=', $period->from->toDateString())->whereDate('work_date', '<=', $period->to->toDateString())
            ->when($stationOnly, fn ($q) => $q->where('station', $stationOnly))
            ->get(['station', 'cbm_status', 'aiec_status', 'reason']);

        $count = fn ($rows, string $field, string $status) => $rows->where($field, $status)->count();
        $stations = $records->groupBy('station')->map(function ($rows, $station) use ($count) {
            $row = ['station' => $station];
            foreach (['cbm' => 'cbm_status', 'aiec' => 'aiec_status'] as $team => $field) {
                $total = $rows->whereNotNull($field)->count();
                $done = $count($rows, $field, 'CLOSED');
                $row[$team] = [
                    'total' => $total, 'done' => $done, 'cancel' => $count($rows, $field, 'CANCEL'), 'open' => $count($rows, $field, 'OPEN'),
                    'percent' => $total ? round($done / $total * 100, 1) : null,
                ];
            }

            return $row;
        })->sortByDesc(fn ($r) => $r['cbm']['total'])->values();

        $totals = [];
        foreach (['cbm', 'aiec'] as $team) {
            $t = ['total' => $stations->sum("$team.total"), 'done' => $stations->sum("$team.done"), 'cancel' => $stations->sum("$team.cancel"), 'open' => $stations->sum("$team.open")];
            $t['percent'] = $t['total'] ? round($t['done'] / $t['total'] * 100, 1) : null;
            $totals[$team] = $t;
        }

        $reasons = $records->where('cbm_status', 'CANCEL')->pluck('reason')->filter()->map(fn ($r) => mb_strtoupper(trim($r)))->countBy()->sortDesc()->take(8);

        return view('livewire.reports.lgt', compact('period', 'stations', 'totals', 'reasons'))
            ->layout('components.layouts.app', ['title' => 'LGT Monitoring']);
    }
}
