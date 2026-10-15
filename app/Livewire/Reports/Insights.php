<?php

namespace App\Livewire\Reports;

use App\Services\Kpi\OperationalInsights;
use App\Services\Kpi\ReportPeriod;
use App\Support\DashboardScope;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Pivots per work group, operator, open aging, open reasons, planned vs unplanned, and man hours. */
class Insights extends Component
{
    #[Url]
    public string $kind = 'week';

    #[Url]
    public string $date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kpi.view'), 403);
        $this->date = $this->date ?: now()->toDateString();
    }

    public function shift(int $steps): void
    {
        $d = Carbon::parse($this->date);
        $this->date = match ($this->kind) {
            'month' => $d->addMonths($steps)->toDateString(),
            'week' => $d->addWeeks($steps)->toDateString(),
            default => $d->addDays($steps)->toDateString(),
        };
    }

    public function render()
    {
        $kind = in_array($this->kind, ['day', 'week', 'month'], true) ? $this->kind : 'week';
        $period = ReportPeriod::of($kind, Carbon::parse($this->date ?: now()->toDateString()));
        $data = app(OperationalInsights::class)->build($period, DashboardScope::for(auth()->user())->stations);

        return view('livewire.reports.insights', ['data' => $data, 'period' => $period])
            ->layout('components.layouts.app', ['title' => 'Pivot Lanjutan']);
    }
}
