<?php

namespace App\Livewire\Reports;

use App\Models\Aoc;
use App\Services\Kpi\NsrdiPivotService;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/** NSRDI deployed vs closed per AOC, station and day for one DJA week - the pivot shown at the Thursday meeting. */
class NsrdiPivot extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'ALL';

    #[Url(as: 'd')]
    public string $date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kpi.view'), 403);
        $this->date = $this->date ?: now()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function move(int $weeks): void
    {
        $this->date = Carbon::parse($this->date)->addWeeks($weeks)->toDateString();
    }

    public function thisWeek(): void
    {
        $this->date = now()->toDateString();
    }

    public function render(NsrdiPivotService $service)
    {
        $user = auth()->user();
        $stations = ! $user->can('registry.view_all') && $user->station ? [strtoupper($user->station)] : null;

        $start = NsrdiPivotService::weekStart(Carbon::parse($this->date ?: now()));
        $data = $service->build($start, $stations);

        // tabs: the AOCs that have capacity or work this week (always the four main airlines)
        $tabs = collect($data['aocs'])->filter(fn ($a, $code) => in_array($code, ['JT', 'ID', 'IU', 'IW'], true) || $a['totals']['deploy'] > 0 || $a['capacity'] > 0);
        $tab = $this->tab === 'ALL' || $tabs->has($this->tab) ? $this->tab : 'ALL';

        return view('livewire.reports.nsrdi-pivot', [
            'start' => $start,
            'end' => $start->copy()->addDays(6),
            'data' => $data,
            'tabs' => $tabs,
            'tab' => $tab,
            'aocNames' => Aoc::pluck('name', 'code'),
        ])->layout('components.layouts.app', ['title' => 'NSRDI per AOC']);
    }
}
