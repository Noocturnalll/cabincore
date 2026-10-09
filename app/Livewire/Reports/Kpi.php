<?php

namespace App\Livewire\Reports;

use App\Services\Kpi\ManHourService;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/** KPI per period (day / week Thu-Wed / month): output per module and man hours against capacity. */
class Kpi extends Component
{
    public string $kind = 'week';

    public string $date = '';

    public function mount(): void
    {
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

    private function output(ReportPeriod $period): array
    {
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();
        $tables = [
            'WO' => ['wo_logs', 'date'],
            'DMI' => ['dmi_logs', 'date'],
            'NSRDI' => ['nsrdi_logs', 'refresh_date'],
            'CML' => ['cml_logs', 'date'],
            'Aircraft Cleaning' => ['aircraft_cleanings', 'date'],
        ];

        $rows = [];
        foreach ($tables as $label => [$table, $col]) {
            $base = DB::table($table)->whereBetween($col, [$from, $to]);
            $total = (clone $base)->count();
            $closed = (clone $base)->whereIn('status', ['Closed', 'Selesai'])->count();
            $rows[] = ['label' => $label, 'total' => $total, 'closed' => $closed, 'open' => $total - $closed, 'rate' => $total ? round($closed / $total * 100) : null];
        }

        return $rows;
    }

    public function render()
    {
        $period = ReportPeriod::of($this->kind, Carbon::parse($this->date ?: now()));

        return view('livewire.reports.kpi', [
            'period' => $period,
            'manHours' => app(ManHourService::class)->summary($period),
            'output' => $this->output($period),
        ])->layout('components.layouts.app', ['title' => 'KPI & Man Hours']);
    }
}
