    <aside id="cbm-sidebar" class="cbm-sidebar">

        {{-- Header --}}
        <div class="cbm-sidebar-header">
            <a href="{{ route('dashboard') }}" wire:navigate class="cbm-sidebar-logo" aria-label="Ke dashboard">
                <img src="{{ asset('images/lion-logo.png') }}" alt="Logo Lion" width="40" height="40">
            </a>
            <div class="cbm-sidebar-brand">
                <div class="cbm-sidebar-brand-name"><span>Cluster Cabin</span><span class="accent">Maintenance</span></div>
                <div class="cbm-sidebar-brand-sub">Batam Aero Technic</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="cbm-sidebar-nav" style="padding-bottom: 2rem;">

            {{-- 1. UTAMA --}}
            <div class="cbm-nav-section-label">Utama</div>
            <a href="{{ route('dashboard') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('dashboard') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                    <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                </svg>
                Dashboard
            </a>

            <div class="cbm-nav-section-label" style="margin-top: .75rem;">Departemen &amp; Divisi</div>

            {{-- 2. DIVISI CABIN MAINTENANCE (CBM) --}}
            @if(auth()->user()?->canAny(['menu.production', 'menu.cabin.dja', 'menu.cabin.cml', 'menu.cabin.nsrdi', 'menu.cabin.dmi', 'menu.cabin.wo', 'menu.cabin.stretcher', 'menu.cabin.dbi', 'menu.cabin.daily_report', 'lgt.view', 'leader.import', 'menu.nsrdi', 'menu.nsrdi.overdue', 'menu.nsrdi.no_spare', 'menu.nsrdi.pivot']))
            <div x-data="{ open: {{ request()->routeIs(['modules.dja', 'modules.cml', 'modules.nsrdi', 'modules.dmi', 'modules.wo', 'modules.stretcher.*', 'modules.dbi', 'modules.daily-report', 'modules.lgt', 'modules.leader-report', 'modules.nsrdi-overdue', 'modules.nsrdi-no-spare', 'reports.nsrdi-pivot']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.dja', 'modules.cml', 'modules.nsrdi', 'modules.dmi', 'modules.wo', 'modules.stretcher.*', 'modules.dbi', 'modules.daily-report', 'modules.lgt', 'modules.leader-report', 'modules.nsrdi-overdue', 'modules.nsrdi-no-spare', 'reports.nsrdi-pivot']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.515 10.674a1.875 1.875 0 012.385-2.222l4.896 1.632-1.393-4.18a1.875 1.875 0 012.222-2.385l4.811 1.604a3.75 3.75 0 011.666 6.326L13.626 15h.874a2.25 2.25 0 012.25 2.25v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5h-1.28l-3.476 3.476a3.75 3.75 0 01-6.326-1.666l-1.604-4.811z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Cabin Technician</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @canany(['menu.cabin.dja', 'menu.production'])
                    <a href="{{ route('modules.dja') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dja') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        DJA
                    </a>
                    @endcanany
                    @canany(['menu.cabin.cml', 'menu.production'])
                    <a href="{{ route('modules.cml') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cml') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        CML Logs
                    </a>
                    @endcanany
                    @canany(['menu.cabin.nsrdi', 'menu.production'])
                    <a href="{{ route('modules.nsrdi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        NSRDI Logs
                    </a>
                    @endcanany
                    @canany(['menu.cabin.dmi', 'menu.production'])
                    <a href="{{ route('modules.dmi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dmi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        DMI Logs
                    </a>
                    @endcanany
                    @canany(['menu.cabin.wo', 'menu.production'])
                    <a href="{{ route('modules.wo') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.wo') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        WO Logs
                    </a>
                    @endcanany
                    @canany(['menu.cabin.stretcher', 'menu.production'])
                    <a href="{{ route('modules.stretcher.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.stretcher.*') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v5.25H6a.75.75 0 000 1.5h5.25V18a.75.75 0 001.5 0v-5.25H18a.75.75 0 000-1.5h-5.25V6z" clip-rule="evenodd" /></svg>
                        Stretcher Tracking
                    </a>
                    @endcanany
                    @canany(['menu.cabin.dbi', 'menu.production'])
                    <a href="{{ route('modules.dbi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dbi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5z" clip-rule="evenodd" /></svg>
                        Daily Beautification (DBI)
                    </a>
                    @endcanany
                    @canany(['menu.cabin.daily_report', 'menu.production'])
                    <a href="{{ route('modules.daily-report') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.daily-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 00.713.31A26.226 26.226 0 0012 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 01-6.477-.427C4.047 21.128 3 19.852 3 18.4z" /></svg>
                        Daily Report
                    </a>
                    @endcanany
                    @canany(['lgt.view', 'menu.production'])
                    <a href="{{ route('modules.lgt') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.lgt') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                        Long Ground Time (LGT)
                    </a>
                    @endcanany
                    @canany(['leader.import', 'menu.production'])
                    <a href="{{ route('modules.leader-report') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.leader-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /></svg>
                        Laporan Leader
                    </a>
                    @endcanany
                    @canany(['menu.nsrdi.overdue', 'menu.nsrdi', 'menu.production'])
                    <a href="{{ route('modules.nsrdi-overdue') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi-overdue') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm.75 5.25a.75.75 0 00-1.5 0v5.25c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V7.5z" clip-rule="evenodd" /></svg>
                        NSRDI Overdue
                    </a>
                    @endcanany
                    @canany(['menu.nsrdi.no_spare', 'menu.nsrdi', 'menu.production'])
                    <a href="{{ route('modules.nsrdi-no-spare') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi-no-spare') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.75 3a.75.75 0 00-.75.75v16.5c0 .414.336.75.75.75h16.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75H3.75zm1.5 1.5h13.5v13.5H5.25V4.5z" clip-rule="evenodd" /></svg>
                        NSRDI No Spare
                    </a>
                    @endcanany
                    @canany(['menu.nsrdi.pivot', 'menu.nsrdi', 'kpi.view'])
                    <a href="{{ route('reports.nsrdi-pivot') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.nsrdi-pivot') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm14.25 1.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V7.5zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V12zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5z" clip-rule="evenodd" /></svg>
                        Pivot NSRDI AOC
                    </a>
                    @endcanany
                </div>
            </div>
            @endif

            {{-- 3. DIVISI AIRCRAFT PAINTING --}}
            @if(auth()->user()?->canAny(['menu.painting', 'menu.painting.nsrdi', 'menu.painting.daily_report']))
            <div x-data="{ open: {{ request()->routeIs(['modules.painting.*']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.painting.*']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm.53 4.22a.75.75 0 00-1.06 0l-4.5 4.5a.75.75 0 001.06 1.06l3.22-3.22v7.44a.75.75 0 001.5 0V8.81l3.22 3.22a.75.75 0 101.06-1.06l-4.5-4.5z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Aircraft Painting</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @canany(['menu.painting.nsrdi', 'menu.painting'])
                    <a href="{{ route('modules.painting.nsrdi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.painting.nsrdi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        NSRDI Logs
                    </a>
                    @endcanany
                    @canany(['menu.painting.daily_report', 'menu.painting'])
                    <a href="{{ route('modules.painting.daily-report') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.painting.daily-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 00.713.31A26.226 26.226 0 0012 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 01-6.477-.427C4.047 21.128 3 19.852 3 18.4z" /></svg>
                        Daily Report
                    </a>
                    @endcanany
                </div>
            </div>
            @endif

            {{-- 4. DIVISI AIRCRAFT CLEANING (AIEC) --}}
            @if(auth()->user()?->canAny(['menu.cleaning', 'menu.cleaning.hub', 'menu.cleaning.daily_report', 'menu.cleaning.sync']))
            <div x-data="{ open: {{ request()->routeIs(['modules.cleaning.*']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.cleaning.*']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Aircraft Cleaning (AIEC)</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @canany(['menu.cleaning.hub', 'menu.cleaning'])
                    <a href="{{ route('modules.cleaning.hub') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs(['modules.cleaning.hub', 'modules.cleaning.general', 'modules.cleaning.interior', 'modules.cleaning.exterior', 'modules.cleaning.gci', 'modules.cleaning.gce', 'modules.cleaning.lgt']) ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
                        Cleaning Log <span class="kd-sub" style="margin-left:auto;font-size:.65rem;opacity:.7;">7 jenis</span>
                    </a>
                    @endcanany
                    @canany(['menu.cleaning.daily_report', 'menu.cleaning'])
                    <a href="{{ route('modules.cleaning.daily-report') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.daily-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 00.713.31A26.226 26.226 0 0012 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 01-6.477-.427C4.047 21.128 3 19.852 3 18.4z" /></svg>
                        Daily Report
                    </a>
                    @endcanany
                    @canany(['menu.cleaning.sync', 'menu.cleaning'])
                    <a href="{{ route('modules.cleaning.sync') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.sync') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0013.621-4.004.75.75 0 00-.53-.919z" clip-rule="evenodd" /></svg>
                        Cleaning Sync
                    </a>
                    @endcanany
                </div>
            </div>
            @endif

            {{-- 5. DIVISI SUPPORTING & INVENTORY (IMS & ASSET) --}}
            @if(auth()->user()?->canAny(['menu.inventory', 'ims.catalog.view', 'ims.request.create', 'ims.stock.in', 'ims.stock.adjust', 'ims.stock.transfer', 'ims.approval.view', 'ims.repair.manage', 'ims.report.view', 'ims.master.manage', 'asset.view', 'menu.tools_equipment', 'compliance.view', 'menu.ict', 'menu.ict.pi', 'menu.ict.tbd']))
            <div x-data="{ open: {{ request()->routeIs(['ims.*', 'assets.index', 'tools.equipment', 'compliance.daily', 'modules.ict-pi', 'modules.ict-tbd', 'manual.index']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['ims.*', 'assets.index', 'tools.equipment', 'compliance.daily', 'modules.ict-pi', 'modules.ict-tbd', 'manual.index']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm4.5 7.5a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 01.75-.75zm3.75-1.5a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0V12zm3.75-1.5a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0v-6a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Supporting &amp; Inventory</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @canany(['ims.catalog.view', 'menu.inventory'])
                    <a href="{{ route('ims.catalog') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.catalog') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm5.25 0a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM3 12a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm5.25 0a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM3 18a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm5.25 0a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
                        Katalog IMS
                    </a>
                    @endcanany
                    @canany(['ims.request.create', 'menu.inventory'])
                    <a href="{{ route('ims.requests') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.requests') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.625 3A1.125 1.125 0 003.5 4.125v15.75A1.125 1.125 0 004.625 21h14.75A1.125 1.125 0 0020.5 19.875V7.636a1.125 1.125 0 00-.33-.796l-3.34-3.34A1.125 1.125 0 0016.035 3H4.625zM12 9a.75.75 0 01.75.75v1.5h1.5a.75.75 0 010 1.5h-1.5v1.5a.75.75 0 01-1.5 0v-1.5h-1.5a.75.75 0 010-1.5h1.5v-1.5A.75.75 0 0112 9z" clip-rule="evenodd" /></svg>
                        Pengeluaran Barang
                    </a>
                    @endcanany
                    @canany(['ims.stock.in', 'ims.approval.view'])
                    <a href="{{ route('ims.penerimaan') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.penerimaan') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v11.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.08 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zm-9 13.5a.75.75 0 01.75.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-2.25a.75.75 0 011.5 0v2.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3v-2.25a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                        Penerimaan Barang
                    </a>
                    @endcanany
                    @canany(['ims.stock.adjust', 'menu.inventory'])
                    <a href="{{ route('ims.opname') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.opname') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.25 2.25a3 3 0 00-3 3v13.5a3 3 0 003 3h13.5a3 3 0 003-3V5.25a3 3 0 00-3-3H5.25zm.75 6.75a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75a.75.75 0 01-.75-.75zm0 4.5a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75a.75.75 0 01-.75-.75zm0 4.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
                        Stock Opname
                    </a>
                    @endcanany
                    @canany(['ims.stock.transfer', 'menu.inventory'])
                    <a href="{{ route('ims.transfer') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.transfer') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0014.536-4.041.75.75 0 00-.507-.912z" clip-rule="evenodd" /></svg>
                        Transfer Stok
                    </a>
                    @endcanany
                    @canany(['ims.approval.view', 'ims.approval.act'])
                    <a href="{{ route('ims.approvals') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.approvals') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.5 3A1.5 1.5 0 009 4.5h6A1.5 1.5 0 0013.5 3h-3zM3 6.75A2.25 2.25 0 015.25 4.5H7.5v2.25a.75.75 0 00.75.75h7.5a.75.75 0 00.75-.75V4.5h2.25A2.25 2.25 0 0121 6.75v12.5A2.25 2.25 0 0118.75 21.5H5.25A2.25 2.25 0 013 19.25V6.75zm12.35 4.9a.75.75 0 00-1.06-1.06l-4.5 4.5-1.54-1.54a.75.75 0 10-1.06 1.06l2.07 2.07a.75.75 0 001.06 0l5.03-5.03z" clip-rule="evenodd" /></svg>
                        Persetujuan
                    </a>
                    @endcanany
                    @canany(['ims.repair.manage', 'ims.repair.request'])
                    <a href="{{ route('ims.repairs') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.repairs') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.515 10.674a1.875 1.875 0 012.385-2.222l4.896 1.632-1.393-4.18a1.875 1.875 0 012.222-2.385l4.811 1.604a3.75 3.75 0 011.666 6.326L13.626 15h.874a2.25 2.25 0 012.25 2.25v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5h-1.28l-3.476 3.476a3.75 3.75 0 01-6.326-1.666l-1.604-4.811z" clip-rule="evenodd" /></svg>
                        Repair Area
                    </a>
                    @endcanany
                    @canany(['ims.report.view', 'menu.inventory'])
                    <a href="{{ route('ims.reports') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.reports') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 4.5A2.25 2.25 0 014.5 2.25h15A2.25 2.25 0 0121.75 4.5v15A2.25 2.25 0 0119.5 21.75H4.5A2.25 2.25 0 012.25 19.5v-15zm4.5 11.25a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v3.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3.75zm5.25-3a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v6.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-6.75zm5.25-4.5a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v11.25a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-11.25z" clip-rule="evenodd" /></svg>
                        Laporan Stok IMS
                    </a>
                    @endcanany
                    @can('ims.master.manage')
                    <a href="{{ route('ims.master') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master*') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6zM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25z" clip-rule="evenodd" /></svg>
                        Master Data IMS
                    </a>
                    @endcan
                    @can('asset.view')
                    <a href="{{ route('assets.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('assets.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.622 1.602a.75.75 0 01.756 0l8.25 4.813a.75.75 0 010 1.304l-8.25 4.813a.75.75 0 01-.756 0L3.372 7.72a.75.75 0 010-1.304l8.25-4.814z" clip-rule="evenodd" /><path d="M2.25 10.375a.75.75 0 01.75-.75h18a.75.75 0 010 1.5H3a.75.75 0 01-.75-.75zM3.75 14.25a.75.75 0 000 1.5h16.5a.75.75 0 000-1.5H3.75zM4.5 18.75a.75.75 0 000 1.5h15a.75.75 0 000-1.5H4.5z" /></svg>
                        Data Asset
                    </a>
                    @endcan
                    @can('menu.tools_equipment')
                    <a href="{{ route('tools.equipment') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('tools.equipment') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.75 6.75A3.75 3.75 0 017.5 3h9A3.75 3.75 0 0120.25 6.75v10.5A3.75 3.75 0 0116.5 21h-9a3.75 3.75 0 01-3.75-3.75V6.75zM13.5 6a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM10.5 9a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm0 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm4.5-4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm0 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd" /></svg>
                        Tools &amp; Equipment
                    </a>
                    @endcan
                    <a href="{{ route('manual.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('manual.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z"/></svg>
                        Referensi Manual
                    </a>
                    @can('compliance.view')
                    <a href="{{ route('compliance.daily') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('compliance.daily') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 8.03a.75.75 0 00-1.06-1.06l-4.5 4.5-1.97-1.97a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.06 0l5.03-5.03z" clip-rule="evenodd" /></svg>
                        Briefing, Attlist &amp; 5R
                    </a>
                    @endcan
                    @canany(['menu.ict.pi', 'menu.ict'])
                    <a href="{{ route('modules.ict-pi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.ict-pi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Findings ICT &amp; IFE (PI)
                    </a>
                    @endcanany
                    @canany(['menu.ict.tbd', 'menu.ict'])
                    <a href="{{ route('modules.ict-tbd') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.ict-tbd') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Findings ICT &amp; IFE (TBD)
                    </a>
                    @endcanany
                </div>
            </div>
            @endif

            {{-- 7. CAPACITY & PLANNING --}}
            @if(auth()->user()?->canAny(['menu.capacity', 'menu.capacity.index', 'menu.capacity.rotation', 'menu.capacity.movement']))
            <div x-data="{ open: {{ request()->routeIs(['modules.capacity', 'modules.aircraft-rotation', 'modules.ac-movement']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.capacity', 'modules.aircraft-rotation', 'modules.ac-movement']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Capacity &amp; Planning</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @canany(['menu.capacity.index', 'menu.capacity'])
                    <a href="{{ route('modules.capacity') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.capacity') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm14.25 1.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V7.5zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V12zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5z" clip-rule="evenodd" /></svg>
                        Capacity Stasiun &amp; Target
                    </a>
                    @endcanany
                    @canany(['menu.capacity.rotation', 'menu.capacity'])
                    <a href="{{ route('modules.aircraft-rotation') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.aircraft-rotation') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0013.621-4.004.75.75 0 00-.53-.919z" clip-rule="evenodd" /></svg>
                        Aircraft Rotation
                    </a>
                    @endcanany
                    @canany(['menu.capacity.movement', 'menu.capacity'])
                    <a href="{{ route('modules.ac-movement') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.ac-movement') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M15.97 2.47a.75.75 0 011.06 0l4.5 4.5a.75.75 0 010 1.06l-4.5 4.5a.75.75 0 11-1.06-1.06l3.22-3.22H7.5a.75.75 0 010-1.5h11.69l-3.22-3.22a.75.75 0 010-1.06zm-7.94 9a.75.75 0 010 1.06l-3.22 3.22H16.5a.75.75 0 010 1.5H4.81l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
                        AC Movement
                    </a>
                    @endcanany
                </div>
            </div>
            @endif

            {{-- 8. HUMAN CAPITAL & GA --}}
            @if(auth()->user()?->canAny(['hr.view', 'hr.view_all', 'attendance.view', 'registry.pas.view', 'registry.training.view', 'registry.assess_internal.view', 'registry.assess_hc.view', 'registry.form.view']))
            <div x-data="{ open: {{ request()->routeIs(['hr.employees', 'attendance.index', 'hr.training', 'registry.index']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['hr.employees', 'attendance.index', 'hr.training', 'registry.index']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Human Capital &amp; GA</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    <div class="cbm-nav-section-label" style="font-size: .65rem; padding: 0.25rem 0.5rem; text-transform: uppercase; color: var(--cbm-text-muted); opacity: .7;">Development &amp; GA</div>
                    @canany(['hr.view', 'hr.view_all'])
                    <a href="{{ route('hr.employees') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('hr.employees') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 4.5a4.5 4.5 0 100 9 4.5 4.5 0 000-9zM2.25 18a9.75 9.75 0 0119.5 0v1.5a.75.75 0 01-.75.75H3a.75.75 0 01-.75-.75V18z" clip-rule="evenodd" /></svg>
                        Database Karyawan
                    </a>
                    @endcanany
                    @can('attendance.view')
                    <a href="{{ route('attendance.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('attendance.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" /></svg>
                        Presensi &amp; Disiplin
                    </a>
                    @endcan
                    @canany(['registry.training.view', 'hr.view'])
                    <a href="{{ route('hr.training') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('hr.training') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z" /></svg>
                        Data Training Karyawan
                    </a>
                    @endcanany
                    @can('registry.pas.view')
                    <a href="{{ route('registry.index', 'pas') }}" wire:navigate class="cbm-nav-item {{ request()->is('registry/pas') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.5 3.75A1.5 1.5 0 003 5.25v13.5A1.5 1.5 0 004.5 20.25h15a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5h-15zm3.75 4.5a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5zM15 9a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3zm0 3a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z" clip-rule="evenodd" /></svg>
                        Data PAS Bandara
                    </a>
                    @endcan
                    <div class="cbm-nav-section-label" style="font-size: .65rem; padding: 0.25rem 0.5rem; text-transform: uppercase; color: var(--cbm-text-muted); opacity: .7;">Organisasi &amp; Assessment</div>
                    @can('registry.assess_internal.view')
                    <a href="{{ route('registry.index', 'assess_internal') }}" wire:navigate class="cbm-nav-item {{ request()->is('registry/assess_internal') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 8.03a.75.75 0 00-1.06-1.06l-4.5 4.5-1.97-1.97a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.06 0l5.03-5.03z" clip-rule="evenodd" /></svg>
                        Assessment Internal
                    </a>
                    @endcan
                    @can('registry.assess_hc.view')
                    <a href="{{ route('registry.index', 'assess_hc') }}" wire:navigate class="cbm-nav-item {{ request()->is('registry/assess_hc') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 8.03a.75.75 0 00-1.06-1.06l-4.5 4.5-1.97-1.97a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.06 0l5.03-5.03z" clip-rule="evenodd" /></svg>
                        Assessment HC
                    </a>
                    @endcan
                    @can('registry.form.view')
                    <a href="{{ route('registry.index', 'form') }}" wire:navigate class="cbm-nav-item {{ request()->is('registry/form') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z" clip-rule="evenodd" /></svg>
                        Form Karyawan
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- 9. OPERASIONAL & DISPATCH --}}
            @if(auth()->user()?->canAny(['menu.operational.verification', 'menu.operational.shift_recap']))
            <div x-data="{ open: {{ request()->routeIs(['verification.queue', 'shift.recap']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['verification.queue', 'shift.recap']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-2.625 6c-.54 0-.828.419-.936.634a1.96 1.96 0 00-.189.866c0 .298.059.605.189.866.108.215.395.634.936.634.54 0 .828-.419.936-.634.13-.26.189-.568.189-.866 0-.298-.059-.605-.189-.866-.108-.215-.395-.634-.936-.634zm4.314.634c.108-.215.395-.634.936-.634.54 0 .828.419.936.634.13.26.189.568.189.866 0 .298-.059.605-.189.866-.108.215-.395.634-.936.634-.54 0-.828-.419-.936-.634a1.96 1.96 0 01-.189-.866c0-.298.059-.605.189-.866z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Operasional &amp; Dispatch</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @can('menu.operational.verification')
                    <a href="{{ route('verification.queue') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('verification.queue') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Verification Queue
                    </a>
                    @endcan
                    @can('menu.operational.shift_recap')
                    <a href="{{ route('shift.recap') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('shift.recap') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.125 3C3.089 3 2.25 3.84 2.25 4.875V18a3 3 0 003 3h15a3 3 0 01-3-3V4.875C17.25 3.839 16.41 3 15.375 3H4.125zM12 9.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H12zm-.75-2.25a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H12a.75.75 0 01-.75-.75zM6 12.75a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6zm0 3a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6zm-.75-6a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5H6a.75.75 0 01-.75-.75zM6 6.75a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6z" clip-rule="evenodd" /></svg>
                        Shift Recap
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- 10. ANALITIK & REPORTING --}}
            @if(auth()->user()?->canAny(['menu.analytics', 'reports.summary.view', 'reports.kpi.view', 'reports.document_accuracy.view', 'reports.lgt.view', 'reports.manpower.view', 'reports.executive.view', 'kpi.view']))
            <div x-data="{ open: {{ request()->routeIs(['reports.summary', 'reports.kpi', 'reports.document-accuracy', 'reports.lgt', 'reports.manpower', 'reports.insights', 'sync.log', 'reports.executive', 'aircraft.history']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['reports.summary', 'reports.kpi', 'reports.document-accuracy', 'reports.lgt', 'reports.manpower', 'reports.insights', 'sync.log', 'reports.executive', 'aircraft.history']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Analitik &amp; Reporting</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @canany(['reports.summary.view', 'menu.analytics'])
                    <a href="{{ route('reports.summary') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.summary') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm14.25 1.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V7.5zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V12zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5z" clip-rule="evenodd" /></svg>
                        Summary
                    </a>
                    @endcanany
                    @canany(['reports.kpi.view', 'menu.analytics'])
                    <a href="{{ route('reports.kpi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.kpi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" /></svg>
                        KPI &amp; Man Hours
                    </a>
                    @endcanany
                    @canany(['reports.document_accuracy.view', 'kpi.view'])
                    <a href="{{ route('reports.document-accuracy') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.document-accuracy') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        KPI Document Accuracy
                    </a>
                    @endcanany
                    @canany(['reports.lgt.view', 'kpi.view'])
                    <a href="{{ route('reports.lgt') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.lgt') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                        LGT Monitoring
                    </a>
                    @endcanany
                    @canany(['reports.manpower.view', 'kpi.view'])
                    <a href="{{ route('reports.manpower') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.manpower') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                        Manpower Harian
                    </a>
                    @endcanany
                    @can('kpi.view')
                    <a href="{{ route('reports.insights') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.insights') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"/></svg>
                        Pivot Lanjutan
                    </a>
                    @endcan
                    @if(auth()->user()?->hasAnyRole([\App\Helpers\RoleHelper::SUPER_ADMIN, ...\App\Helpers\RoleHelper::COD_DESK]))
                    <a href="{{ route('sync.log') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('sync.log') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0014.536-4.041.75.75 0 00-.507-.912z" clip-rule="evenodd"/></svg>
                        Log Sinkron GSS
                    </a>
                    @endif
                    @if(auth()->user()?->can('reports.executive.view') || auth()->user()?->hasAnyRole([\App\Helpers\RoleHelper::SUPER_ADMIN, \App\Helpers\RoleHelper::MANAGER, ...\App\Helpers\RoleHelper::ALL_PIC, ...\App\Helpers\RoleHelper::COD_DESK]))
                    <a href="{{ route('reports.executive') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.executive') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z" /></svg>
                        Executive Production Report
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <div class="cbm-nav-section-label" style="margin-top: .75rem;">Administrasi Sistem</div>

            {{-- 11. DATA MASTER & SISTEM --}}
            @if(auth()->user()?->canAny(['master.airports.view', 'master.aircraft.view', 'master.categories.view', 'master.divisions.view', 'master.positions.view', 'master.capacity.view', 'sources.view', 'master.system.view']))
            <div x-data="{ open: {{ request()->routeIs(['master.airports', 'master.aircraft', 'master.categories', 'master.divisions', 'master.positions', 'master.capacity-config', 'sources.index', 'master.data']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['master.airports', 'master.aircraft', 'master.categories', 'master.divisions', 'master.positions', 'master.capacity-config', 'sources.index', 'master.data']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.25 5.25a3 3 0 013-3h13.5a3 3 0 013 3V15a3 3 0 01-3 3H5.25a3 3 0 01-3-3V5.25zM4.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM19.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM4.5 9a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM19.5 9a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Data Master &amp; Sistem</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @can('master.airports.view')
                    <a href="{{ route('master.airports') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.airports') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                        Bandara / Station
                    </a>
                    @endcan
                    @can('master.aircraft.view')
                    <a href="{{ route('master.aircraft') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.aircraft') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" /></svg>
                        Registrasi Pesawat
                    </a>
                    @endcan
                    @can('master.categories.view')
                    <a href="{{ route('master.categories') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.categories') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M19.5 21a3 3 0 003-3V9a3 3 0 00-3-3h-5.379a1.5 1.5 0 01-1.06-.44l-2.122-2.12a1.5 1.5 0 00-1.06-.44H4.5A3 3 0 001.5 6v12a3 3 0 003 3h15z" clip-rule="evenodd" /></svg>
                        Kategori Pekerjaan
                    </a>
                    @endcan
                    @can('master.divisions.view')
                    <a href="{{ route('master.divisions') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.divisions') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
                        Master Divisi
                    </a>
                    @endcan
                    @can('master.positions.view')
                    <a href="{{ route('master.positions') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.positions') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>
                        Master Jabatan
                    </a>
                    @endcan
                    @can('master.capacity.view')
                    <a href="{{ route('master.capacity-config') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.capacity-config') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 10.5a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v8.25a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-8.25zm5.25-4.5a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v12.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75V6zm5.25-3a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v15.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" /></svg>
                        Master Capacity
                    </a>
                    @endcan
                    @can('sources.view')
                    <a href="{{ route('sources.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('sources.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0013.621-4.004.75.75 0 00-.53-.919z" clip-rule="evenodd" /></svg>
                        Sumber Data Eksternal
                    </a>
                    @endcan
                    @can('master.system.view')
                        @php $masterTypes = collect(config('master.types'))->filter(fn ($d, $t) => auth()->user()?->can("master.$t.view")); @endphp
                        @foreach($masterTypes as $t => $d)
                            <a href="{{ route('master.data', $t) }}" wire:navigate class="cbm-nav-item {{ request()->is('master/data/'.$t) ? 'cbm-active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm14.25 1.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V7.5zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V12zm0 4.5a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5z" clip-rule="evenodd" /></svg>
                                {{ $d['label'] }}
                            </a>
                        @endforeach
                    @endcan
                </div>
            </div>
            @endif

            {{-- 12. SISTEM & KEAMANAN --}}
            @if(auth()->user()?->canAny(['security.users.view', 'security.roles.view', 'security.audit.view']))
            <div x-data="{ open: {{ request()->routeIs(['users.index', 'roles.index', 'audit.index']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['users.index', 'roles.index', 'audit.index']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sistem &amp; Keamanan</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @can('security.users.view')
                    <a href="{{ route('users.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('users.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /></svg>
                        User Management
                    </a>
                    @endcan
                    @can('security.roles.view')
                    <a href="{{ route('roles.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('roles.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Peran &amp; Hak Akses
                    </a>
                    @endcan
                    @can('security.audit.view')
                    <a href="{{ route('audit.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('audit.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                        Audit Trail
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- 13. PUSAT DOKUMEN & PROFIL --}}
            <div x-data="{ open: {{ request()->routeIs(['documents.*', 'profile.index']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="cbm-nav-item {{ request()->routeIs(['documents.*', 'profile.index']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem; min-width: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" />
                        </svg>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Dokumen &amp; Profil</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="margin-left: 1rem; padding-left: .75rem; border-left: 1.5px solid var(--cbm-card-border, rgba(255,255,255,.08)); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                    @can('documents.view')
                    <a href="{{ route('documents.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('documents.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.5 4.5a3 3 0 00-3 3v9a3 3 0 003 3h8.25a3 3 0 003-3v-9a3 3 0 00-3-3H4.5zm0 1.5h8.25a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H4.5A1.5 1.5 0 013 16.5v-9A1.5 1.5 0 014.5 6z" clip-rule="evenodd" /><path d="M19.5 5.25v13.5a1.5 1.5 0 01-1.5 1.5h-3v-16.5h3a1.5 1.5 0 011.5 1.5zM12 9.75a.75.75 0 00-1.5 0v3a.75.75 0 001.5 0v-3z" /></svg>
                        CMPM &amp; Dokumen SOP
                    </a>
                    @endcan
                    @can('documents.master.view')
                    <a href="{{ route('documents.master') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('documents.master') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2.25 5.25a3 3 0 013-3h13.5a3 3 0 013 3V15a3 3 0 01-3 3H5.25a3 3 0 01-3-3V5.25zM4.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM19.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
                        Master Kategori Dokumen
                    </a>
                    @endcan
                    @can('profile.view')
                    <a href="{{ route('profile.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('profile.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 00-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 00-2.282.819l-.922 1.597a1.875 1.875 0 00.432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 000 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 00-.432 2.385l.922 1.597a1.875 1.875 0 002.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 002.28-.819l.923-1.597a1.875 1.875 0 00-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 000-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 00-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 00-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 00-1.85-1.567h-1.843zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" clip-rule="evenodd" /></svg>
                        Profil Saya
                    </a>
                    @endcan
                </div>
            </div>

        </nav>

        {{-- Footer (user info + logout) --}}
        <div class="cbm-sidebar-footer">
            <div class="cbm-user-card">
                <div class="cbm-user-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div style="flex:1; min-width:0;">
                    <div class="cbm-user-name">
                        <x-text-popup :text="auth()->user()->name ?? 'User'" />
                    </div>
                    <div class="cbm-user-role">{{ auth()->user()->roles->first()?->name ?? auth()->user()->jabatan ?? 'Staff' }}</div>
                </div>
                <button type="button" onclick="document.getElementById('cbm-logout-form').submit();" style="background:transparent; border:none; padding:0; cursor:pointer; color:var(--cbm-text-sub); display:flex;" title="Log Out">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem;">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <form id="cbm-logout-form" action="{{ route('logout') ?? '#' }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>

    </aside>
