<div>
    <style>
        @media print {
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
            .cbm-card { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; margin-bottom: 2rem !important; }
            .print-color-adjust { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .kpi-grid { grid-template-columns: repeat(4, 1fr) !important; gap: 1rem !important; }
            .kpi-box { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
            .station-table th, .station-table td { border: 1px solid #e5e7eb !important; padding: 0.5rem !important; }
        }
        
        .toggle-container {
            position: relative;
            background: var(--cbm-bg);
            padding: 0.35rem;
            border-radius: 999px;
            border: 1px solid var(--cbm-card-border);
            display: inline-flex;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        .toggle-pill {
            position: absolute;
            top: 0.35rem;
            bottom: 0.35rem;
            left: 0.35rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #ec4899, #be185d);
            box-shadow: 0 4px 12px rgba(236,72,153,0.3);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            width: 100px;
            z-index: 0;
        }
        .toggle-btn {
            position: relative;
            padding: 0.5rem 0;
            width: 100px;
            text-align: center;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.3s ease;
            border: none;
            background: transparent;
            color: var(--cbm-text-muted);
            z-index: 1;
        }
        .toggle-btn:hover { color: var(--cbm-text); }
        .toggle-btn.active { color: white !important; }

        .content-wrapper {
            transition: opacity 0.3s ease, transform 0.3s ease, filter 0.3s ease;
        }
        .content-wrapper.is-loading {
            opacity: 0.6;
            transform: translateY(4px) scale(0.995);
            filter: blur(2px);
            pointer-events: none;
        }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; }
        
        .kpi-box {
            border-radius: 1rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s; position: relative; overflow: hidden;
            background: rgba(var(--kpi-r), var(--kpi-g), var(--kpi-b), 0.08);
            border: 1px solid rgba(var(--kpi-r), var(--kpi-g), var(--kpi-b), 0.15);
        }
        .cbm-light .kpi-box {
            background: rgba(var(--kpi-r), var(--kpi-g), var(--kpi-b), 0.12);
            border: 1px solid rgba(var(--kpi-r), var(--kpi-g), var(--kpi-b), 0.25);
        }
        .kpi-box:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); }
        .kpi-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; z-index: 1; }
        .kpi-val { font-size: 2rem; font-weight: 800; color: var(--cbm-text); line-height: 1.1; z-index: 1; }
        .kpi-sub { font-size: 0.75rem; font-weight: 600; margin-top: 0.25rem; z-index: 1; }
        .kpi-icon { position: absolute; right: -0.5rem; bottom: -1rem; width: 5rem; height: 5rem; opacity: 0.08; transform: rotate(-10deg); pointer-events: none; }

        .station-table { width: 100%; border-collapse: collapse; text-align: left; }
        .station-table th { padding: 1rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cbm-text-muted); border-bottom: 1px solid var(--cbm-card-border); background: var(--cbm-bg); }
        .station-table td { padding: 1rem; border-bottom: 1px solid var(--cbm-card-border); color: var(--cbm-text); vertical-align: middle; }
        .station-table tr:last-child td { border-bottom: none; }
        .progress-bar { height: 0.5rem; border-radius: 999px; background: var(--cbm-bg); overflow: hidden; display: flex; width: 100px; margin-top: 0.25rem; }
        .progress-fill { height: 100%; border-radius: 999px; transition: width 0.5s ease; }
    </style>

    <div class="cbm-page-header no-print" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, #ec4899, #be185d); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(236,72,153,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.025em;">Summary Report</h1>
                <p class="cbm-greeting-sub" style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.25rem;">Indikator Kinerja Utama (KPI) Operasional</p>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="toggle-container">
                <!-- Sliding Background Pill -->
                <div class="toggle-pill" 
                     style="{{ $period === 'daily' ? 'transform: translateX(0);' : ($period === 'weekly' ? 'transform: translateX(100%);' : 'transform: translateX(200%);') }}">
                </div>
                
                <button type="button" wire:click="setPeriod('daily')" class="toggle-btn {{ $period === 'daily' ? 'active' : '' }}">Harian</button>
                <button type="button" wire:click="setPeriod('weekly')" class="toggle-btn {{ $period === 'weekly' ? 'active' : '' }}">Mingguan</button>
                <button type="button" wire:click="setPeriod('monthly')" class="toggle-btn {{ $period === 'monthly' ? 'active' : '' }}">Bulanan</button>
            </div>

            <button onclick="window.print()" class="cbm-btn cbm-btn-primary" style="background: var(--cbm-card-bg); color: var(--cbm-text); border: 1px solid var(--cbm-card-border); display: flex; gap: 0.5rem; align-items: center; padding: 0 1.25rem; height: 2.75rem; border-radius: 0.875rem; font-weight: 600; cursor: pointer; transition: transform 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Download PDF
            </button>
        </div>
    </div>

    <!-- Print Area Start -->
    <div id="print-area" class="content-wrapper" wire:loading.class="is-loading">
        <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text); margin: 0 0 0.25rem 0;">Laporan Eksekutif ({{ ucfirst($period) }})</h2>
            <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin: 0;">Periode: {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>
        </div>

        @hasanyrole([\App\Helpers\RoleHelper::SUPER_ADMIN, \App\Helpers\RoleHelper::MANAGER, \App\Helpers\RoleHelper::ADMIN_CGK, \App\Helpers\RoleHelper::PIC_CABIN])
        <!-- CABIN MAINTENANCE SECTION -->
        <div class="cbm-card print-color-adjust" style="margin-bottom: 2.5rem; overflow: visible;">
            <div class="cbm-card-header" style="border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 0.25rem; height: 1.5rem; background: #3b82f6; border-radius: 2px;"></div>
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text); margin: 0;">Cabin Maintenance KPI</h3>
            </div>
            
            <div class="cbm-card-body">
                <div class="kpi-grid">
                    
                    <!-- Pengerjaan (Total Work) -->
                    <div class="kpi-box" style="--kpi-r: 59; --kpi-g: 130; --kpi-b: 246;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #3b82f6;"><path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-15a3 3 0 00-3 3V18a3 3 0 003 3h15zM1.5 10.146V6a3 3 0 013-3h5.379a2.25 2.25 0 011.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 013 3v1.146A4.483 4.483 0 0019.5 9h-15a4.483 4.483 0 00-3 1.146z" /></svg>
                        <div class="kpi-title" style="color: #3b82f6;">Pengerjaan (Laporan)</div>
                        <div class="kpi-val">{{ $cabinKpi['totalWork'] }}</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Total Pekerjaan Log</div>
                    </div>

                    <!-- Planning -->
                    <div class="kpi-box" style="--kpi-r: 99; --kpi-g: 102; --kpi-b: 241;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #6366f1;"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #6366f1;">Planning vs Unplanned</div>
                        <div class="kpi-val">{{ $cabinKpi['planned'] }} <span style="font-size: 1rem; color: var(--cbm-text-muted);">/ {{ $cabinKpi['unplanned'] }}</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Terjadwal / Tidak Terjadwal</div>
                    </div>

                    <!-- Closed -->
                    <div class="kpi-box" style="--kpi-r: 16; --kpi-g: 185; --kpi-b: 129;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #10b981;"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #10b981;">Closed (Terselesaikan)</div>
                        <div class="kpi-val">{{ $cabinKpi['closed'] }}</div>
                        <div class="kpi-sub" style="color: #10b981;">{{ $cabinKpi['totalWork'] > 0 ? round(($cabinKpi['closed'] / $cabinKpi['totalWork']) * 100) : 0 }}% Completion Rate</div>
                    </div>

                    <!-- Open -->
                    <div class="kpi-box" style="--kpi-r: 245; --kpi-g: 158; --kpi-b: 11;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #f59e0b;"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #f59e0b;">Open (Aktif)</div>
                        <div class="kpi-val">{{ $cabinKpi['open'] }}</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Pekerjaan Belum Selesai</div>
                    </div>

                    <!-- Overdue -->
                    <div class="kpi-box" style="--kpi-r: 239; --kpi-g: 68; --kpi-b: 68;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #ef4444;"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #ef4444;">Overdue (NSRDI)</div>
                        <div class="kpi-val">{{ $cabinKpi['overdue'] }}</div>
                        <div class="kpi-sub" style="color: #ef4444;">Melewati Due Date</div>
                    </div>

                    <!-- Findings -->
                    <div class="kpi-box" style="--kpi-r: 236; --kpi-g: 72; --kpi-b: 153;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #ec4899;"><path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #ec4899;">Temuan (ICT Finding)</div>
                        <div class="kpi-val">{{ $cabinKpi['findingsTotal'] }}</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">{{ $cabinKpi['findingsOpen'] }} Temuan Masih Open</div>
                    </div>

                    <!-- Man Hours -->
                    <div class="kpi-box" style="--kpi-r: 139; --kpi-g: 92; --kpi-b: 246;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #8b5cf6;"><path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.88 6.27a.75.75 0 011.06 0l1.59 1.59a.75.75 0 11-1.06 1.06l-1.59-1.59a.75.75 0 010-1.06zM6.27 18.88a.75.75 0 010-1.06l1.59-1.59a.75.75 0 111.06 1.06l-1.59 1.59a.75.75 0 01-1.06 0zM20.25 12a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V12.75a.75.75 0 01.75-.75zM6.27 5.12a.75.75 0 011.06 0l1.59 1.59a.75.75 0 01-1.06 1.06L6.27 6.18a.75.75 0 010-1.06zM18.88 17.73a.75.75 0 010 1.06l-1.59 1.59a.75.75 0 11-1.06-1.06l1.59-1.59a.75.75 0 011.06 0zM3.75 12a.75.75 0 01.75-.75h2.25a.75.75 0 010 1.5H4.5a.75.75 0 01-.75-.75z" /></svg>
                        <div class="kpi-title" style="color: #8b5cf6;">Man Hours</div>
                        <div class="kpi-val">{{ $cabinKpi['manHours'] }} <span style="font-size: 1rem; color: var(--cbm-text-muted);">Jam</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Total Jam Kerja WO</div>
                    </div>

                    <!-- Man Power -->
                    <div class="kpi-box" style="--kpi-r: 20; --kpi-g: 184; --kpi-b: 166;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #14b8a6;"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
                        <div class="kpi-title" style="color: #14b8a6;">Man Power</div>
                        <div class="kpi-val">{{ $cabinKpi['manPower'] }} <span style="font-size: 1rem; color: var(--cbm-text-muted);">Orang</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Operator / Teknisi Aktif</div>
                    </div>

                    <!-- Utilization -->
                    <div class="kpi-box" style="--kpi-r: 14; --kpi-g: 165; --kpi-b: 233;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #0ea5e9;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg>
                        <div class="kpi-title" style="color: #0ea5e9;">Utilisasi Man Power</div>
                        <div class="kpi-val">{{ $cabinKpi['utilization'] }}<span style="font-size: 1rem; color: var(--cbm-text-muted);">%</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Optimal: 70-85%</div>
                    </div>

                    <!-- FTR Quality -->
                    <div class="kpi-box" style="--kpi-r: 34; --kpi-g: 197; --kpi-b: 94;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #22c55e;"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #22c55e;">First Time Right (FTR)</div>
                        <div class="kpi-val">{{ $cabinKpi['ftr'] }}<span style="font-size: 1rem; color: var(--cbm-text-muted);">%</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Lolos Tanpa Rework</div>
                    </div>
                </div>
            </div>
        </div>
        @endhasanyrole

        @hasanyrole([\App\Helpers\RoleHelper::SUPER_ADMIN, \App\Helpers\RoleHelper::MANAGER, \App\Helpers\RoleHelper::ADMIN_CGK])
        <!-- AIRCRAFT CLEANING SECTION -->
        <div class="cbm-card print-color-adjust" style="margin-bottom: 2.5rem; overflow: visible;">
            <div class="cbm-card-header" style="border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 0.25rem; height: 1.5rem; background: #14b8a6; border-radius: 2px;"></div>
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text); margin: 0;">Aircraft Cleaning KPI</h3>
            </div>
            
            <div class="cbm-card-body">
                <div class="kpi-grid">
                    
                    <!-- Pengerjaan (Total Work) -->
                    <div class="kpi-box" style="--kpi-r: 20; --kpi-g: 184; --kpi-b: 166;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #14b8a6;"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v.756a49.106 49.106 0 019.152 2.252.75.75 0 01.388 1.177L18.823 10.6a4.5 4.5 0 01-6.289 1.488l-1.025-.572a3 3 0 00-4.192.992l-.248.43a3 3 0 00.992 4.192l.572 1.025a4.5 4.5 0 01-1.488 6.289l-3.411 3.468a.75.75 0 01-1.177-.388A49.106 49.106 0 011.51 12.75h-.756a.75.75 0 010-1.5h.756a49.106 49.106 0 012.252-9.152.75.75 0 011.177-.388l3.468 3.41a4.5 4.5 0 016.289-1.488l1.025.572a3 3 0 004.192-.992l.43-.248a3 3 0 00-.992-4.192l-1.025-.572a4.5 4.5 0 011.488-6.289l3.41-3.468a.75.75 0 011.061 1.06L12 2.25z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #14b8a6;">Pengerjaan (Laporan)</div>
                        <div class="kpi-val">{{ $cleaningKpi['totalWork'] }}</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Total Pesawat Dibersihkan</div>
                    </div>

                    <!-- Planning -->
                    <div class="kpi-box" style="--kpi-r: 14; --kpi-g: 165; --kpi-b: 233;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #0ea5e9;"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #0ea5e9;">Planning vs Unplanned</div>
                        <div class="kpi-val">{{ $cleaningKpi['planned'] }} <span style="font-size: 1rem; color: var(--cbm-text-muted);">/ {{ $cleaningKpi['unplanned'] }}</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Terjadwal / Dadakan</div>
                    </div>

                    <!-- Closed -->
                    <div class="kpi-box" style="--kpi-r: 16; --kpi-g: 185; --kpi-b: 129;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #10b981;"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #10b981;">Closed (Terselesaikan)</div>
                        <div class="kpi-val">{{ $cleaningKpi['closed'] }}</div>
                        <div class="kpi-sub" style="color: #10b981;">{{ $cleaningKpi['totalWork'] > 0 ? round(($cleaningKpi['closed'] / $cleaningKpi['totalWork']) * 100) : 0 }}% Completion Rate</div>
                    </div>

                    <!-- Open -->
                    <div class="kpi-box" style="--kpi-r: 245; --kpi-g: 158; --kpi-b: 11;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #f59e0b;"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #f59e0b;">Open (Aktif)</div>
                        <div class="kpi-val">{{ $cleaningKpi['open'] }}</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Pembersihan Tertunda</div>
                    </div>
                    
                    <!-- Findings -->
                    <div class="kpi-box" style="--kpi-r: 236; --kpi-g: 72; --kpi-b: 153;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #ec4899;"><path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #ec4899;">Temuan (QA Finding)</div>
                        <div class="kpi-val">{{ $cleaningKpi['findingsTotal'] }}</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">{{ $cleaningKpi['findingsOpen'] }} Temuan Masih Open</div>
                    </div>

                    <!-- Man Hours -->
                    <div class="kpi-box" style="--kpi-r: 139; --kpi-g: 92; --kpi-b: 246;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #8b5cf6;"><path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.88 6.27a.75.75 0 011.06 0l1.59 1.59a.75.75 0 11-1.06 1.06l-1.59-1.59a.75.75 0 010-1.06zM6.27 18.88a.75.75 0 010-1.06l1.59-1.59a.75.75 0 111.06 1.06l-1.59 1.59a.75.75 0 01-1.06 0zM20.25 12a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V12.75a.75.75 0 01.75-.75zM6.27 5.12a.75.75 0 011.06 0l1.59 1.59a.75.75 0 01-1.06 1.06L6.27 6.18a.75.75 0 010-1.06zM18.88 17.73a.75.75 0 010 1.06l-1.59 1.59a.75.75 0 11-1.06-1.06l1.59-1.59a.75.75 0 011.06 0zM3.75 12a.75.75 0 01.75-.75h2.25a.75.75 0 010 1.5H4.5a.75.75 0 01-.75-.75z" /></svg>
                        <div class="kpi-title" style="color: #8b5cf6;">Man Hours</div>
                        <div class="kpi-val">{{ $cleaningKpi['manHours'] }} <span style="font-size: 1rem; color: var(--cbm-text-muted);">Jam</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Total Jam Kerja Cleaning</div>
                    </div>

                    <!-- Man Power -->
                    <div class="kpi-box" style="--kpi-r: 99; --kpi-g: 102; --kpi-b: 241;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #6366f1;"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
                        <div class="kpi-title" style="color: #6366f1;">Man Power</div>
                        <div class="kpi-val">{{ $cleaningKpi['manPower'] }} <span style="font-size: 1rem; color: var(--cbm-text-muted);">Orang</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Cleaner / Staff Aktif</div>
                    </div>

                    <!-- Quality Score -->
                    <div class="kpi-box" style="--kpi-r: 34; --kpi-g: 197; --kpi-b: 94;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #22c55e;"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #22c55e;">Rata-rata Kualitas (Score)</div>
                        <div class="kpi-val">{{ $cleaningKpi['averageScore'] }}%</div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Sangat Memuaskan</div>
                    </div>

                    <!-- Utilization -->
                    <div class="kpi-box" style="--kpi-r: 14; --kpi-g: 165; --kpi-b: 233;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #0ea5e9;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg>
                        <div class="kpi-title" style="color: #0ea5e9;">Utilisasi Man Power</div>
                        <div class="kpi-val">{{ $cleaningKpi['utilization'] }}<span style="font-size: 1rem; color: var(--cbm-text-muted);">%</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Optimal: 70-85%</div>
                    </div>

                    <!-- FTR Quality -->
                    <div class="kpi-box" style="--kpi-r: 34; --kpi-g: 197; --kpi-b: 94;">
                        <svg class="kpi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color: #22c55e;"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                        <div class="kpi-title" style="color: #22c55e;">First Time Right (FTR)</div>
                        <div class="kpi-val">{{ $cleaningKpi['ftr'] }}<span style="font-size: 1rem; color: var(--cbm-text-muted);">%</span></div>
                        <div class="kpi-sub" style="color: var(--cbm-text-muted);">Lolos Tanpa Rework</div>
                    </div>
                </div>
            </div>
        </div>
        @endhasanyrole

        <!-- STATION LEVEL KPI TRACKING (TARGET 100%) -->
        <div class="cbm-card print-color-adjust" style="margin-bottom: 2.5rem; overflow: visible;">
            <div class="cbm-card-header" style="border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 0.25rem; height: 1.5rem; background: #6366f1; border-radius: 2px;"></div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text); margin: 0;">Station Performance & Analytics (Target 100%)</h3>
                </div>
                <div style="font-size: 0.75rem; color: var(--cbm-text-muted); display: flex; gap: 1rem; align-items: center;">
                    <span style="display: flex; align-items: center; gap: 0.25rem;"><div style="width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #22c55e;"></div> > 80% Baik</span>
                    <span style="display: flex; align-items: center; gap: 0.25rem;"><div style="width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #f59e0b;"></div> 50-80% Waspada</span>
                    <span style="display: flex; align-items: center; gap: 0.25rem;"><div style="width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #ef4444;"></div> < 50% Kritis</span>
                </div>
            </div>
            
            <div class="cbm-card-body" style="padding: 0; overflow-x: auto;">
                <table class="station-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">Station</th>
                            <th style="width: 15%;">Pencapaian KPI (%)</th>
                            <th style="width: 10%;">CML Closed</th>
                            <th style="width: 10%;">NSRDI KPI</th>
                            <th style="width: 10%;">DMI KPI</th>
                            <th style="width: 10%;">WO KPI</th>
                            <th style="width: 12%;">Man Power (MP)</th>
                            <th style="width: 12%;">Man Hours</th>
                            <th style="width: 13%;">Cleaning KPI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stationKpis as $sk)
                        <tr style="{{ !$sk['isActive'] ? 'opacity: 0.5;' : '' }} transition: background 0.2s;" onmouseover="this.style.background='rgba(0,0,0,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="font-weight: 700;">{{ $sk['station'] }}</td>
                            
                            <!-- Overall Score -->
                            <td>
                                @php
                                    $scoreColor = $sk['overallScore'] >= 80 ? '#22c55e' : ($sk['overallScore'] >= 50 ? '#f59e0b' : '#ef4444');
                                @endphp
                                <div style="display: flex; align-items: center; justify-content: space-between; font-weight: 700; color: {{ $scoreColor }}; margin-bottom: 0.25rem;">
                                    <span>{{ $sk['overallScore'] }}%</span>
                                    <span style="font-size: 0.65rem; color: var(--cbm-text-muted);">Target: 100%</span>
                                </div>
                                <div class="progress-bar" style="width: 100%; background: var(--cbm-card-border);">
                                    <div class="progress-fill" style="width: {{ $sk['overallScore'] }}%; background: {{ $scoreColor }};"></div>
                                </div>
                            </td>

                            <!-- CML Closed -->
                            <td>
                                <div style="font-weight: 600; font-size: 1.125rem;">{{ $sk['cmlClosed'] }}</div>
                            </td>

                            <!-- NSRDI KPI -->
                            <td>
                                @php $nColor = $sk['nsrdiKPI'] >= 80 ? '#22c55e' : ($sk['nsrdiKPI'] >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                                <span style="font-weight: 700; color: {{ $nColor }};">{{ $sk['nsrdiKPI'] }}%</span>
                            </td>

                            <!-- DMI KPI -->
                            <td>
                                @php $dColor = $sk['dmiKPI'] >= 80 ? '#22c55e' : ($sk['dmiKPI'] >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                                <span style="font-weight: 700; color: {{ $dColor }};">{{ $sk['dmiKPI'] }}%</span>
                            </td>

                            <!-- WO KPI -->
                            <td>
                                @php $wColor = $sk['woKPI'] >= 80 ? '#22c55e' : ($sk['woKPI'] >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                                <span style="font-weight: 700; color: {{ $wColor }};">{{ $sk['woKPI'] }}%</span>
                            </td>

                            <!-- Man Power -->
                            <td>
                                <div style="font-weight: 600;">{{ $sk['manPower'] }} <span style="font-size: 0.75rem; color: var(--cbm-text-muted);">Pax</span></div>
                            </td>

                            <!-- Man Hours -->
                            <td>
                                <div style="font-weight: 600;">{{ $sk['manHours'] }} <span style="font-size: 0.75rem; color: var(--cbm-text-muted);">Jam</span></div>
                            </td>

                            <!-- Cleaning KPI -->
                            <td>
                                @php $cColor = $sk['cleaningScore'] >= 80 ? '#22c55e' : ($sk['cleaningScore'] >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-weight: 700; color: {{ $cColor }};">{{ $sk['cleaningScore'] }}%</span>
                                </div>
                                <div class="progress-bar" style="width: 100%; height: 0.25rem;">
                                    <div class="progress-fill" style="width: {{ $sk['cleaningScore'] }}%; background: {{ $cColor }};"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PARETO ANALYSIS: TOP 5 RECURRING ISSUES -->
        <div class="cbm-card print-color-adjust" style="margin-bottom: 2.5rem; overflow: visible;">
            <div class="cbm-card-header" style="border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 0.25rem; height: 1.5rem; background: #ec4899; border-radius: 2px;"></div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text); margin: 0;">Top 5 Rekurensi Temuan Kategori (Pareto Analysis)</h3>
                </div>
            </div>
            
            <div class="cbm-card-body" style="padding: 0; display: flex; flex-direction: column; gap: 1rem; padding-bottom: 1rem;">
                @php $maxTotal = max(array_column($topIssues, 'total')) ?: 1; @endphp
                @foreach($topIssues as $index => $issue)
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 0 1.25rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">
                            <span style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="background: rgba(236,72,153,0.1); color: #ec4899; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">{{ $index + 1 }}</span>
                                {{ $issue['wo_category'] ?? 'Uncategorized' }}
                            </span>
                            <span>{{ $issue['total'] }} Laporan</span>
                        </div>
                        <div class="progress-bar" style="width: 100%; height: 0.5rem; background: var(--cbm-card-border); border-radius: 999px;">
                            <div class="progress-fill" style="width: {{ ($issue['total'] / $maxTotal) * 100 }}%; background: linear-gradient(90deg, #ec4899, #be185d); border-radius: 999px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
    <!-- Print Area End -->
</div>
