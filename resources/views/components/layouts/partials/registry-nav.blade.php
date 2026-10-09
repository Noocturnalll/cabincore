{{-- Sidebar groups for the registry modules (config/registry.php) plus Database Karyawan and Referensi. --}}
@php
    $registryModules = collect(config('registry.modules'))->filter(fn ($m, $key) => auth()->user()?->can("registry.$key.view"));
    $groupLabels = config('registry.groups');
@endphp

@foreach($groupLabels as $groupKey => $groupLabel)
    @php $items = $registryModules->filter(fn ($m) => $m['group'] === $groupKey); @endphp
    @if($items->isNotEmpty() || ($groupKey === 'dev-ga' && auth()->user()?->can('hr.view')))
        <div class="cbm-nav-section-label">{{ $groupLabel }}</div>
        @if($groupKey === 'dev-ga')
            @can('hr.view')
                <a href="{{ route('hr.employees') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('hr.employees') ? 'cbm-active' : '' }}">Database Karyawan</a>
            @endcan
            @can('attendance.view')
                <a href="{{ route('attendance.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('attendance.index') ? 'cbm-active' : '' }}">Presensi &amp; Disiplin</a>
            @endcan
        @endif
        @foreach($items as $key => $m)
            <a href="{{ route('registry.index', $key) }}" wire:navigate class="cbm-nav-item {{ request()->is('registry/'.$key) ? 'cbm-active' : '' }}">{{ $m['label'] }}</a>
        @endforeach
    @endif
@endforeach

@can('asset.view')
    <div class="cbm-nav-section-label">Supporting</div>
    <a href="{{ route('assets.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('assets.index') ? 'cbm-active' : '' }}">Data Asset</a>
@endcan

@can('compliance.view')
    <div class="cbm-nav-section-label">Compliance</div>
    <a href="{{ route('compliance.daily') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('compliance.daily') ? 'cbm-active' : '' }}">Briefing, Attlist &amp; 5R</a>
@endcan

@can('sources.view')
    <div class="cbm-nav-section-label">Sistem</div>
    <a href="{{ route('sources.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('sources.index') ? 'cbm-active' : '' }}">Sumber Data</a>
@endcan

@php $masterTypes = collect(config('master.types'))->filter(fn ($d, $t) => auth()->user()?->can("master.$t.view")); @endphp
@if($masterTypes->isNotEmpty())
    <div class="cbm-nav-section-label">Master Sistem</div>
    @foreach($masterTypes as $t => $d)
        <a href="{{ route('master.data', $t) }}" wire:navigate class="cbm-nav-item {{ request()->is('master/data/'.$t) ? 'cbm-active' : '' }}">{{ $d['label'] }}</a>
    @endforeach
@endif

<div class="cbm-nav-section-label">Referensi</div>
<a href="{{ route('documents.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('documents.index') ? 'cbm-active' : '' }}">CMPM, SOP &amp; Dokumen</a>
