<?php

namespace App\Livewire\Reports;

use App\Models\DocumentAccuracy as Accuracy;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Document accuracy: of the CML / NSRDI documents the stations reported, how many were recorded in eMRO
 * (recorded = reported - missed). Daily, weekly (Thursday - Wednesday) or monthly.
 */
class DocumentAccuracy extends Component
{
    public string $kind = 'week';

    public string $date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kpi.view'), 403);
        $this->date = now()->subDay()->toDateString();
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
        $period = ReportPeriod::of($this->kind, Carbon::parse($this->date ?: now()));
        $user = auth()->user();
        $stationOnly = ! $user->can('registry.view_all') && $user->station ? strtoupper($user->station) : null;

        $rows = Accuracy::query()
            ->whereDate('work_date', '>=', $period->from->toDateString())->whereDate('work_date', '<=', $period->to->toDateString())
            ->when($stationOnly, fn ($q) => $q->where('station', $stationOnly))
            ->select('station', DB::raw('SUM(cml_reported) cml'), DB::raw('SUM(nsrdi_reported) nsrdi'), DB::raw('SUM(cml_missed) cml_missed'), DB::raw('SUM(nsrdi_missed) nsrdi_missed'))
            ->groupBy('station')->orderBy('station')->get()
            ->map(function ($r) {
                $reported = (int) $r->cml + (int) $r->nsrdi;
                $missed = (int) $r->cml_missed + (int) $r->nsrdi_missed;

                return [
                    'station' => $r->station, 'cml' => (int) $r->cml, 'nsrdi' => (int) $r->nsrdi,
                    'reported' => $reported, 'missed' => $missed, 'recorded' => $reported - $missed,
                    'percent' => $reported ? round(($reported - $missed) / $reported * 100, 1) : null,
                ];
            });

        $total = ['reported' => $rows->sum('reported'), 'missed' => $rows->sum('missed')];
        $total['recorded'] = $total['reported'] - $total['missed'];
        $total['percent'] = $total['reported'] ? round($total['recorded'] / $total['reported'] * 100, 1) : null;

        $notes = Accuracy::query()
            ->whereDate('work_date', '>=', $period->from->toDateString())->whereDate('work_date', '<=', $period->to->toDateString())
            ->when($stationOnly, fn ($q) => $q->where('station', $stationOnly))
            ->where(fn ($q) => $q->where('cml_missed', '>', 0)->orWhere('nsrdi_missed', '>', 0))
            ->orderByDesc('work_date')->limit(30)->get();

        return view('livewire.reports.document-accuracy', compact('period', 'rows', 'total', 'notes'))
            ->layout('components.layouts.app', ['title' => 'KPI Document Accuracy']);
    }
}
