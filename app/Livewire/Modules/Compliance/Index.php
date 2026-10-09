<?php

namespace App\Livewire\Modules\Compliance;

use App\Models\ComplianceEntry;
use App\Models\SyncSetting;
use App\Services\Compliance\ComplianceReport;
use App\Services\Compliance\ComplianceSyncService;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Daily briefing, attendant list and 5R evidence per station and shift (mirrored from the Apps Script sheet).
 * Roles with registry.view_all see every station; a user whose profile has a station sees only that one.
 */
class Index extends Component
{
    public string $range = '7';          // '7', '30', '90', 'all'

    public ?string $station = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('compliance.view'), 403);
    }

    private function allowedStations(): ?array
    {
        $user = auth()->user();
        if ($user->can('registry.view_all') || ! $user->station) {
            return null;
        }

        return [strtoupper($user->station)];
    }

    public function setRange(string $range): void
    {
        $this->range = in_array($range, ['7', '30', '90', 'all'], true) ? $range : '7';
    }

    public function pick(?string $station): void
    {
        $this->station = $this->station === $station ? null : $station;
    }

    public function syncNow(ComplianceSyncService $service): void
    {
        abort_unless(auth()->user()->can('registry.view_all'), 403);

        $stats = $service->sync();
        $this->dispatch('notify', $stats === null
            ? ['icon' => 'error', 'message' => $service->lastError() ?? 'Sinkronisasi gagal.', 'timer' => 7000]
            : ['icon' => 'success', 'message' => "{$stats['read']} entri dibaca, {$stats['created']} baru."]);
    }

    public function render(ComplianceReport $report)
    {
        $to = now()->startOfDay();
        $from = match ($this->range) {
            'all' => Carbon::parse(collect(config('compliance.stations'))->min('since')),
            default => $to->copy()->subDays((int) $this->range - 1),
        };

        $allowed = $this->allowedStations();
        $data = $report->build($from, $to, $allowed);

        $selected = $this->station && isset($data['stations'][$this->station]) ? $this->station : null;
        $days = collect();
        if ($selected) {
            $days = ComplianceEntry::where('station', $selected)
                ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
                ->orderByDesc('work_date')->get()->groupBy(fn ($e) => $e->work_date->toDateString());
        }

        $setting = SyncSetting::for(ComplianceSyncService::Key);

        return view('livewire.modules.compliance.index', [
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'selected' => $selected,
            'days' => $days,
            'shiftsOf' => $selected ? config("compliance.stations.$selected.shifts") : [],
            'setting' => $setting,
            'canSync' => auth()->user()->can('registry.view_all'),
            'documents' => config('compliance.documents'),
        ])->layout('components.layouts.app', ['title' => 'Compliance Harian']);
    }
}
