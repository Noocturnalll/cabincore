<?php

namespace App\Livewire\Reports;

use App\Models\Airport;
use App\Services\Reports\ProductionExecutiveService;
use Livewire\Attributes\Url;
use Livewire\Component;

class Executive extends Component
{
    #[Url(as: 'preset')]
    public string $periodPreset = 'this_month';

    #[Url(as: 'from')]
    public string $startDate = '';

    #[Url(as: 'to')]
    public string $endDate = '';

    #[Url(as: 'sta')]
    public string $station = '';

    #[Url(as: 'pillar')]
    public string $activePillar = 'all';

    public function mount(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            $this->applyPreset($this->periodPreset);
        }
    }

    public function setPreset(string $preset): void
    {
        $this->periodPreset = $preset;
        $this->applyPreset($preset);
    }

    public function updatedStartDate(): void
    {
        $this->periodPreset = 'custom';
    }

    public function updatedEndDate(): void
    {
        $this->periodPreset = 'custom';
    }

    public function setPillar(string $pillar): void
    {
        $this->activePillar = in_array($pillar, ['all', 'cbm', 'painting', 'cleaning'], true) ? $pillar : 'all';
    }

    public function resetFilters(): void
    {
        $this->station = '';
        $this->activePillar = 'all';
        $this->setPreset('this_month');
    }

    private function applyPreset(string $preset): void
    {
        $now = now();
        switch ($preset) {
            case 'today':
                $this->startDate = $now->toDateString();
                $this->endDate = $now->toDateString();
                break;
            case 'yesterday':
                $y = $now->copy()->subDay()->toDateString();
                $this->startDate = $y;
                $this->endDate = $y;
                break;
            case 'this_week':
                $this->startDate = $now->copy()->startOfWeek()->toDateString();
                $this->endDate = $now->copy()->endOfWeek()->toDateString();
                break;
            case 'last_week':
                $this->startDate = $now->copy()->subWeek()->startOfWeek()->toDateString();
                $this->endDate = $now->copy()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'this_month':
            default:
                $this->startDate = $now->copy()->startOfMonth()->toDateString();
                $this->endDate = $now->toDateString();
                break;
        }
    }

    public function render(ProductionExecutiveService $service)
    {
        $report = $service->getProductionReport($this->startDate, $this->endDate, $this->station ?: null);
        $stations = Airport::where('status', 'Aktif')->orderBy('kode')->pluck('kode');

        return view('livewire.reports.executive', [
            'report' => $report,
            'stations' => $stations,
        ])->layout('components.layouts.app', ['title' => 'Executive Production Meeting Report']);
    }
}
