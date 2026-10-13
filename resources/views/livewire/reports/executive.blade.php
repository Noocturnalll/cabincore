<div>
    {{-- Header --}}
    <div class="mod-header" style="margin-bottom:1.25rem;">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">Hasil Produksi &amp; Rapat Koordinasi Operasional</span>
            <h1 class="mod-title" style="display:flex;align-items:center;gap:.6rem;">
                Executive Production Meeting Dashboard
                <span style="font-size:.75rem;padding:.2rem .6rem;border-radius:999px;background:rgba(59,130,246,.15);color:#3b82f6;font-weight:700;">
                    3 Pilar Bank Data Terpadu
                </span>
            </h1>
            <p class="mod-subtitle">
                Konsolidasi hasil produksi Cabin Maintenance, Aircraft Painting, dan Aircraft Cleaning (AIEC) berbasis data DJA terverifikasi
            </p>
        </div>
        <div class="mod-actions">
            <button type="button" onclick="window.print()" class="mod-btn-outline" style="display:inline-flex;align-items:center;gap:.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.1rem;height:1.1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656l10.5 0z" />
                </svg>
                Mode Presentasi / Print
            </button>
            <a href="{{ route('reports.nsrdi-pivot') }}" wire:navigate class="mod-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.1rem;height:1.1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                </svg>
                Pivot Mingguan NSRDI (Kamis)
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="mod-card" style="margin-bottom:1.25rem; padding:.85rem 1.15rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-card-border);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:.85rem;">
            {{-- Presets --}}
            <div style="display:flex; gap:.35rem; align-items:center; flex-wrap:wrap;">
                <span style="font-size:.78rem; font-weight:700; color:var(--cbm-text-muted); margin-right:.25rem;">Periode:</span>
                <button type="button" wire:click="setPreset('today')" class="ch-tab {{ $periodPreset === 'today' ? 'on' : '' }}">Hari Ini</button>
                <button type="button" wire:click="setPreset('yesterday')" class="ch-tab {{ $periodPreset === 'yesterday' ? 'on' : '' }}">Kemarin (H-1)</button>
                <button type="button" wire:click="setPreset('this_week')" class="ch-tab {{ $periodPreset === 'this_week' ? 'on' : '' }}">Pekan Ini</button>
                <button type="button" wire:click="setPreset('this_month')" class="ch-tab {{ $periodPreset === 'this_month' ? 'on' : '' }}">Bulan Berjalan</button>
            </div>

            {{-- Controls --}}
            <div style="display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:.35rem;">
                    <span style="font-size:.78rem; color:var(--cbm-text-muted);">Rentang:</span>
                    <input type="date" wire:model.live="startDate" style="font-size:.8rem; padding:.3rem .5rem; border-radius:.35rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);" />
                    <span style="font-size:.78rem; color:var(--cbm-text-muted);">&ndash;</span>
                    <input type="date" wire:model.live="endDate" style="font-size:.8rem; padding:.3rem .5rem; border-radius:.35rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);" />
                </div>

                <div style="display:flex; align-items:center; gap:.35rem;">
                    <span style="font-size:.78rem; color:var(--cbm-text-muted);">Stasiun:</span>
                    <select wire:model.live="station" style="font-size:.8rem; padding:.3rem .6rem; border-radius:.35rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                        <option value="">Semua Stasiun (Nasional)</option>
                        @foreach($stations as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" wire:click="resetFilters" style="font-size:.78rem; padding:.3rem .6rem; border-radius:.35rem; background:transparent; border:1px solid var(--cbm-card-border); color:var(--cbm-text-muted); cursor:pointer;">
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- TOP KPI CARDS (3 PILAR + MAN HOURS) --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        {{-- 1. CABIN MAINTENANCE --}}
        <div class="mod-card" style="border-left:4px solid #3b82f6; position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <span style="font-size:.72rem; font-weight:800; color:#3b82f6; text-transform:uppercase; letter-spacing:.05em;">Pilar 1: Cabin Maintenance</span>
                    <h3 style="font-size:1.65rem; font-weight:800; color:var(--cbm-text); margin:.25rem 0 .15rem;">
                        {{ number_format($report['kpis']['cbm']['closed']) }} <span style="font-size:.95rem; font-weight:600; color:var(--cbm-text-muted);">/ {{ number_format($report['kpis']['cbm']['total']) }} Closed</span>
                    </h3>
                </div>
                <span style="padding:.2rem .5rem; border-radius:999px; font-size:.8rem; font-weight:800; background:rgba(59,130,246,.15); color:#3b82f6;">
                    {{ $report['kpis']['cbm']['rate'] }}% Rate
                </span>
            </div>
            <div style="font-size:.78rem; color:var(--cbm-text-muted); margin-top:.5rem; display:flex; justify-content:space-between;">
                <span>Target: <strong>{{ number_format($report['kpis']['cbm']['target_period']) }}</strong> ({{ $report['kpis']['cbm']['target_achievement'] }}%)</span>
                <span>Open: <strong style="color:{{ $report['kpis']['cbm']['open'] > 0 ? '#ef4444' : '#10b981' }};">{{ number_format($report['kpis']['cbm']['open']) }}</strong></span>
            </div>
            <div style="font-size:.72rem; color:var(--cbm-text-muted); margin-top:.35rem;">
                Planned (DJA): {{ $report['kpis']['cbm']['planned'] }} &bull; Unplanned: {{ $report['kpis']['cbm']['unplanned'] }}
            </div>
        </div>

        {{-- 2. AIRCRAFT PAINTING --}}
        <div class="mod-card" style="border-left:4px solid #ec4899;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <span style="font-size:.72rem; font-weight:800; color:#ec4899; text-transform:uppercase; letter-spacing:.05em;">Pilar 2: Aircraft Painting</span>
                    <h3 style="font-size:1.65rem; font-weight:800; color:var(--cbm-text); margin:.25rem 0 .15rem;">
                        {{ number_format($report['kpis']['painting']['closed']) }} <span style="font-size:.95rem; font-weight:600; color:var(--cbm-text-muted);">/ {{ number_format($report['kpis']['painting']['total']) }} Closed</span>
                    </h3>
                </div>
                <span style="padding:.2rem .5rem; border-radius:999px; font-size:.8rem; font-weight:800; background:rgba(236,72,153,.15); color:#ec4899;">
                    {{ $report['kpis']['painting']['rate'] }}% Rate
                </span>
            </div>
            <div style="font-size:.78rem; color:var(--cbm-text-muted); margin-top:.5rem; display:flex; justify-content:space-between;">
                <span>Open: <strong style="color:{{ $report['kpis']['painting']['open'] > 0 ? '#ef4444' : '#10b981' }};">{{ number_format($report['kpis']['painting']['open']) }} job</strong></span>
                <span>Planned: <strong>{{ $report['kpis']['painting']['planned'] }}</strong></span>
            </div>
            <div style="font-size:.72rem; color:var(--cbm-text-muted); margin-top:.35rem;">
                PPO &bull; Livery &bull; Exterior Placard &bull; Touch Up
            </div>
        </div>

        {{-- 3. AIRCRAFT CLEANING (AIEC) --}}
        <div class="mod-card" style="border-left:4px solid #10b981;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <span style="font-size:.72rem; font-weight:800; color:#10b981; text-transform:uppercase; letter-spacing:.05em;">Pilar 3: Aircraft Cleaning (AIEC)</span>
                    <h3 style="font-size:1.65rem; font-weight:800; color:var(--cbm-text); margin:.25rem 0 .15rem;">
                        {{ number_format($report['kpis']['cleaning']['total']) }} <span style="font-size:.95rem; font-weight:600; color:var(--cbm-text-muted);">Aktivitas</span>
                    </h3>
                </div>
                <span style="padding:.2rem .5rem; border-radius:999px; font-size:.8rem; font-weight:800; background:rgba(16,185,129,.15); color:#10b981;">
                    {{ $report['kpis']['cleaning']['rate'] }}% Closed
                </span>
            </div>
            <div style="font-size:.78rem; color:var(--cbm-text-muted); margin-top:.5rem; display:flex; justify-content:space-between;">
                <span>DCI: <strong>{{ $report['kpis']['cleaning']['dci'] }}</strong> (Target: {{ $report['kpis']['cleaning']['dci_target'] }})</span>
                <span>DCE: <strong>{{ $report['kpis']['cleaning']['dce'] }}</strong> (Target: {{ $report['kpis']['cleaning']['dce_target'] }})</span>
            </div>
            <div style="font-size:.72rem; color:var(--cbm-text-muted); margin-top:.35rem;">
                Transit: {{ number_format($report['kpis']['cleaning']['transit']) }} &bull; General: {{ number_format($report['kpis']['cleaning']['general']) }}
            </div>
        </div>

        {{-- 4. MAN HOURS & TOTAL RECORD --}}
        <div class="mod-card" style="border-left:4px solid #8b5cf6;">
            <div>
                <span style="font-size:.72rem; font-weight:800; color:#8b5cf6; text-transform:uppercase; letter-spacing:.05em;">Total Jam Kerja Produksi</span>
                <h3 style="font-size:1.65rem; font-weight:800; color:var(--cbm-text); margin:.25rem 0 .15rem;">
                    {{ number_format($report['kpis']['man_hours'], 1) }} <span style="font-size:.95rem; font-weight:600; color:var(--cbm-text-muted);">MHRS</span>
                </h3>
            </div>
            <div style="font-size:.78rem; color:var(--cbm-text-muted); margin-top:.5rem;">
                Periode Evaluasi: <strong>{{ $report['period']['label'] }} ({{ $report['period']['days'] }} hari)</strong>
            </div>
            <div style="font-size:.72rem; color:var(--cbm-text-muted); margin-top:.35rem;">
                Cakupan Stasiun: <strong>{{ $report['station'] ?: 'Seluruh Stasiun Aktif' }}</strong>
            </div>
        </div>
    </div>

    {{-- PILLAR TABS NAV --}}
    <div style="display:flex; gap:.4rem; margin-bottom:1.25rem; border-bottom:1px solid var(--cbm-card-border); padding-bottom:.75rem; overflow-x:auto;">
        <button type="button" wire:click="setPillar('all')" class="ch-tab {{ $activePillar === 'all' ? 'on' : '' }}">
            Semua Pilar (Konsolidasi Produksi)
        </button>
        <button type="button" wire:click="setPillar('cbm')" class="ch-tab {{ $activePillar === 'cbm' ? 'on' : '' }}">
            Pilar 1: Cabin Maintenance Matrix
        </button>
        <button type="button" wire:click="setPillar('painting')" class="ch-tab {{ $activePillar === 'painting' ? 'on' : '' }}">
            Pilar 2: Aircraft Painting Matrix
        </button>
        <button type="button" wire:click="setPillar('cleaning')" class="ch-tab {{ $activePillar === 'cleaning' ? 'on' : '' }}">
            Pilar 3: Aircraft Cleaning Hub &amp; Kapabilitas
        </button>
    </div>

    {{-- SECTION 1: CBM MATRIX --}}
    @if(in_array($activePillar, ['all', 'cbm'], true))
        <div class="mod-card" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
                <div>
                    <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">
                        Matriks Hasil Produksi Cabin Maintenance (CBM) per Stasiun
                    </h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">
                        Rincian perolehan pekerjaan WO, DMI, NSRDI, dan CML Log terverifikasi
                    </p>
                </div>
                <span style="font-size:.75rem; padding:.25rem .6rem; border-radius:999px; background:rgba(59,130,246,.12); color:#3b82f6; font-weight:700;">
                    Single Source of Truth: DJA &amp; Daily Report
                </span>
            </div>

            <div class="mod-table-wrap">
                <table class="mod-table" style="text-align:right;">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Stasiun</th>
                            <th style="text-align:right;">NSRDI (Cls/Tot)</th>
                            <th style="text-align:right;">WO (Cls/Tot)</th>
                            <th style="text-align:right;">DMI (Cls/Tot)</th>
                            <th style="text-align:right;">CML (Cls/Tot)</th>
                            <th style="text-align:right; font-weight:800; background:rgba(255,255,255,.04);">Total Produksi</th>
                            <th style="text-align:right;">Closed</th>
                            <th style="text-align:right;">Open</th>
                            <th style="text-align:right;">Closure Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['matrices']['cbm_stations'] as $m)
                            <tr>
                                <td style="text-align:left; font-weight:700;">{{ $m['station'] }}</td>
                                <td>{{ $m['nsrdi_closed'] }} / {{ $m['nsrdi'] }}</td>
                                <td>{{ $m['wo_closed'] }} / {{ $m['wo'] }}</td>
                                <td>{{ $m['dmi_closed'] }} / {{ $m['dmi'] }}</td>
                                <td>{{ $m['cml_closed'] }} / {{ $m['cml'] }}</td>
                                <td style="font-weight:800; background:rgba(255,255,255,.04); color:#3b82f6;">{{ $m['total'] }}</td>
                                <td style="color:#10b981; font-weight:700;">{{ $m['closed'] }}</td>
                                <td style="color:{{ $m['open'] > 0 ? '#ef4444' : 'var(--cbm-text-muted)' }}; font-weight:700;">{{ $m['open'] }}</td>
                                <td>
                                    <span style="font-weight:700; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; background:{{ $m['rate'] >= 80 ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)' }}; color:{{ $m['rate'] >= 80 ? '#10b981' : '#ef4444' }};">
                                        {{ $m['rate'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center; padding:1.5rem; color:var(--cbm-text-muted);">
                                    Tidak ada data aktivitas Cabin Maintenance pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($report['matrices']['cbm_stations']))
                        <tfoot>
                            <tr style="font-weight:800; border-top:2px solid var(--cbm-card-border); background:rgba(255,255,255,.04);">
                                <td style="text-align:left;">Total Sistem</td>
                                <td>{{ $report['kpis']['cbm']['nsrdi_closed'] }} / {{ $report['kpis']['cbm']['total'] }}</td>
                                <td>{{ collect($report['matrices']['cbm_stations'])->sum('wo_closed') }} / {{ collect($report['matrices']['cbm_stations'])->sum('wo') }}</td>
                                <td>{{ collect($report['matrices']['cbm_stations'])->sum('dmi_closed') }} / {{ collect($report['matrices']['cbm_stations'])->sum('dmi') }}</td>
                                <td>{{ collect($report['matrices']['cbm_stations'])->sum('cml_closed') }} / {{ collect($report['matrices']['cbm_stations'])->sum('cml') }}</td>
                                <td style="color:#3b82f6; font-size:.95rem;">{{ $report['kpis']['cbm']['total'] }}</td>
                                <td style="color:#10b981; font-size:.95rem;">{{ $report['kpis']['cbm']['closed'] }}</td>
                                <td style="color:#ef4444; font-size:.95rem;">{{ $report['kpis']['cbm']['open'] }}</td>
                                <td style="color:#10b981; font-size:.95rem;">{{ $report['kpis']['cbm']['rate'] }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- SECTION 2: PAINTING MATRIX --}}
    @if(in_array($activePillar, ['all', 'painting'], true))
        <div class="mod-card" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
                <div>
                    <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">
                        Matriks Hasil Produksi Aircraft Painting per Stasiun &amp; Jenis Pekerjaan
                    </h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">
                        Rekapitulasi defect PPO, livery, placard, dan touch up cat dari NSRDI &amp; CML Log
                    </p>
                </div>
            </div>

            <div class="mod-table-wrap">
                <table class="mod-table" style="text-align:right;">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Stasiun</th>
                            @foreach($report['matrices']['paint_job_types'] as $jt)
                                <th style="text-align:right; font-size:.78rem;">{{ $jt }}</th>
                            @endforeach
                            <th style="text-align:right; font-weight:800; background:rgba(255,255,255,.04);">Total</th>
                            <th style="text-align:right;">Closed</th>
                            <th style="text-align:right;">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['matrices']['painting_stations'] as $p)
                            <tr>
                                <td style="text-align:left; font-weight:700;">{{ $p['station'] }}</td>
                                @foreach($report['matrices']['paint_job_types'] as $jt)
                                    <td style="color:{{ $p['types'][$jt] > 0 ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
                                        {{ $p['types'][$jt] ?: '-' }}
                                    </td>
                                @endforeach
                                <td style="font-weight:800; background:rgba(255,255,255,.04); color:#ec4899;">{{ $p['total'] }}</td>
                                <td style="color:#10b981; font-weight:700;">{{ $p['closed'] }}</td>
                                <td style="color:{{ $p['open'] > 0 ? '#ef4444' : 'var(--cbm-text-muted)' }}; font-weight:700;">{{ $p['open'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['matrices']['paint_job_types']) + 4 }}" style="text-align:center; padding:1.5rem; color:var(--cbm-text-muted);">
                                    Tidak ada data aktivitas Aircraft Painting pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- SECTION 3: CLEANING HUB CAPABILITY MATRIX --}}
    @if(in_array($activePillar, ['all', 'cleaning'], true))
        <div class="mod-card" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
                <div>
                    <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">
                        Matriks Kapabilitas &amp; Utilisasi Kuota Aircraft Cleaning (10 Stasiun Hub)
                    </h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">
                        Pemenuhan kuota Deep Cleaning Interior (DCI) dan Exterior (DCE) vs Rencana Manpower (MP Plan)
                    </p>
                </div>
            </div>

            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>STA</th>
                            <th>Nama Bandara / Hub</th>
                            <th style="text-align:center;">Limit DCI</th>
                            <th style="text-align:center;">Realisasi DCI</th>
                            <th style="text-align:center;">Limit DCE</th>
                            <th style="text-align:center;">Realisasi DCE</th>
                            <th>Transit &amp; Gen</th>
                            <th style="text-align:center;">MP Plan</th>
                            <th>Status Beban</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['matrices']['cleaning_hubs'] as $ch)
                            <tr>
                                <td><span style="font-weight:800; color:var(--cbm-text);">{{ $ch['code'] }}</span></td>
                                <td style="font-size:.8rem; font-weight:600;">{{ $ch['name'] }}</td>
                                <td style="text-align:center; font-weight:700;">{{ $ch['dci_cap'] ?: '-' }}</td>
                                <td style="text-align:center; color:#3b82f6; font-weight:700;">
                                    {{ $ch['dci_done'] }}
                                    <div style="font-size:.7rem; color:var(--cbm-text-muted);">({{ $ch['avg_dci'] }}/hari)</div>
                                </td>
                                <td style="text-align:center; font-weight:700;">{{ $ch['dce_cap'] ?: '-' }}</td>
                                <td style="text-align:center; color:#ec4899; font-weight:700;">
                                    {{ $ch['dce_done'] }}
                                    <div style="font-size:.7rem; color:var(--cbm-text-muted);">({{ $ch['avg_dce'] }}/hari)</div>
                                </td>
                                <td style="font-size:.75rem;">
                                    Gen: {{ $ch['general_done'] }} | Tra: {{ $ch['transit_done'] }}
                                </td>
                                <td style="text-align:center; font-size:.8rem;">
                                    {{ $ch['mp_plan'] > 0 ? $ch['mp_plan'].' MP' : '-' }}
                                </td>
                                <td>
                                    <span style="font-size:.75rem; font-weight:700; padding:.15rem .45rem; border-radius:.25rem; background:{{ $ch['status_class'] === 'green' ? 'rgba(16,185,129,.15)' : ($ch['status_class'] === 'amber' ? 'rgba(245,158,11,.15)' : 'rgba(239,68,68,.15)') }}; color:{{ $ch['status_class'] === 'green' ? '#10b981' : ($ch['status_class'] === 'amber' ? '#f59e0b' : '#ef4444') }};">
                                        {{ $ch['status_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- SECTION 4: PARETO ANALYSIS & MEETING DISCUSSION POINTS --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1rem;">
        {{-- Top Defect Systems --}}
        <div class="mod-card">
            <h4 style="font-size:.95rem; font-weight:700; color:var(--cbm-text); margin:0 0 .75rem;">
                Top 6 Kategori Kerusakan Terbanyak (Pareto)
            </h4>
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                @forelse($report['pareto']['top_defects'] as $def)
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:.78rem; margin-bottom:.2rem;">
                            <span style="font-weight:600; color:var(--cbm-text);">{{ $def['name'] }}</span>
                            <span style="font-weight:700; color:var(--cbm-text-muted);">{{ $def['count'] }} item ({{ $def['percentage'] }}%)</span>
                        </div>
                        <div style="width:100%; height:6px; background:var(--cbm-input-bg); border-radius:999px; overflow:hidden;">
                            <div style="height:100%; width:{{ min(100, $def['percentage'] * 2.5) }}%; background:#3b82f6; border-radius:999px;"></div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:.8rem; color:var(--cbm-text-muted);">Belum ada data temuan defect pada periode ini.</p>
                @endforelse
            </div>
        </div>

        {{-- Top Reasons Open --}}
        <div class="mod-card">
            <h4 style="font-size:.95rem; font-weight:700; color:var(--cbm-text); margin:0 0 .75rem;">
                Analisis Kendala: Alasan Pekerjaan Belum Closed (Open)
            </h4>
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                @forelse($report['pareto']['top_reasons_open'] as $rea)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:.45rem .6rem; border-radius:.35rem; background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border);">
                        <span style="font-size:.8rem; font-weight:600; color:var(--cbm-text);">{{ $rea['reason'] }}</span>
                        <span style="font-size:.8rem; font-weight:800; color:#ef4444; padding:.1rem .4rem; background:rgba(239,68,68,.12); border-radius:.25rem;">
                            {{ $rea['count'] }} job
                        </span>
                    </div>
                @empty
                    <p style="font-size:.8rem; color:#10b981; font-weight:600;">Semua pekerjaan pada periode ini berstatus Closed! ✓</p>
                @endforelse
            </div>
        </div>

        {{-- Top Aircraft Affected --}}
        <div class="mod-card">
            <h4 style="font-size:.95rem; font-weight:700; color:var(--cbm-text); margin:0 0 .75rem;">
                Armada dengan Defect / Beban Pekerjaan Terbanyak
            </h4>
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                @forelse($report['pareto']['top_aircraft'] as $ac)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:.45rem .6rem; border-radius:.35rem; background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border);">
                        <span style="font-size:.85rem; font-weight:800; color:var(--cbm-text);">{{ $ac['reg'] }}</span>
                        <span style="font-size:.8rem; font-weight:700; color:#3b82f6;">{{ $ac['count'] }} aktivitas</span>
                    </div>
                @empty
                    <p style="font-size:.8rem; color:var(--cbm-text-muted);">Belum ada data armada pada periode ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
