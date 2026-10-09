<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The one dashboard: "Ringkasan KPI" (tiles, things to chase, KPI per station) and "Operasional" (the DJA / ICT / AC
 * charts). Both are existing components rendered inside this page, so each keeps its own period and filters.
 * Users without kpi.view only get the operational tab.
 */
class DashboardHub extends Component
{
    #[Url(as: 't')]
    public string $tab = '';

    public function mount(): void
    {
        $this->tab = $this->resolve($this->tab);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $this->resolve($tab);
    }

    private function resolve(string $tab): string
    {
        $canKpi = (bool) auth()->user()?->can('kpi.view');

        if (! $canKpi) {
            return 'ops';
        }

        return in_array($tab, ['kpi', 'ops'], true) ? $tab : 'kpi';
    }

    public function render()
    {
        return view('livewire.dashboard-hub', ['canKpi' => (bool) auth()->user()?->can('kpi.view')])
            ->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
