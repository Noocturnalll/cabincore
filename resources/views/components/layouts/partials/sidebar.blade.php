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

            <a href="{{ route('dashboard') }}" class="cbm-nav-item {{ request()->routeIs('dashboard') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                    <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                </svg>
                Dashboard
            </a>

            @hasrole(\App\Helpers\RoleHelper::SUPER_ADMIN)
            <div class="cbm-nav-section-label">Data Master</div>
            <a href="{{ route('master.airports') }}" class="cbm-nav-item {{ request()->routeIs('master.airports') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" /><path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" /></svg>
                Bandara / Stasiun
            </a>
            <a href="{{ route('master.aircraft') }}" class="cbm-nav-item {{ request()->routeIs('master.aircraft') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.75 12a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
                Registrasi Pesawat
            </a>
            <a href="{{ route('master.categories') }}" class="cbm-nav-item {{ request()->routeIs('master.categories') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.5 3.75a6 6 0 00-5.98 6.496A5.25 5.25 0 005.25 20.25H16.5a5.25 5.25 0 002.05-10.024 6 6 0 00-8.05-6.476z" clip-rule="evenodd" /></svg>
                Kategori Pekerjaan
            </a>
            @endhasrole

            @hasanyrole([\App\Helpers\RoleHelper::ADMIN_CGK, \App\Helpers\RoleHelper::PIC_CABIN, \App\Helpers\RoleHelper::PIC_AIC, \App\Helpers\RoleHelper::PIC_PAINTING, \App\Helpers\RoleHelper::PIC_SUPPORTING])
            <div class="cbm-nav-section-label">Operasional</div>
            
            @hasrole(\App\Helpers\RoleHelper::ADMIN_CGK)
            <!-- Removed Import DJA as requested -->
            @endhasrole

            <a href="{{ route('verification.queue') }}" class="cbm-nav-item {{ request()->routeIs('verification.queue') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-2.625 6c-.54 0-.828.419-.936.634a1.96 1.96 0 00-.189.866c0 .298.059.605.189.866.108.215.395.634.936.634.54 0 .828-.419.936-.634.13-.26.189-.568.189-.866 0-.298-.059-.605-.189-.866-.108-.215-.395-.634-.936-.634zm4.314.634c.108-.215.395-.634.936-.634.54 0 .828.419.936.634.13.26.189.568.189.866 0 .298-.059.605-.189.866-.108.215-.395.634-.936.634-.54 0-.828-.419-.936-.634a1.96 1.96 0 01-.189-.866c0-.298.059-.605.189-.866zm-4.34 7.964a.75.75 0 01-1.061-1.06 5.236 5.236 0 013.73-1.538 5.236 5.236 0 013.73 1.538.75.75 0 11-1.06 1.06 3.736 3.736 0 00-2.67-1.098 3.736 3.736 0 00-2.669 1.098z" clip-rule="evenodd" /></svg>
                Verification Queue
            </a>
            
            @hasrole(\App\Helpers\RoleHelper::ADMIN_CGK)
            <a href="{{ route('shift.recap') }}" class="cbm-nav-item {{ request()->routeIs('shift.recap') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.125 3C3.089 3 2.25 3.84 2.25 4.875V18a3 3 0 003 3h15a3 3 0 01-3-3V4.875C17.25 3.839 16.41 3 15.375 3H4.125zM12 9.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H12zm-.75-2.25a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H12a.75.75 0 01-.75-.75zM6 12.75a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6zm0 3a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6zm-.75-6a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5H6a.75.75 0 01-.75-.75zM6 6.75a.75.75 0 000 1.5h7.5a.75.75 0 000-1.5H6z" clip-rule="evenodd" /></svg>
                Shift Recap
            </a>
            @endhasrole

            @hasanyrole([\App\Helpers\RoleHelper::PIC_CABIN, \App\Helpers\RoleHelper::PIC_AIC, \App\Helpers\RoleHelper::PIC_PAINTING, \App\Helpers\RoleHelper::PIC_SUPPORTING])
            <a href="{{ route('tools.equipment') }}" class="cbm-nav-item {{ request()->routeIs('tools.equipment') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.75 6.75A3.75 3.75 0 017.5 3h9A3.75 3.75 0 0120.25 6.75v10.5A3.75 3.75 0 0116.5 21h-9a3.75 3.75 0 01-3.75-3.75V6.75zM13.5 6a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM10.5 9a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm0 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm4.5-4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm0 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd" /></svg>
                Tools & Equipment
            </a>
            @endhasrole
            @endhasanyrole

            <div class="cbm-nav-section-label">Modul CBM</div>
            <div x-data="{ open: {{ request()->is('modules/*') ? 'true' : 'false' }} }">
                <a href="#" @click.prevent="open = !open" class="cbm-nav-item {{ request()->is('modules/*') ? 'cbm-active' : '' }}" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.5 22.5a3 3 0 003-3v-8.174l-6.879 4.022 3.485 1.876a.75.75 0 11-.712 1.321l-5.683-3.06a1.5 1.5 0 00-1.422 0l-5.683 3.06a.75.75 0 01-.712-1.32l3.485-1.877L1.5 11.326V19.5a3 3 0 003 3h15z" />
                            <path d="M1.5 9.589v-.745a3 3 0 011.57-2.641l7.5-4.039a3 3 0 012.86 0l7.5 4.039a3 3 0 011.57 2.641v.745l-8.318 4.864a1.5 1.5 0 01-1.514 0L1.5 9.589z" />
                        </svg>
                        <span>Cabin Maintenance</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0; transition: transform 0.2s ease;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:leave="transition ease-in duration-150" style="padding-left: 2rem; margin-top: 0.25rem; overflow: hidden; display: flex; flex-direction: column; gap: 0.25rem;">
                    <a href="{{ route('modules.dja') }}" class="cbm-nav-item {{ request()->routeIs('modules.dja') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        DJA
                    </a>
                    <a href="{{ route('modules.cml') }}" class="cbm-nav-item {{ request()->routeIs('modules.cml') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        CML Logs
                    </a>
                    <a href="{{ route('modules.nsrdi') }}" class="cbm-nav-item {{ request()->routeIs('modules.nsrdi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        NSRDI Logs
                    </a>
                    <a href="{{ route('modules.dmi') }}" class="cbm-nav-item {{ request()->routeIs('modules.dmi') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        DMI Logs
                    </a>
                    <a href="{{ route('modules.wo') }}" class="cbm-nav-item {{ request()->routeIs('modules.wo') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /></svg>
                        WO Logs
                    </a>
                    <a href="{{ route('modules.ict') }}" class="cbm-nav-item {{ request()->routeIs('modules.ict') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" /><path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" /></svg>
                        Finding ICT
                    </a>
                    <a href="{{ route('modules.daily-report') }}" class="cbm-nav-item {{ request()->routeIs('modules.daily-report') ? 'cbm-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 013-3h3a3 3 0 013 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0112 15.75c-2.73 0-5.36-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 017.5 5.455V5.25zm7.5 0v.09a49.488 49.488 0 00-6 0v-.09a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5zm-3 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 00.713.31A26.226 26.226 0 0012 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 01-6.477-.427C4.047 21.128 3 19.852 3 18.4z" /></svg>
                        Daily Report
                    </a>
                </div>
            </div>

            @hasanyrole([\App\Helpers\RoleHelper::MANAGER, \App\Helpers\RoleHelper::PIC_CABIN, \App\Helpers\RoleHelper::PIC_AIC, \App\Helpers\RoleHelper::PIC_PAINTING, \App\Helpers\RoleHelper::PIC_SUPPORTING])
            <div class="cbm-nav-section-label">Analitik</div>
            
            @hasrole(\App\Helpers\RoleHelper::MANAGER)
            <a href="{{ route('reports.executive') }}" class="cbm-nav-item {{ request()->routeIs('reports.executive') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z" /></svg>
                Executive Reports
            </a>
            @endhasrole

            <a href="{{ route('aircraft.history') }}" class="cbm-nav-item {{ request()->routeIs('aircraft.history') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" /></svg>
                Aircraft History
            </a>
            @endhasanyrole

            <div class="cbm-nav-section-label">Sistem & Keamanan</div>
            
            @hasrole(\App\Helpers\RoleHelper::SUPER_ADMIN)
            <a href="{{ route('users.index') }}" class="cbm-nav-item {{ request()->routeIs('users.index') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 013.57-4.047zM20.226 19.389a8.287 8.287 0 00-1.308-5.135 3.75 3.75 0 013.57 4.047l-.01.121a.563.563 0 01-.373.486l-.115.04c-.567.2-1.156.349-1.764.441z" /></svg>
                User Management
            </a>
            @endhasrole

            @hasanyrole([\App\Helpers\RoleHelper::SUPER_ADMIN, \App\Helpers\RoleHelper::MANAGER])
            <a href="{{ route('audit.index') }}" class="cbm-nav-item {{ request()->routeIs('audit.index') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                Audit Trail
            </a>
            @endhasanyrole

            <div class="cbm-nav-section-label">Lainnya</div>

            <a href="{{ route('documents.index') }}" class="cbm-nav-item {{ request()->routeIs('documents.index') ? 'cbm-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" /></svg>
                Document Center
            </a>

            <a href="{{ route('profile.index') }}" class="cbm-nav-item {{ request()->routeIs('profile.index') ? 'cbm-active' : '' }}">
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
                    <div class="cbm-user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ auth()->user()->name ?? 'User' }}
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
