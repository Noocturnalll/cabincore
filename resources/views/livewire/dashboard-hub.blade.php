<div>
    @if($canKpi)
        <div class="cbm-tabs" role="tablist" aria-label="Tampilan dashboard" style="padding-left:0;padding-right:0;margin-bottom:1rem;">
            <a href="{{ route('dashboard', ['t' => 'kpi']) }}" wire:navigate role="tab" aria-selected="{{ $tab === 'kpi' ? 'true' : 'false' }}" class="cbm-tab {{ $tab === 'kpi' ? 'active' : '' }}" style="text-decoration:none;">Ringkasan KPI</a>
            <a href="{{ route('dashboard', ['t' => 'ops']) }}" wire:navigate role="tab" aria-selected="{{ $tab === 'ops' ? 'true' : 'false' }}" class="cbm-tab {{ $tab === 'ops' ? 'active' : '' }}" style="text-decoration:none;">Operasional</a>
        </div>
    @endif

    @if($tab === 'kpi')
        <livewire:reports.kpi-dashboard wire:key="hub-kpi" />
    @else
        <livewire:dashboard wire:key="hub-ops" />
    @endif
</div>
