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
                <a href="{{ route('hr.employees') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('hr.employees') ? 'cbm-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                    Database Karyawan
                </a>
            @endcan
            @can('attendance.view')
                <a href="{{ route('attendance.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('attendance.index') ? 'cbm-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" /></svg>
                    Presensi &amp; Disiplin
                </a>
            @endcan
        @endif
        @foreach($items as $key => $m)
            <a href="{{ route('registry.index', $key) }}" wire:navigate class="cbm-nav-item {{ request()->is('registry/'.$key) ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                {{ $m['label'] }}
            </a>
        @endforeach
    @endif
@endforeach

@can('asset.view')
    <div class="cbm-nav-section-label">Supporting</div>
    <a href="{{ route('assets.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('assets.index') ? 'cbm-active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.622 1.602a.75.75 0 01.756 0l8.25 4.813a.75.75 0 010 1.304l-8.25 4.813a.75.75 0 01-.756 0L3.372 7.72a.75.75 0 010-1.304l8.25-4.814z" clip-rule="evenodd" /><path d="M2.25 10.375a.75.75 0 01.75-.75h18a.75.75 0 010 1.5H3a.75.75 0 01-.75-.75zM3.75 14.25a.75.75 0 000 1.5h16.5a.75.75 0 000-1.5H3.75zM4.5 18.75a.75.75 0 000 1.5h15a.75.75 0 000-1.5H4.5z" /></svg>
        Data Asset
    </a>
@endcan

@can('compliance.view')
    <div class="cbm-nav-section-label">Compliance</div>
    <a href="{{ route('compliance.daily') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('compliance.daily') ? 'cbm-active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 8.03a.75.75 0 00-1.06-1.06l-4.5 4.5-1.97-1.97a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.06 0l5.03-5.03z" clip-rule="evenodd" /></svg>
        Briefing, Attlist &amp; 5R
    </a>
@endcan

@can('sources.view')
    <div class="cbm-nav-section-label">Sistem</div>
    <a href="{{ route('sources.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('sources.index') ? 'cbm-active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0013.621-4.004.75.75 0 00-.53-.919z" clip-rule="evenodd" /></svg>
        Sumber Data
    </a>
@endcan

@php $masterTypes = collect(config('master.types'))->filter(fn ($d, $t) => auth()->user()?->can("master.$t.view")); @endphp
@if($masterTypes->isNotEmpty())
    <div class="cbm-nav-section-label">Master Sistem</div>
    @foreach($masterTypes as $t => $d)
        <a href="{{ route('master.data', $t) }}" wire:navigate class="cbm-nav-item {{ request()->is('master/data/'.$t) ? 'cbm-active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm14.25 1.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V7.5zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V12zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5z" clip-rule="evenodd" /></svg>
            {{ $d['label'] }}
        </a>
    @endforeach
@endif

<div class="cbm-nav-section-label">Referensi</div>
<a href="{{ route('documents.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('documents.index') ? 'cbm-active' : '' }}">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z" clip-rule="evenodd" /></svg>
    CMPM, SOP &amp; Dokumen
</a>
