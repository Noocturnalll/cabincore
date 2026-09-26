<div>
<style>
/* â•â•â• Dashboard &mdash; Enterprise Colorful Edition â•â•â• */

/* â”€â”€ Page header â”€â”€ */
.cbm-page-header { margin-bottom: 2rem; }
.cbm-greeting {
    font-size: clamp(1.5rem, 3vw, 2.125rem);
    font-weight: 800;
    color: var(--cbm-text);
    letter-spacing: -.025em;
    line-height: 1.15;
}
.cbm-greeting span {
    background: linear-gradient(90deg, #60a5fa, #a78bfa, #f472b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.cbm-greeting-sub {
    font-size: .9375rem;
    color: var(--cbm-text-muted);
    margin-top: .35rem;
    font-weight: 500;
}

/* â”€â”€ Stat Cards Grid â”€â”€ */
.cbm-stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.375rem;
}
@media (max-width: 1200px) { .cbm-stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px)  { .cbm-stats-grid { grid-template-columns: 1fr; } }

.cbm-stat-card {
    position: relative;
    border-radius: 1.25rem;
    padding: 1.375rem 1.25rem 1.125rem;
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
    border: 1px solid transparent;
}
.cbm-stat-card:hover { transform: translateY(-3px); }

/* coloured card variants */
.cbm-sc-blue {
    background: linear-gradient(145deg, rgba(59,130,246,.18) 0%, rgba(99,102,241,.10) 100%);
    border-color: rgba(59,130,246,.25);
    box-shadow: 0 4px 24px rgba(59,130,246,.18), 0 1px 4px rgba(0,0,0,.10);
}
.cbm-sc-blue::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background: linear-gradient(90deg, #3b82f6, #6366f1);
}
.cbm-sc-green {
    background: linear-gradient(145deg, rgba(34,197,94,.16) 0%, rgba(16,185,129,.08) 100%);
    border-color: rgba(34,197,94,.25);
    box-shadow: 0 4px 24px rgba(34,197,94,.18), 0 1px 4px rgba(0,0,0,.10);
}
.cbm-sc-green::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background: linear-gradient(90deg, #22c55e, #10b981);
}
.cbm-sc-orange {
    background: linear-gradient(145deg, rgba(251,146,60,.16) 0%, rgba(245,158,11,.08) 100%);
    border-color: rgba(251,146,60,.25);
    box-shadow: 0 4px 24px rgba(251,146,60,.18), 0 1px 4px rgba(0,0,0,.10);
}
.cbm-sc-orange::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background: linear-gradient(90deg, #fb923c, #f59e0b);
}
.cbm-sc-purple {
    background: linear-gradient(145deg, rgba(168,85,247,.16) 0%, rgba(236,72,153,.08) 100%);
    border-color: rgba(168,85,247,.25);
    box-shadow: 0 4px 24px rgba(168,85,247,.18), 0 1px 4px rgba(0,0,0,.10);
}
.cbm-sc-purple::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background: linear-gradient(90deg, #a855f7, #ec4899);
}

/* Light mode overrides for stat cards */
.cbm-light .cbm-sc-blue   { background: linear-gradient(145deg, rgba(59,130,246,.09) 0%, rgba(99,102,241,.05) 100%); box-shadow: 0 4px 20px rgba(59,130,246,.15), 0 1px 3px rgba(0,0,0,.05); }
.cbm-light .cbm-sc-green  { background: linear-gradient(145deg, rgba(34,197,94,.09) 0%, rgba(16,185,129,.04) 100%); box-shadow: 0 4px 20px rgba(34,197,94,.15), 0 1px 3px rgba(0,0,0,.05); }
.cbm-light .cbm-sc-orange { background: linear-gradient(145deg, rgba(251,146,60,.09) 0%, rgba(245,158,11,.04) 100%); box-shadow: 0 4px 20px rgba(251,146,60,.15), 0 1px 3px rgba(0,0,0,.05); }
.cbm-light .cbm-sc-purple { background: linear-gradient(145deg, rgba(168,85,247,.09) 0%, rgba(236,72,153,.04) 100%); box-shadow: 0 4px 20px rgba(168,85,247,.15), 0 1px 3px rgba(0,0,0,.05); }

.cbm-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: .875rem;
}
.cbm-stat-label { font-size: .8125rem; font-weight: 700; color: var(--cbm-text-muted); letter-spacing: .01em; }
.cbm-stat-icon {
    width: 2.75rem; height: 2.75rem;
    border-radius: .875rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.cbm-stat-icon svg { width: 1.375rem; height: 1.375rem; color: #fff; }
.cbm-si-blue   { background: linear-gradient(135deg,#3b82f6,#6366f1); box-shadow: 0 6px 16px rgba(59,130,246,.4); }
.cbm-si-green  { background: linear-gradient(135deg,#22c55e,#10b981); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
.cbm-si-orange { background: linear-gradient(135deg,#fb923c,#f59e0b); box-shadow: 0 6px 16px rgba(251,146,60,.4); }
.cbm-si-purple { background: linear-gradient(135deg,#a855f7,#ec4899); box-shadow: 0 6px 16px rgba(168,85,247,.4); }

.cbm-stat-value {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--cbm-text);
    line-height: 1;
    margin-bottom: .625rem;
    letter-spacing: -.02em;
}
.cbm-stat-sub-row { display: flex; gap: .5rem; flex-wrap: wrap; }
.cbm-stat-chip {
    font-size: .6875rem; font-weight: 700;
    background: rgba(255,255,255,.08);
    color: var(--cbm-text-muted);
    padding: .2rem .5rem; border-radius: 999px;
    border: 1px solid rgba(255,255,255,.1);
}
.cbm-light .cbm-stat-chip { background: rgba(0,0,0,.05); border-color: rgba(0,0,0,.08); }

/* â”€â”€ KPI Row â”€â”€ */
.cbm-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 1200px) { .cbm-kpi-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px)  { .cbm-kpi-row { grid-template-columns: 1fr; } }

.cbm-kpi-card {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.125rem;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--cbm-card-shadow);
    transition: transform .2s ease, box-shadow .2s ease;
}
.cbm-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.2); }
.cbm-light .cbm-kpi-card:hover { box-shadow: 0 8px 24px rgba(37,99,235,.12); }
.cbm-kpi-icon {
    width: 2.625rem; height: 2.625rem;
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.cbm-kpi-icon svg { width: 1.25rem; height: 1.25rem; color: #fff; }
.cbm-kpi-label { font-size: .6875rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .06em; }
.cbm-kpi-value { font-size: 1.375rem; font-weight: 800; color: var(--cbm-text); margin-top: .1rem; letter-spacing: -.015em; }
.cbm-kpi-sub   { font-size: .75rem; color: var(--cbm-text-muted); margin-top: .2rem; font-weight: 500; }

/* â”€â”€ Charts Row â”€â”€ */
.cbm-charts-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 1280px) { .cbm-charts-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px)  { .cbm-charts-row { grid-template-columns: 1fr; } }

.cbm-chart-card {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.25rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    box-shadow: var(--cbm-card-shadow);
    transition: box-shadow .2s ease;
}
.cbm-chart-card:hover { box-shadow: 0 12px 36px rgba(0,0,0,.25); }
.cbm-light .cbm-chart-card:hover { box-shadow: 0 8px 28px rgba(37,99,235,.12); }

.cbm-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.25rem;
}
.cbm-card-title { font-size: 1rem; font-weight: 800; color: var(--cbm-text); letter-spacing: -.01em; }
.cbm-card-sub   { font-size: .8125rem; color: var(--cbm-text-muted); margin-top: .2rem; font-weight: 500; }
.cbm-chart-container { flex: 1; min-height: 230px; position: relative; }

/* â”€â”€ Donut â”€â”€ */
.cbm-donut-wrap { position: relative; display: flex; flex-direction: column; align-items: center; }
.cbm-donut-center {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    text-align: center; pointer-events: none;
}
.cbm-donut-center-value { font-size: 1.625rem; font-weight: 800; color: var(--cbm-text); line-height: 1; }
.cbm-donut-center-label { font-size: .6875rem; color: var(--cbm-text-muted); font-weight: 600; margin-top: .2rem; }
.cbm-donut-legend { display: flex; flex-direction: column; gap: .5rem; margin-top: 1rem; width: 100%; }
.cbm-donut-legend-item { display: flex; align-items: center; justify-content: space-between; font-size: .8125rem; font-weight: 600; }
.cbm-dli-left   { display: flex; align-items: center; gap: .5rem; color: var(--cbm-text); }
.cbm-dli-dot    { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.cbm-dli-val    { font-weight: 800; color: var(--cbm-text); }
.cbm-dli-detail { font-size: .7rem; color: var(--cbm-text-muted); padding-left: 1.25rem; margin-top: .15rem; }

/* â”€â”€ Legend chips â”€â”€ */
.cbm-legend { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.cbm-legend-item { display: flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); }
.cbm-legend-dot { width: .5rem; height: .5rem; border-radius: 50%; }

/* â”€â”€ Table at bottom â”€â”€ */
.cbm-bottom-table {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: var(--cbm-card-shadow);
    overflow: hidden;
}

/* â”€â”€ Responsive: main page wrapper â”€â”€ */
@media (max-width: 640px) {
    .cbm-stats-grid { gap: .875rem; }
    .cbm-kpi-row    { gap: .875rem; }
    .cbm-charts-row { gap: 1rem; }
    .cbm-stat-value { font-size: 1.875rem; }
}
</style>

@php
    $dja = $stats['dja'] ?? [];
    $unplanned = $stats['unplanned'] ?? [];
    $djaTotal   = $dja['total']  ?? 0;
    $djaClosed  = $dja['closed'] ?? 0;
    $djaOpen    = $dja['open']   ?? 0;
    $cmlClosed      = $stats['cml_closed']    ?? 0;
    $djaCloseRate   = $stats['dja_close_rate']  ?? 0;
    $unplannedCloseRate = $unplanned['all_rate'] ?? 0;
    $targetDate = $stats['target_date'] ?? now()->format('Y-m-d');
    $targetDateFormatted = \Carbon\Carbon::parse($targetDate)->locale('id')->isoFormat('dddd, D MMMM YYYY');
@endphp

{{-- â•â•â•â• PAGE HEADER â•â•â•â• --}}
<div class="cbm-page-header">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
        <div>
            <div class="cbm-greeting">
                Selamat datang, <span>{{ explode(' ', auth()->user()->name)[0] }}</span> &#x1F44B;
            </div>
            <div class="cbm-greeting-sub">Monitor operasional cabin maintenance harian Anda</div>
        </div>
        <div style="display:inline-flex;align-items:center;gap:.5rem;background:var(--cbm-nav-active);border:1px solid var(--cbm-card-border);border-radius:.875rem;padding:.5rem 1rem;font-size:.8125rem;font-weight:700;color:var(--cbm-nav-active-t);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
            Data: {{ $targetDateFormatted }}
        </div>
    </div>
</div>

{{-- â•â•â•â• TOP 4 STAT CARDS â•â•â•â• --}}
<div class="cbm-stats-grid">

    {{-- Card 1: Total DJA --}}
    <div class="cbm-stat-card cbm-sc-blue">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Semua Total Laporan DJA</div>
            <div class="cbm-stat-icon cbm-si-blue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z"/><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"/></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $djaTotal }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">WO: {{ $dja['wo_total'] ?? 0 }}</span>
            <span class="cbm-stat-chip">DMI: {{ $dja['dmi_total'] ?? 0 }}</span>
            <span class="cbm-stat-chip">NSRDI: {{ $dja['nsrdi_total'] ?? 0 }}</span>
        </div>
    </div>

    {{-- Card 2: Total Closed --}}
    <div class="cbm-stat-card cbm-sc-green">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Total Closed DJA</div>
            <div class="cbm-stat-icon cbm-si-green">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $djaClosed }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">WO: {{ $dja['wo_closed'] ?? 0 }}</span>
            <span class="cbm-stat-chip">DMI: {{ $dja['dmi_closed'] ?? 0 }}</span>
            <span class="cbm-stat-chip">NSRDI: {{ $dja['nsrdi_closed'] ?? 0 }}</span>
        </div>
    </div>

    {{-- Card 3: Total Open --}}
    <div class="cbm-stat-card cbm-sc-orange">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Total Open DJA</div>
            <div class="cbm-stat-icon cbm-si-orange">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd"/></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $djaOpen }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">WO: {{ $dja['wo_open'] ?? 0 }}</span>
            <span class="cbm-stat-chip">DMI: {{ $dja['dmi_open'] ?? 0 }}</span>
            <span class="cbm-stat-chip">NSRDI: {{ $dja['nsrdi_open'] ?? 0 }}</span>
        </div>
    </div>

    {{-- Card 4: CML Closed --}}
    <div class="cbm-stat-card cbm-sc-purple">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Total CML Closed</div>
            <div class="cbm-stat-icon cbm-si-purple">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 116 0h3a.75.75 0 00.75-.75V15z"/><path d="M8.25 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM15.75 6.75a.75.75 0 00-.75.75v11.25c0 .087.015.17.042.248a3 3 0 015.958.464c.853-.175 1.522-.935 1.464-1.883a18.845 18.845 0 00-3.414-9.787 2.037 2.037 0 00-1.6-.773H15.75z"/><path d="M19.5 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z"/></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $cmlClosed }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">Dokumen CML selesai</span>
        </div>
    </div>

    {{-- Card 5: Total ICT Findings --}}
    <div class="cbm-stat-card cbm-sc-orange">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Total ICT Findings</div>
            <div class="cbm-stat-icon" style="background:linear-gradient(135deg,rgba(251,146,60,.15),rgba(245,158,11,.15));color:#f59e0b; width: 2.75rem; height: 2.75rem; border-radius: .875rem; display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.5rem;height:1.5rem;"><path fill-rule="evenodd" d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" /><path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" /></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $stats['ict']['total'] ?? 0 }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">Open: {{ $stats['ict']['open'] ?? 0 }}</span>
            <span class="cbm-stat-chip">Closed: {{ $stats['ict']['closed'] ?? 0 }}</span>
        </div>
        
    </div>

    {{-- Card 6: Aircraft Cleaning --}}
    <div class="cbm-stat-card cbm-sc-green">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Aircraft Cleaning</div>
            <div class="cbm-stat-icon cbm-si-green">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.971 1.816A5.208 5.208 0 0014.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.258 5.258 0 013.39 1.258A.75.75 0 0021 8.25V5.25a2.25 2.25 0 00-2.25-2.25h-5.25c-.2 0-.398.026-.579.066zm-1.942 0a5.258 5.258 0 00-3.39 1.258A.75.75 0 006.5 2.5V5.25a2.25 2.25 0 002.25 2.25h5.25c.2 0 .398-.026.579-.066A5.208 5.208 0 0012.971 1.816z" clip-rule="evenodd"/>
                    <path fill-rule="evenodd" d="M14.25 8.25v-1.875a3.708 3.708 0 00-1.026-2.58A3.758 3.758 0 0115.5 5.25h1.875a3.75 3.75 0 012.125.666v2.334h-5.25zm-4.5 0h5.25v12a2.25 2.25 0 01-2.25 2.25H9A2.25 2.25 0 016.75 20.25v-12h2.25a3.708 3.708 0 001.026 2.58A3.758 3.758 0 019.5 8.25z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $stats['ac']['total'] ?? 0 }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">GC: {{ $stats['ac']['gc_total'] ?? 0 }}</span>
            <span class="cbm-stat-chip">DCI: {{ $stats['ac']['dci_total'] ?? 0 }}</span>
            <span class="cbm-stat-chip">DCE: {{ $stats['ac']['dce_total'] ?? 0 }}</span>
            <span class="cbm-stat-chip">TC: {{ $stats['ac']['tc_total'] ?? 0 }}</span>
        </div>
    </div>

</div>

{{-- â•â•â•â• KPI ROW â•â•â•â• --}}
{{-- â• â• â• â•  MAN POWER & MAN HOURS ROW â• â• â• â•  --}}
<div class="cbm-kpi-row" style="margin-bottom: 1.5rem; grid-template-columns: repeat(2, 1fr);">
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(59,130,246,.05) 0%, rgba(99,102,241,.02) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1);box-shadow:0 6px 16px rgba(59,130,246,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Total Man Power (PIC)</div>
            <div class="cbm-kpi-value">{{ $stats['man_power'] ?? 0 }} <span style="font-size:1rem;color:var(--cbm-text-muted);">Personel</span></div>
            <div class="cbm-kpi-sub">Total teknisi / PIC di sistem</div>
        </div>
    </div>
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(168,85,247,.05) 0%, rgba(236,72,153,.02) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#a855f7,#ec4899);box-shadow:0 6px 16px rgba(168,85,247,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Man Hours (WO)</div>
            <div class="cbm-kpi-value">{{ $stats['man_hours'] ?? 0 }} <span style="font-size:1rem;color:var(--cbm-text-muted);">Jam</span></div>
            <div class="cbm-kpi-sub">Total estimasi man hour dari log WO hari ini</div>
        </div>
    </div>
</div>
<div class="cbm-kpi-row">
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#06b6d4,#0ea5e9);box-shadow:0 6px 16px rgba(6,182,212,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned WO</div>
            <div class="cbm-kpi-value">{{ $unplanned['wo_total'] ?? 0 }}</div>
            <div class="cbm-kpi-sub">Di luar DJA</div>
        </div>
    </div>
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#f87171,#dc2626);box-shadow:0 6px 16px rgba(248,113,113,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned DMI</div>
            <div class="cbm-kpi-value">{{ $unplanned['dmi_total'] ?? 0 }}</div>
            <div class="cbm-kpi-sub">Di luar DJA</div>
        </div>
    </div>
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 6px 16px rgba(99,102,241,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6zM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned NSRDI</div>
            <div class="cbm-kpi-value">{{ $unplanned['nsrdi_total'] ?? 0 }}</div>
            <div class="cbm-kpi-sub">Di luar DJA</div>
        </div>
    </div>
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);box-shadow:0 6px 16px rgba(245,158,11,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Close Rate DJA</div>
            <div class="cbm-kpi-value">{{ $djaCloseRate }}<span style="font-size:.875rem;font-weight:600;color:var(--cbm-text-muted);">%</span></div>
            <div class="cbm-kpi-sub">+{{ $unplannedCloseRate }}% unplanned</div>
        </div>
    </div>
</div>

{{-- â•â•â•â• CHARTS ROW â•â•â•â• --}}
@php
    $stationStats = $stats['stationStats'] ?? [];
    $stationLabels  = array_keys($stationStats);
    $stationWo      = array_map(function($s) { return $s['details']['wo']['closed'] + $s['details']['wo']['open']; }, array_values($stationStats));
    $stationDmi     = array_map(function($s) { return $s['details']['dmi']['closed'] + $s['details']['dmi']['open']; }, array_values($stationStats));
    $stationNsrdi   = array_map(function($s) { return $s['details']['nsrdi']['closed'] + $s['details']['nsrdi']['open']; }, array_values($stationStats));
    $stationGc      = array_map(function($s) { return ($s['details']['ac_gc']['closed'] ?? 0) + ($s['details']['ac_gc']['open'] ?? 0); }, array_values($stationStats));
    $stationDci     = array_map(function($s) { return ($s['details']['ac_dci']['closed'] ?? 0) + ($s['details']['ac_dci']['open'] ?? 0); }, array_values($stationStats));
    $stationDce     = array_map(function($s) { return ($s['details']['ac_dce']['closed'] ?? 0) + ($s['details']['ac_dce']['open'] ?? 0); }, array_values($stationStats));
    $stationDetails = array_column(array_values($stationStats), 'details');

    $trendLabels     = $stats['trendLabels']     ?? [];
    $trendCmlClosed  = $stats['trendCmlClosed']  ?? [];
    $trendDja        = $stats['trendDja']        ?? [];
    $trendUnplanned  = $stats['trendUnplanned']  ?? [];

    $dClosedTotal = $dja['closed']      ?? 0;
    $dClosedWo    = $dja['wo_closed']   ?? 0;
    $dClosedDmi   = $dja['dmi_closed']  ?? 0;
    $dClosedNsrdi = $dja['nsrdi_closed']?? 0;

    $dOpenTotal   = $dja['open']        ?? 0;
    $dOpenWo      = $dja['wo_open']     ?? 0;
    $dOpenDmi     = $dja['dmi_open']    ?? 0;
    $dOpenNsrdi   = $dja['nsrdi_open']  ?? 0;

    $uTotal = ($unplanned['wo_total'] ?? 0) + ($unplanned['dmi_total'] ?? 0) + ($unplanned['nsrdi_total'] ?? 0);
    $uWo    = $unplanned['wo_total']    ?? 0;
    $uDmi   = $unplanned['dmi_total']   ?? 0;
    $uNsrdi = $unplanned['nsrdi_total'] ?? 0;
    $totalKeseluruhan = $dClosedTotal + $dOpenTotal + $uTotal;
@endphp

<div class="cbm-charts-row">

    {{-- Chart 1: Bar Chart &mdash; DJA per Station --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">DJA per Station</div>
                <div class="cbm-card-sub">WO, DMI, dan NSRDI per bandara</div>
            </div>
            <div class="cbm-legend">
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#3b82f6;"></div>WO</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#f59e0b;"></div>DMI</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#a855f7;"></div>NSRDI</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="cbm-bar-chart" height="230"
                data-labels='@json($stationLabels)'
                data-wo='@json($stationWo)'
                data-dmi='@json($stationDmi)'
                data-nsrdi='@json($stationNsrdi)'
                data-details='@json($stationDetails)'>
            </canvas>
        </div>
    </div>

    {{-- Chart 1.5: AC Bar Chart &mdash; Aircraft Cleaning per Station --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Aircraft Cleaning per Station</div>
                <div class="cbm-card-sub">GC, DCI, DCE, dan TC per bandara</div>
            </div>
            <div class="cbm-legend">
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#22c55e;"></div>GC</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#10b981;"></div>DCI</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#047857;"></div>DCE</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#0891b2;"></div>TC</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="ac-bar-chart" height="230"
                data-labels='@json($stationLabels)'
                data-gc='@json($stationGc)'
                data-dci='@json($stationDci)'
                data-dce='@json($stationDce)'
                data-tc='@json(array_map(function($s) { return ($s["details"]["ac_tc"]["closed"] ?? 0) + ($s["details"]["ac_tc"]["open"] ?? 0); }, array_values($stationStats)))'
                data-details='@json($stationDetails)'>
            </canvas>
        </div>
    </div>

    {{-- Chart 2: Line Chart --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Grafik Penyelesaian</div>
                <div class="cbm-card-sub">Pekerjaan harian &mdash; 7 hari terakhir</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="cbm-line-chart" height="230"></canvas>
        </div>
    </div>

    {{-- Chart 3: Donut Chart --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Status Keseluruhan</div>
                <div class="cbm-card-sub">DJA Closed, Open &amp; Unplanned</div>
            </div>
        </div>
        <div class="cbm-donut-wrap" wire:ignore>
            <div style="position:relative;width:172px;height:172px;margin:0 auto;">
                <canvas id="cbm-donut-chart"
                    data-closed="{{ $dClosedTotal }}"
                    data-open="{{ $dOpenTotal }}"
                    data-unplanned="{{ $uTotal }}"
                    data-total="{{ $totalKeseluruhan }}">
                </canvas>
                <div class="cbm-donut-center">
                    <div class="cbm-donut-center-value">{{ $totalKeseluruhan }}</div>
                    <div class="cbm-donut-center-label">Total</div>
                </div>
            </div>
        </div>
        <div class="cbm-donut-legend">
            {{-- Closed --}}
            <div>
                <div class="cbm-donut-legend-item">
                    <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#34d399;"></span>Closed</span>
                    <span class="cbm-dli-val">{{ $dClosedTotal }}</span>
                </div>
                <div class="cbm-dli-detail">WO {{ $dClosedWo }} &bull; DMI {{ $dClosedDmi }} &bull; NSRDI {{ $dClosedNsrdi }}</div>
            </div>
            {{-- Open --}}
            <div>
                <div class="cbm-donut-legend-item">
                    <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#f87171;"></span>Open</span>
                    <span class="cbm-dli-val">{{ $dOpenTotal }}</span>
                </div>
                <div class="cbm-dli-detail">WO {{ $dOpenWo }} &bull; DMI {{ $dOpenDmi }} &bull; NSRDI {{ $dOpenNsrdi }}</div>
            </div>
            {{-- Unplanned --}}
            <div>
                <div class="cbm-donut-legend-item">
                    <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#818cf8;"></span>Unplanned</span>
                    <span class="cbm-dli-val">{{ $uTotal }}</span>
                </div>
                <div class="cbm-dli-detail">WO {{ $uWo }} &bull; DMI {{ $uDmi }} &bull; NSRDI {{ $uNsrdi }}</div>
            </div>
        </div>
    </div>

</div>

{{-- â•â•â•â• ICT CHARTS ROW â•â•â•â• --}}
<div class="cbm-charts-row" style="grid-template-columns: repeat(2, 1fr); margin-top: 1.375rem;">
    {{-- Chart 4: ICT Daily Chart --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">ICT Harian per Maskapai</div>
                <div class="cbm-card-sub">Data tanggal {{ $targetDateFormatted }}: Open vs Closed</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="ict-daily-chart" height="230"
                data-labels='@json($stats["ict"]["charts"]["daily"]["labels"] ?? [])'
                data-open='@json($stats["ict"]["charts"]["daily"]["open"] ?? [])'
                data-closed='@json($stats["ict"]["charts"]["daily"]["closed"] ?? [])'>
            </canvas>
        </div>
    </div>

    {{-- Chart 5: ICT Monthly Chart --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">ICT Bulanan per Maskapai</div>
                <div class="cbm-card-sub">Data bulan ini: Open vs Closed</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="ict-monthly-chart" height="230"
                data-labels='@json($stats["ict"]["charts"]["monthly"]["labels"] ?? [])'
                data-open='@json($stats["ict"]["charts"]["monthly"]["open"] ?? [])'
                data-closed='@json($stats["ict"]["charts"]["monthly"]["closed"] ?? [])'>
            </canvas>
        </div>
    </div>
</div>

{{-- â•â•â•â• BOTTOM TABLE â•â•â•â• --}}
<div class="cbm-bottom-table">
    <div class="cbm-card-header" style="margin-bottom:1.25rem;">
        <div>
            <div class="cbm-card-title">Prioritas Laporan (Overdue ICT)</div>
            <div class="cbm-card-sub">Daftar ICT Finding yang berstatus Open dan butuh ditindaklanjuti.</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;text-align:left;border-collapse:collapse;font-size:.875rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--cbm-divider);color:var(--cbm-text-muted);font-size:.6875rem;text-transform:uppercase;letter-spacing:.07em;">
                    <th style="padding:.75rem .75rem;font-weight:700;">No</th>
                    <th style="padding:.75rem .75rem;font-weight:700;">Data / Kategori</th>
                    <th style="padding:.75rem .75rem;font-weight:700;">Status</th>
                    <th style="padding:.75rem .75rem;font-weight:700;">Total</th>
                    <th style="padding:.75rem .75rem;font-weight:700;text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($openIctFindings ?? [] as $index => $finding)
                <tr style="border-bottom: 1px solid var(--cbm-border); font-size: 0.875rem; color: var(--cbm-text);" onmouseover="this.style.background='var(--cbm-nav-hover)'" onmouseout="this.style.background=''">
                    <td style="padding: 1rem 0.5rem; color: var(--cbm-text-muted);">{{ $index + 1 }}</td>
                    <td style="padding: 1rem 0.5rem; font-weight: 500; color: var(--cbm-text);">
                        <div style="font-weight: 700; color: var(--cbm-blue);">Finding ICT: {{ $finding->no_finding }}</div>
                        <div style="font-size: 0.75rem; color: var(--cbm-text-muted); margin-top: 0.25rem;">Reg: {{ $finding->aircraft_registration }} | Date: {{ \Carbon\Carbon::parse($finding->date)->format('d M Y') }}</div>
                        <div style="font-size: 0.75rem; color: var(--cbm-text-muted); margin-top: 0.15rem;"><x-text-popup :text="$finding->defect_description ?? '-'" title="Defect Description" /></div>
                    </td>
                    <td style="padding: 1rem 0.5rem;"><span style="background: rgba(248, 113, 113, 0.15); color: #ef4444; padding: 0.35rem 0.75rem; border-radius: 0.375rem; font-weight: 600; font-size: 0.75rem;">Open</span></td>
                    <td style="padding: 1rem 0.5rem; font-weight: 600; color: var(--cbm-text);">1</td>
                    <td style="padding: 1rem 0.5rem; text-align: right; color: var(--cbm-blue); font-weight: 600;">
                        <a href="{{ route('modules.ict') }}" style="color: inherit; text-decoration: none;">Update &rarr;</a>
                    </td>
                </tr>
                @empty
                <tr style="border-bottom: 1px solid var(--cbm-border); font-size: 0.875rem; color: var(--cbm-text);">
                    <td colspan="5" style="padding: 1rem 0.5rem; text-align: center; color: var(--cbm-text-muted);">
                        Tidak ada laporan prioritas / ICT Finding yang berstatus Open.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    <div class="cbm-charts-row" style="margin-top: 1.5rem; grid-template-columns: 1fr;">
        <div class="cbm-chart-card">
            <div class="cbm-card-header">
                <div>
                    <div class="cbm-card-title">NSRDI Overdue (Status: Open)</div>
                    <div class="cbm-card-sub">Laporan NSRDI yang melewati batas Plan Date (Batik, Lion, SAJ, Wings)</div>
                </div>
            </div>
            <div class="cbm-chart-container" wire:ignore>
                <canvas id="nsrdi-overdue-chart" height="230"
                    data-values='@json(array_values($stats["nsrdi_overdue"] ?? []))'
                    data-labels='@json(array_keys($stats["nsrdi_overdue"] ?? []))'>
                </canvas>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var isLight = document.getElementById('cbm-html').classList.contains('cbm-light');
var barChart, lineChart, donutChart, ictDailyChart, ictMonthlyChart, nsrdiOverdueChart;

function textColor()  { return isLight ? '#0f172a' : '#f1f5f9'; }
function mutedColor() { return isLight ? '#64748b' : '#94a3b8'; }
function gridColor()  { return isLight ? 'rgba(148,163,184,.15)' : 'rgba(255,255,255,.06)'; }
function tooltipBg()  { return isLight ? '#ffffff' : '#1e293b'; }

function cbmIntTicks() {
    return {
        stepSize: 1,
        precision: 0,
        callback: function(v) { return Number.isInteger(v) ? v : null; }
    };
}

function cbmInitCharts() {
    if (typeof Chart === 'undefined') return setTimeout(cbmInitCharts, 100);

    var isLightNow = document.getElementById('cbm-html').classList.contains('cbm-light');
    isLight = isLightNow;

    /* â”€â”€â”€ BAR CHART â”€â”€â”€ */
    var barEl = document.getElementById('cbm-bar-chart');
    if (!barEl) return;

    if (barChart) { barChart.destroy(); barChart = null; }
    if (lineChart) { lineChart.destroy(); lineChart = null; }
    if (donutChart) { donutChart.destroy(); donutChart = null; }
    if (ictDailyChart) { ictDailyChart.destroy(); ictDailyChart = null; }
    if (ictMonthlyChart) { ictMonthlyChart.destroy(); ictMonthlyChart = null; }
    if (nsrdiOverdueChart) { nsrdiOverdueChart.destroy(); nsrdiOverdueChart = null; }

    var barLabels  = JSON.parse(barEl.dataset.labels  || '[]');
    var barWo      = JSON.parse(barEl.dataset.wo      || '[]');
    var barDmi     = JSON.parse(barEl.dataset.dmi     || '[]');
    var barNsrdi   = JSON.parse(barEl.dataset.nsrdi   || '[]');
    var barDetails = JSON.parse(barEl.dataset.details || '[]');

    barChart = new Chart(barEl.getContext('2d'), {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [
                { label:'WO',    data: barWo,    backgroundColor:'rgba(59,130,246,.85)',  borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 },
                { label:'DMI',   data: barDmi,   backgroundColor:'rgba(245,158,11,.85)',  borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 },
                { label:'NSRDI', data: barNsrdi, backgroundColor:'rgba(168,85,247,.85)', borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode:'index', intersect:false },
            plugins: {
                legend: { display:false },
                tooltip: {
                    backgroundColor: tooltipBg(), titleColor: textColor(), bodyColor: mutedColor(),
                    borderColor: isLight ? 'rgba(148,163,184,.3)' : 'rgba(255,255,255,.1)', borderWidth:1,
                    padding:12, cornerRadius:10,
                    callbacks: {
                        afterBody: function(ctx) {
                            var idx = ctx[0].dataIndex;
                            if (barDetails && barDetails[idx]) {
                                var d = barDetails[idx];
                                return [
                                    '',
                                    'Rincian Open/Closed:',
                                    '  WO: ' + d.wo.open + ' Open / ' + d.wo.closed + ' Closed',
                                    '  DMI: ' + d.dmi.open + ' Open / ' + d.dmi.closed + ' Closed',
                                    '  NSRDI: ' + d.nsrdi.open + ' Open / ' + d.nsrdi.closed + ' Closed'
                                ];
                            }
                            return [];
                        }
                    }
                }
            },
            scales: {
                x: { stacked:true, grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                y: { stacked:true, grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false} }
            }
        }
    });

    /* ─── AC BAR CHART ─── */
    var acBarEl = document.getElementById('ac-bar-chart');
    if (acBarEl) {
        var acGc   = JSON.parse(acBarEl.dataset.gc  || '[]');
        var acDci  = JSON.parse(acBarEl.dataset.dci || '[]');
        var acDce  = JSON.parse(acBarEl.dataset.dce || '[]');
        var acTc   = JSON.parse(acBarEl.dataset.tc  || '[]');

        new Chart(acBarEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [
                    { label:'GC',  data: acGc,  backgroundColor:'rgba(34,197,94,.85)',  borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'DCI', data: acDci, backgroundColor:'rgba(16,185,129,.85)', borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'DCE', data: acDce, backgroundColor:'rgba(4,120,87,.85)',   borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'TC',  data: acTc,  backgroundColor:'rgba(6,182,212,.85)',  borderRadius:5, barPercentage:0.65, categoryPercentage:0.75 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode:'index', intersect:false },
                plugins: {
                    legend: { display:false },
                    tooltip: {
                        backgroundColor: tooltipBg(), titleColor: textColor(), bodyColor: mutedColor(),
                        borderColor: isLight ? 'rgba(148,163,184,.3)' : 'rgba(255,255,255,.1)', borderWidth:1,
                        padding:12, cornerRadius:10,
                        callbacks: {
                            afterBody: function(ctx) {
                                var idx = ctx[0].dataIndex;
                                if (barDetails && barDetails[idx]) {
                                    var d = barDetails[idx];
                                    return [
                                        '',
                                        'Rincian Open/Closed:',
                                        '  GC: ' + (d.ac_gc?.open||0) + ' Open / ' + (d.ac_gc?.closed||0) + ' Closed',
                                        '  DCI: ' + (d.ac_dci?.open||0) + ' Open / ' + (d.ac_dci?.closed||0) + ' Closed',
                                        '  DCE: ' + (d.ac_dce?.open||0) + ' Open / ' + (d.ac_dce?.closed||0) + ' Closed',
                                        '  TC: ' + (d.ac_tc?.open||0) + ' Open / ' + (d.ac_tc?.closed||0) + ' Closed'
                                    ];
                                }
                                return [];
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked:true, grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y: { stacked:true, grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false} }
                }
            }
        });
    }

    /* â”€â”€â”€ LINE CHART â”€â”€â”€ */
    var lineEl = document.getElementById('cbm-line-chart');
    var lineCtx = lineEl.getContext('2d');

    var gradPurple = lineCtx.createLinearGradient(0, 0, 0, 220);
    gradPurple.addColorStop(0, 'rgba(168,85,247,.35)'); gradPurple.addColorStop(1, 'rgba(168,85,247,0)');
    var gradBlue = lineCtx.createLinearGradient(0, 0, 0, 220);
    gradBlue.addColorStop(0, 'rgba(59,130,246,.35)'); gradBlue.addColorStop(1, 'rgba(59,130,246,0)');
    var gradRed = lineCtx.createLinearGradient(0, 0, 0, 220);
    gradRed.addColorStop(0, 'rgba(248,113,113,.35)'); gradRed.addColorStop(1, 'rgba(248,113,113,0)');

    lineChart = new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: @json($trendLabels ?? []),
            datasets: [
                { label:'CML Closed', data:@json($trendCmlClosed ?? []), borderColor:'#a855f7', backgroundColor:gradPurple, borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#a855f7', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 },
                { label:'Total DJA',  data:@json($trendDja ?? []),       borderColor:'#3b82f6', backgroundColor:gradBlue,   borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#3b82f6', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 },
                { label:'Unplanned',  data:@json($trendUnplanned ?? []), borderColor:'#f87171', backgroundColor:gradRed,    borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#f87171', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{mode:'index',intersect:false},
            plugins: {
                legend:{ display:true, position:'top', align:'end', labels:{color:mutedColor(),font:{weight:'700',size:11},boxWidth:10,usePointStyle:true,pointStyle:'circle'} },
                tooltip:{ backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
            },
            scales: {
                x:{ grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                y:{ grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false}, suggestedMin:0 }
            }
        }
    });

    /* â”€â”€â”€ DONUT CHART â”€â”€â”€ */
    var donutEl = document.getElementById('cbm-donut-chart');
    var dClosed    = parseInt(donutEl.dataset.closed    || 0);
    var dOpen      = parseInt(donutEl.dataset.open      || 0);
    var dUnplanned = parseInt(donutEl.dataset.unplanned || 0);

    donutChart = new Chart(donutEl.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Closed','Open','Unplanned'],
            datasets: [{ data:[dClosed,dOpen,dUnplanned], backgroundColor:['#34d399','#f87171','#818cf8'], borderColor:isLight?'#eef2ff':'#0d1117', borderWidth:4, hoverOffset:8 }]
        },
        options: {
            responsive:true, maintainAspectRatio:true, cutout:'72%',
            plugins: {
                legend:{display:false},
                tooltip:{ backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:10 }
            }
        }
    });

        /* â”€â”€â”€ ICT DAILY CHART â”€â”€â”€ */
    var ictDailyEl = document.getElementById('ict-daily-chart');
    if (ictDailyEl) {
        var dLabels = JSON.parse(ictDailyEl.dataset.labels || '[]');
        var dOpen   = JSON.parse(ictDailyEl.dataset.open || '[]');
        var dClosed = JSON.parse(ictDailyEl.dataset.closed || '[]');

        ictDailyChart = new Chart(ictDailyEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dLabels,
                datasets: [
                    { label:'Closed', data: dClosed, backgroundColor:'rgba(52,211,153,.85)', borderRadius:5, barPercentage:0.7, categoryPercentage:0.8 },
                    { label:'Open', data: dOpen, backgroundColor:'rgba(248,113,113,.85)', borderRadius:5, barPercentage:0.7, categoryPercentage:0.8 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display:true, position:'top', align:'end', labels:{color:mutedColor(),font:{weight:'700',size:11},usePointStyle:true,pointStyle:'circle'} },
                    tooltip: { backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
                },
                scales: {
                    x: { grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y: { grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false} }
                }
            }
        });
    }

    /* â”€â”€â”€ ICT MONTHLY CHART â”€â”€â”€ */
    var ictMonthlyEl = document.getElementById('ict-monthly-chart');
    if (ictMonthlyEl) {
        var mLabels = JSON.parse(ictMonthlyEl.dataset.labels || '[]');
        var mOpen   = JSON.parse(ictMonthlyEl.dataset.open || '[]');
        var mClosed = JSON.parse(ictMonthlyEl.dataset.closed || '[]');

        ictMonthlyChart = new Chart(ictMonthlyEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: mLabels,
                datasets: [
                    { label:'Closed', data: mClosed, backgroundColor:'rgba(52,211,153,.85)', borderRadius:5, barPercentage:0.7, categoryPercentage:0.8 },
                    { label:'Open', data: mOpen, backgroundColor:'rgba(248,113,113,.85)', borderRadius:5, barPercentage:0.7, categoryPercentage:0.8 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display:true, position:'top', align:'end', labels:{color:mutedColor(),font:{weight:'700',size:11},usePointStyle:true,pointStyle:'circle'} },
                    tooltip: { backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
                },
                scales: {
                    x: { grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y: { grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false} }
                }
            }
        });
    }

    /* ─── NSRDI OVERDUE CHART ─── */
    var nsrdiOverdueEl = document.getElementById('nsrdi-overdue-chart');
    if (nsrdiOverdueEl) {
        var nValues = JSON.parse(nsrdiOverdueEl.dataset.values || '[]');
        var nLabels = JSON.parse(nsrdiOverdueEl.dataset.labels || '[]');
        nsrdiOverdueChart = new Chart(nsrdiOverdueEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: nLabels,
                datasets: [{ label:'Overdue Count', data: nValues, backgroundColor:'rgba(239,68,68,.85)', borderRadius:5, barPercentage:0.6, categoryPercentage:0.7 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display:false },
                    tooltip: { backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
                },
                scales: {
                    x: { grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y: { grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false} }
                }
            }
        });
    }

    /* â”€â”€â”€ THEME UPDATER â”€â”€â”€ */
    window.cbmUpdateChartTheme = function(theme) {
        isLight = (theme === 'light');
        [barChart, lineChart, donutChart, ictDailyChart, ictMonthlyChart, nsrdiOverdueChart].forEach(function(c) {
            if (!c) return;
            if (c.options.scales) {
                ['x','y'].forEach(function(ax) {
                    if (c.options.scales[ax]) {
                        if (c.options.scales[ax].ticks) c.options.scales[ax].ticks.color = mutedColor();
                        if (c.options.scales[ax].grid)  c.options.scales[ax].grid.color  = gridColor();
                    }
                });
            }
            if (c.options.plugins) {
                if (c.options.plugins.tooltip) {
                    c.options.plugins.tooltip.backgroundColor = tooltipBg();
                    c.options.plugins.tooltip.titleColor = textColor();
                    c.options.plugins.tooltip.bodyColor  = mutedColor();
                }
                if (c.options.plugins.legend && c.options.plugins.legend.labels)
                    c.options.plugins.legend.labels.color = mutedColor();
            }
            c.update();
        });
        donutChart.data.datasets[0].borderColor = isLight ? '#eef2ff' : '#0d1117';
        donutChart.update();
    };
    window.cbmCharts = [barChart, lineChart, donutChart, ictDailyChart, ictMonthlyChart, nsrdiOverdueChart];
}

document.addEventListener('DOMContentLoaded', function() { requestAnimationFrame(cbmInitCharts); });
document.addEventListener('livewire:navigated', function() { requestAnimationFrame(cbmInitCharts); });
</script>

</div>

