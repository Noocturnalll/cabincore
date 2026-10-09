<?php

namespace App\Livewire\Reports;

use App\Models\RosterEntry;
use Carbon\Carbon;
use Livewire\Component;

/** Manpower available per station, team and shift on a day, from the imported roster (the workbook's "Daily Report MP"). */
class Manpower extends Component
{
    public string $date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kpi.view'), 403);
        $this->date = now()->toDateString();
    }

    public function shift(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
    }

    public function render()
    {
        $user = auth()->user();
        $stationOnly = ! $user->can('registry.view_all') && $user->station ? strtoupper($user->station) : null;
        $teams = ['CBM', 'AIEC', 'PAINTING', 'IRREG', 'FINISHING'];

        $entries = RosterEntry::query()
            ->whereDate('work_date', $this->date ?: now()->toDateString())
            ->when($stationOnly, fn ($q) => $q->where('station', $stationOnly))
            ->get(['station', 'team', 'shift', 'shift_code']);

        $stations = $entries->groupBy('station')->map(function ($rows, $station) use ($teams) {
            $row = ['station' => $station, 'off' => $rows->whereNull('shift')->count(), 'total' => 0];
            foreach ($teams as $team) {
                foreach (['PAGI', 'SIANG', 'MALAM'] as $shift) {
                    $n = $rows->where('team', $team)->where('shift', $shift)->count();
                    $row[$team][$shift] = $n;
                    $row['total'] += $n;
                }
            }

            return $row;
        })->sortByDesc('total')->values();

        $totals = ['off' => $stations->sum('off'), 'total' => $stations->sum('total')];
        foreach ($teams as $team) {
            foreach (['PAGI', 'SIANG', 'MALAM'] as $shift) {
                $totals[$team][$shift] = $stations->sum("$team.$shift");
            }
        }

        return view('livewire.reports.manpower', compact('stations', 'totals', 'teams'))
            ->layout('components.layouts.app', ['title' => 'Manpower Harian']);
    }
}
