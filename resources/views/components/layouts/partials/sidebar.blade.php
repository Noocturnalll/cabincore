    <aside id="cbm-sidebar" class="cbm-sidebar">

        {{-- Header --}}
        <div class="cbm-sidebar-header">
            <div class="cbm-sidebar-logo" style="background: transparent;">
                <img src="{{ asset('images/lion-logo.png') }}" alt="Lion Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div class="cbm-sidebar-brand">
                <div class="cbm-sidebar-brand-name">Cabin Core</div>
                <div class="cbm-sidebar-brand-sub">Batam Aero Technic</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="cbm-sidebar-nav">
            <div class="cbm-nav-section-label">Utama</div>

            <a href="{{ route('dashboard') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('dashboard') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                    <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                </svg>
                Dashboard
            </a>

            @hasrole(\App\Helpers\RoleHelper::SUPER_ADMIN)
            <div class="cbm-nav-section-label">Data Master</div>
            <a href="{{ route('master.airports') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.airports') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                Bandara / Station
            </a>
            <a href="{{ route('master.aircraft') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.aircraft') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" /></svg>
                Registrasi Pesawat
            </a>
            <a href="{{ route('master.categories') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.categories') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M19.5 21a3 3 0 003-3V9a3 3 0 00-3-3h-5.379a1.5 1.5 0 01-1.06-.44l-2.122-2.12a1.5 1.5 0 00-1.06-.44H4.5A3 3 0 001.5 6v12a3 3 0 003 3h15z" clip-rule="evenodd" /></svg>
                Kategori Pekerjaan
            </a>
            <a href="{{ route('master.divisions') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.divisions') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
                Master Divisi
            </a>
            <a href="{{ route('master.positions') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.positions') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>
                Master Jabatan
            </a>
            <a href="{{ route('master.capacity-config') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.capacity-config') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 10.5a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v8.25a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-8.25zm5.25-4.5a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v12.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75V6zm5.25-3a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v15.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" /></svg>
                Master Capacity
            </a>
            <a href="{{ route('master.ron-config') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('master.ron-config') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd" /></svg>
                Master RON
            </a>
            @endhasrole

            <div class="cbm-nav-section-label">Cabin Maintenance</div>
            <div x-data="{ open: {{ request()->routeIs(['modules.dja', 'modules.cml', 'modules.nsrdi', 'modules.dmi', 'modules.wo', 'modules.daily-report']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.dja', 'modules.cml', 'modules.nsrdi', 'modules.dmi', 'modules.wo', 'modules.daily-report']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.515 10.674a1.875 1.875 0 012.385-2.222l4.896 1.632-1.393-4.18a1.875 1.875 0 012.222-2.385l4.811 1.604a3.75 3.75 0 011.666 6.326L13.626 15h.874a2.25 2.25 0 012.25 2.25v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5h-1.28l-3.476 3.476a3.75 3.75 0 01-6.326-1.666l-1.604-4.811z" clip-rule="evenodd" />
                        </svg>
                        <span>Cabin Maintenance</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.dja') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dja') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        DJA
                    </a>
                    <a href="{{ route('modules.cml') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cml') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        CML Logs
                    </a>
                    <a href="{{ route('modules.nsrdi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        NSRDI Logs
                    </a>
                    <a href="{{ route('modules.dmi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dmi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        DMI Logs
                    </a>
                    <a href="{{ route('modules.wo') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.wo') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        WO Logs
                    </a>

                    <a href="{{ route('modules.daily-report') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.daily-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 00.713.31A26.226 26.226 0 0012 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 01-6.477-.427C4.047 21.128 3 19.852 3 18.4z" /></svg>
                        Daily Report
                    </a>
                </div>
            </div>

            <div class="cbm-nav-section-label">Aircraft Cleaning</div>
            <div x-data="{ open: {{ request()->routeIs(['modules.cleaning.general', 'modules.cleaning.interior', 'modules.cleaning.exterior', 'modules.cleaning.daily-report']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.cleaning.general', 'modules.cleaning.interior', 'modules.cleaning.exterior', 'modules.cleaning.daily-report']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5zM18 1.5a.75.75 0 01.728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 010 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 01-1.456 0l-.258-1.036a2.625 2.625 0 00-1.91-1.91l-1.036-.258a.75.75 0 010-1.456l1.036-.258a2.625 2.625 0 001.91-1.91l.258-1.036A.75.75 0 0118 1.5zM16.5 15a.75.75 0 01.712.513l.394 1.183c.15.447.5.799.948.948l1.183.395a.75.75 0 010 1.422l-1.183.395c-.447.15-.799.5-.948.948l-.395 1.183a.75.75 0 01-1.422 0l-.395-1.183a1.5 1.5 0 00-.948-.948l-1.183-.395a.75.75 0 010-1.422l1.183-.395c.447-.15.799-.5.948-.948l.395-1.183A.75.75 0 0116.5 15z" clip-rule="evenodd" />
                        </svg>
                        <span>Aircraft Cleaning</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.cleaning.general') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.general') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        General Cleaning
                    </a>
                    <a href="{{ route('modules.cleaning.interior') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.interior') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Interior Cleaning (DCI)
                    </a>
                    <a href="{{ route('modules.cleaning.exterior') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.exterior') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Exterior Cleaning (DCE)
                    </a>
                    <a href="{{ route('modules.cleaning.daily-report') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.daily-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 00.713.31A26.226 26.226 0 0012 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 01-6.477-.427C4.047 21.128 3 19.852 3 18.4z" /></svg>
                        Daily Report
                    </a>
                    <a href="{{ route('modules.cleaning.sync') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cleaning.sync') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0013.621-4.004.75.75 0 00-.53-.919z" clip-rule="evenodd" /></svg>
                        Cleaning Sync
                    </a>
                </div>
            </div>


            <div class="cbm-nav-section-label">Team Painting</div>
            <div x-data="{ open: {{ request()->routeIs(['modules.nsrdi', 'modules.dmi', 'modules.cml']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.nsrdi', 'modules.dmi', 'modules.cml']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M20.599 1.5c-.376 0-.743.111-1.055.32l-5.08 3.385a18.747 18.747 0 0 0-3.471 2.987 10.04 10.04 0 0 1 4.815 4.815 18.748 18.748 0 0 0 2.987-3.472l3.386-5.079A1.902 1.902 0 0 0 20.599 1.5Zm-8.3 14.025a18.76 18.76 0 0 0 1.896-1.207 8.026 8.026 0 0 0-4.513-4.513A18.75 18.75 0 0 0 8.475 11.7l-.278.5a5.26 5.26 0 0 1 3.601 3.602l.5-.278ZM6.75 13.5A3.75 3.75 0 0 0 3 17.25a1.5 1.5 0 0 1-1.601 1.497.75.75 0 0 0-.7 1.123 5.25 5.25 0 0 0 9.8-2.62 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd" />
                        </svg>
                        <span>Painting Ops</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.nsrdi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        NSRDI Logs
                    </a>
                    <a href="{{ route('modules.dmi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dmi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        DMI Logs
                    </a>
                    <a href="{{ route('modules.cml') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.cml') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        CML Logs
                    </a>
                </div>
            </div>

            <div class="cbm-nav-section-label">Team CM Irregular</div>
            <div x-data="{ open: {{ request()->routeIs(['modules.dmi', 'ims.repairs', 'ims.requests']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.dmi', 'ims.repairs', 'ims.requests']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a3.375 3.375 0 0 1-2.435 2.652l-.536.144c-.94.252-1.465 1.248-1.15 2.17l.18.528c.18.529.56 1.01.996 1.408l.386.353c.87.794 1.034 2.126.386 3.125l-.234.364c-.496.772-1.417 1.122-2.316.883l-.537-.143a3.375 3.375 0 0 1-2.434-2.652l-.092-.55c-.151-.904-.933-1.567-1.849-1.567H2.25v2.25h1.22c.916 0 1.699.663 1.85 1.567l.091.549a3.375 3.375 0 0 1 2.435 2.652l.536.144c.94.252 1.465 1.248 1.15 2.17l-.18.528c-.18.529-.56 1.01-.996 1.408l-.386.353c-.87-.794-1.034 2.126-.386 3.125l.234.364c.496.772 1.417 1.122 2.316.883l.537-.143a3.375 3.375 0 0 1 2.434-2.652l.092-.55c.151-.904.933-1.567 1.849 1.567h2.342c.916 0 1.699-.663 1.85-1.567l.091-.549a3.375 3.375 0 0 1 2.435-2.652l.536-.144c.94-.252 1.465-1.248 1.15-2.17l-.18-.528c-.18-.529-.56-1.01-.996-1.408l-.386-.353c-.87-.794-1.034-2.126-.386-3.125l.234-.364c.496-.772 1.417-1.122 2.316-.883l.537.143a3.375 3.375 0 0 1 2.434 2.652l.092.55c.151.904.933 1.567 1.849 1.567H21.75v-2.25h-1.22c-.916 0-1.699-.663-1.85-1.567l-.091-.549a3.375 3.375 0 0 1-2.435-2.652l-.536-.144c-.94-.252-1.465-1.248-1.15-2.17l.18-.528c.18-.529.56-1.01.996-1.408l.386-.353c.87-.794 1.034-2.126.386-3.125l-.234-.364c-.496-.772-1.417-1.122-2.316-.883l-.537.143a3.375 3.375 0 0 1-2.434-2.652l-.092-.55c-.151-.904-.933-1.567-1.849-1.567h-2.342Z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                        </svg>
                        <span>CM Irreg Ops</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.dmi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.dmi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        DMI Logs
                    </a>
                    <a href="{{ route('ims.repairs') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.repairs') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.515 10.674a1.875 1.875 0 012.385-2.222l4.896 1.632-1.393-4.18a1.875 1.875 0 012.222-2.385l4.811 1.604a3.75 3.75 0 011.666 6.326L13.626 15h.874a2.25 2.25 0 012.25 2.25v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5h-1.28l-3.476 3.476a3.75 3.75 0 01-6.326-1.666l-1.604-4.811z" clip-rule="evenodd" /></svg>
                        Repair & Workshop
                    </a>
                    <a href="#" class="cbm-nav-item" title="Robbing / Kanibal Part (Segera Hadir)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" /></svg>
                        Robbing Part <span style="font-size:0.6rem; color:#f59e0b; font-weight:bold; margin-left: auto;">(TBD)</span>
                    </a>
                    <a href="{{ route('ims.requests') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.requests') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v11.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zm-9 13.5a.75.75 0 01.75.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-2.25a.75.75 0 011.5 0v2.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3v-2.25a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                        Install / Request Part
                    </a>
                </div>
            </div>

            <div class="cbm-nav-section-label">Inventory Management</div>
            <div x-data="{ open: {{ request()->routeIs(['ims.*']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['ims.*']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm4.5 7.5a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 01.75-.75zm3.75-1.5a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0V12zm3.75-1.5a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0v-6a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                        </svg>
                        <span>IMS Tracker</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('ims.catalog') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.catalog') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm5.25 0a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM3 12a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm5.25 0a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM3 18a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm5.25 0a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
                        Katalog Barang
                    </a>
                    <a href="{{ route('ims.requests') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.requests') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.625 3A1.125 1.125 0 003.5 4.125v15.75A1.125 1.125 0 004.625 21h14.75A1.125 1.125 0 0020.5 19.875V7.636a1.125 1.125 0 00-.33-.796l-3.34-3.34A1.125 1.125 0 0016.035 3H4.625zM12 9a.75.75 0 01.75.75v1.5h1.5a.75.75 0 010 1.5h-1.5v1.5a.75.75 0 01-1.5 0v-1.5h-1.5a.75.75 0 010-1.5h1.5v-1.5A.75.75 0 0112 9z" clip-rule="evenodd" /></svg>
                        Pengeluaran Barang
                    </a>
                    @can('ims.approval.view')
                    <a href="{{ route('ims.penerimaan') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.penerimaan') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v11.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zm-9 13.5a.75.75 0 01.75.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-2.25a.75.75 0 011.5 0v2.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3v-2.25a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                        Penerimaan Barang
                    </a>
                    <a href="{{ route('ims.opname') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.opname') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.25 2.25a3 3 0 00-3 3v13.5a3 3 0 003 3h13.5a3 3 0 003-3V5.25a3 3 0 00-3-3H5.25zm.75 6.75a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75a.75.75 0 01-.75-.75zm0 4.5a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75a.75.75 0 01-.75-.75zm0 4.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
                        Stock Opname
                    </a>
                    <a href="{{ route('ims.transfer') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.transfer') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0014.536-4.041.75.75 0 00-.507-.912z" clip-rule="evenodd" /></svg>
                        Transfer Stok
                    </a>
                    <a href="{{ route('ims.approvals') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.approvals') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.5 3A1.5 1.5 0 009 4.5h6A1.5 1.5 0 0013.5 3h-3zM3 6.75A2.25 2.25 0 015.25 4.5H7.5v2.25a.75.75 0 00.75.75h7.5a.75.75 0 00.75-.75V4.5h2.25A2.25 2.25 0 0121 6.75v12.5A2.25 2.25 0 0118.75 21.5H5.25A2.25 2.25 0 013 19.25V6.75zm12.35 4.9a.75.75 0 00-1.06-1.06l-4.5 4.5-1.54-1.54a.75.75 0 10-1.06 1.06l2.07 2.07a.75.75 0 001.06 0l5.03-5.03z" clip-rule="evenodd" /></svg>
                        Persetujuan
                    </a>
                    @endcan
                    @can('ims.repair.manage')
                    <a href="{{ route('ims.repairs') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.repairs') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.515 10.674a1.875 1.875 0 012.385-2.222l4.896 1.632-1.393-4.18a1.875 1.875 0 012.222-2.385l4.811 1.604a3.75 3.75 0 011.666 6.326L13.626 15h.874a2.25 2.25 0 012.25 2.25v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5a.75.75 0 00-.75-.75h-1.5a.75.75 0 01-.75-.75v-1.5h-1.28l-3.476 3.476a3.75 3.75 0 01-6.326-1.666l-1.604-4.811z" clip-rule="evenodd" /></svg>
                        Repair Area
                    </a>
                    @endcan
                    @can('ims.report.view')
                    <a href="{{ route('ims.reports') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.reports') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 4.5A2.25 2.25 0 014.5 2.25h15A2.25 2.25 0 0121.75 4.5v15A2.25 2.25 0 0119.5 21.75H4.5A2.25 2.25 0 012.25 19.5v-15zm4.5 11.25a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v3.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3.75zm5.25-3a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v6.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-6.75zm5.25-4.5a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v11.25a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-11.25z" clip-rule="evenodd" /></svg>
                        Laporan Stok
                    </a>
                    @endcan
                    @can('ims.master.manage')
                    <div x-data="{ openMaster: {{ request()->routeIs(['ims.master.*']) ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="openMaster = !openMaster" class="cbm-nav-item {{ request()->routeIs(['ims.master.*']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: .75rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2.25 5.25a3 3 0 013-3h13.5a3 3 0 013 3V15a3 3 0 01-3 3H5.25a3 3 0 01-3-3V5.25zM4.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM19.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM4.5 9a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM19.5 9a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
                                <span>Data Master</span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="openMaster ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <div x-show="openMaster" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                            <a href="{{ route('ims.master') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master') ? 'cbm-active' : '' }}">Dashboard Master</a>
                            <a href="{{ route('ims.master.items') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master.items') ? 'cbm-active' : '' }}">Data Barang</a>
                            <a href="{{ route('ims.master.categories') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master.categories') ? 'cbm-active' : '' }}">Kategori Barang</a>
                            <a href="{{ route('ims.master.units') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master.units') ? 'cbm-active' : '' }}">Satuan (UOM)</a>
                            <a href="{{ route('ims.master.locations') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master.locations') ? 'cbm-active' : '' }}">Lokasi & Rak</a>
                            <a href="{{ route('ims.master.suppliers') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master.suppliers') ? 'cbm-active' : '' }}">Data Supplier</a>
                            <a href="{{ route('ims.master.aircraft-types') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('ims.master.aircraft-types') ? 'cbm-active' : '' }}">Tipe Pesawat</a>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>

            <div class="cbm-nav-section-label">NSRDI Management</div>
            <div x-data="{ open: {{ request()->routeIs(['modules.nsrdi-no-spare', 'modules.nsrdi-overdue']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.nsrdi-no-spare', 'modules.nsrdi-overdue']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" />
                        </svg>
                        <span>NSRDI</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.nsrdi-overdue') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi-overdue') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        NSRDI Overdue
                    </a>
                    <a href="{{ route('modules.nsrdi-no-spare') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.nsrdi-no-spare') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        NSRDI No Spare
                    </a>
                </div>
            </div>

            <div class="cbm-nav-section-label">ICT Management</div>
            <div x-data="{ open: {{ request()->routeIs(['modules.ict-pi', 'modules.ict-tbd']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['modules.ict-pi', 'modules.ict-tbd']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                        </svg>
                        <span>Finding ICT</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.ict-pi') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.ict-pi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Findings ICT PI
                    </a>
                    <a href="{{ route('modules.ict-tbd') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.ict-tbd') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        Findings ICT (TBD)
                    </a>
                </div>
            </div>

            <div class="cbm-nav-section-label">Capacity Management</div>
            <a href="{{ route('modules.capacity') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.capacity') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" /></svg>
                Capacity
            </a>
            <a href="{{ route('modules.aircraft-rotation') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.aircraft-rotation') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0013.621-4.004.75.75 0 00-.53-.919z" clip-rule="evenodd" /></svg>
                Aircraft Rotation
            </a>
            <a href="{{ route('modules.ac-movement') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('modules.ac-movement') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M15.97 2.47a.75.75 0 011.06 0l4.5 4.5a.75.75 0 010 1.06l-4.5 4.5a.75.75 0 11-1.06-1.06l3.22-3.22H7.5a.75.75 0 010-1.5h11.69l-3.22-3.22a.75.75 0 010-1.06zm-7.94 9a.75.75 0 010 1.06l-3.22 3.22H16.5a.75.75 0 010 1.5H4.81l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
                AC Movement
            </a>


            @hasanyrole([\App\Helpers\RoleHelper::ADMIN_CGK, \App\Helpers\RoleHelper::PIC_CABIN, \App\Helpers\RoleHelper::PIC_AIC, \App\Helpers\RoleHelper::PIC_PAINTING, \App\Helpers\RoleHelper::PIC_SUPPORTING])
            <div class="cbm-nav-section-label">Operasional</div>
            
            @hasrole(\App\Helpers\RoleHelper::ADMIN_CGK)
            <!-- Removed Import DJA as requested -->
            @endhasrole

            <a href="{{ route('verification.queue') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('verification.queue') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-2.625 6c-.54 0-.828.419-.936.634a1.96 1.96 0 00-.189.866c0 .298.059.605.189.866.108.215.395.634.936.634.54 0 .828-.419.936-.634.13-.26.189-.568.189-.866 0-.298-.059-.605-.189-.866-.108-.215-.395-.634-.936-.634zm4.314.634c.108-.215.395-.634.936-.634.54 0 .828.419.936.634.13.26.189.568.189.866 0 .298-.059.605-.189.866-.108.215-.395.634-.936.634-.54 0-.828-.419-.936-.634a1.96 1.96 0 01-.189-.866c0-.298.059-.605.189-.866zm-4.34 7.964a.75.75 0 01-1.061-1.06 5.236 5.236 0 013.73-1.538 5.236 5.236 0 013.73 1.538.75.75 0 11-1.06 1.06 3.736 3.736 0 00-2.67-1.098 3.736 3.736 0 00-2.669 1.098z" clip-rule="evenodd" /></svg>
                Verification Queue
            </a>
            
            @hasrole(\App\Helpers\RoleHelper::ADMIN_CGK)
            <a href="{{ route('shift.recap') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('shift.recap') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.125 3C3.089 3 2.25 3.84 2.25 4.875V18a3 3 0 003 3h15a3 3 0 01-3-3V4.875C17.25 3.839 16.41 3 15.375 3H4.125zM12 9.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H12zm-.75-2.25a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H12a.75.75 0 01-.75-.75zM6 12.75a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6zm0 3a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6zm-.75-6a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5H6a.75.75 0 01-.75-.75zM6 6.75a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6z" clip-rule="evenodd" /></svg>
                Shift Recap
            </a>
            @endhasrole

            @hasanyrole([\App\Helpers\RoleHelper::PIC_CABIN, \App\Helpers\RoleHelper::PIC_AIC, \App\Helpers\RoleHelper::PIC_PAINTING, \App\Helpers\RoleHelper::PIC_SUPPORTING])
            <a href="{{ route('tools.equipment') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('tools.equipment') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.75 6.75A3.75 3.75 0 017.5 3h9A3.75 3.75 0 0120.25 6.75v10.5A3.75 3.75 0 0116.5 21h-9a3.75 3.75 0 01-3.75-3.75V6.75zM13.5 6a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM10.5 9a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm0 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm4.5-4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm0 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd" /></svg>
                Tools & Equipment
            </a>
            @endhasrole
            @endhasanyrole

            @hasanyrole([\App\Helpers\RoleHelper::SUPER_ADMIN, \App\Helpers\RoleHelper::MANAGER, \App\Helpers\RoleHelper::ADMIN_CGK, \App\Helpers\RoleHelper::PIC_CABIN, \App\Helpers\RoleHelper::PIC_AIC, \App\Helpers\RoleHelper::PIC_PAINTING, \App\Helpers\RoleHelper::PIC_SUPPORTING])
            <div class="cbm-nav-section-label">Analitik</div>
            
            <a href="{{ route('reports.summary') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.summary') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" /></svg>
                Summary
            </a>
            
            @hasrole(\App\Helpers\RoleHelper::MANAGER)
            <a href="{{ route('reports.executive') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('reports.executive') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z" /></svg>
                Executive Reports
            </a>
            @endhasrole


            @endhasanyrole

            <div class="cbm-nav-section-label">Sistem & Keamanan</div>
            
            @hasrole(\App\Helpers\RoleHelper::SUPER_ADMIN)
            <a href="{{ route('users.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('users.index') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 013.57-4.047zM20.226 19.389a8.287 8.287 0 00-1.308-5.135 3.75 3.75 0 013.57 4.047l-.01.121a.563.563 0 01-.373.486l-.115.04c-.567.2-1.156.349-1.764.441z" /></svg>
                User Management
            </a>
            @endhasrole

            @hasanyrole([\App\Helpers\RoleHelper::SUPER_ADMIN, \App\Helpers\RoleHelper::MANAGER])
            <a href="{{ route('audit.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('audit.index') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                Audit Trail
            </a>
            @endhasanyrole

            <div class="cbm-nav-section-label">Lainnya</div>

            <div x-data="{ open: {{ request()->routeIs(['documents.*']) ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->routeIs(['documents.*']) ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" /></svg>
                        <span>Document Center</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('documents.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('documents.index') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.5 4.5a3 3 0 00-3 3v9a3 3 0 003 3h8.25a3 3 0 003-3v-9a3 3 0 00-3-3H4.5zm0 1.5h8.25a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H4.5A1.5 1.5 0 013 16.5v-9A1.5 1.5 0 014.5 6z" clip-rule="evenodd" /><path d="M19.5 5.25v13.5a1.5 1.5 0 01-1.5 1.5h-3v-16.5h3a1.5 1.5 0 011.5 1.5zM12 9.75a.75.75 0 00-1.5 0v3a.75.75 0 001.5 0v-3z" /></svg>
                        Pusat Dokumen
                    </a>
                    @hasrole(\App\Helpers\RoleHelper::SUPER_ADMIN)
                    <a href="{{ route('documents.master') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('documents.master') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2.25 5.25a3 3 0 013-3h13.5a3 3 0 013 3V15a3 3 0 01-3 3H5.25a3 3 0 01-3-3V5.25zM4.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM19.5 15a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
                        Master Data
                    </a>
                    @endhasrole
                </div>
            </div>

            <a href="{{ route('profile.index') }}" wire:navigate class="cbm-nav-item {{ request()->routeIs('profile.index') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 00-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 00-2.282.819l-.922 1.597a1.875 1.875 0 00.432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 000 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 00-.432 2.385l.922 1.597a1.875 1.875 0 002.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 002.28-.819l.923-1.597a1.875 1.875 0 00-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 000-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 00-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 00-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 00-1.85-1.567h-1.843zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" clip-rule="evenodd" /></svg>
                Profil Saya
            </a>
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
                    <div class="cbm-user-role">{{ auth()->user()->jabatan ?? 'Staff' }}</div>
                </div>
                <a href="#" onclick="event.preventDefault(); document.getElementById('cbm-logout-form').submit();" style="color:var(--cbm-text-sub); display:flex;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem;">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
            <form id="cbm-logout-form" action="{{ route('logout') ?? '#' }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>

    </aside>
