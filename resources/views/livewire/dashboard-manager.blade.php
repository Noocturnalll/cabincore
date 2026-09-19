<div>
<style>
/* ═══ Dashboard — Enterprise Colorful Edition ═══ */

/* ── Page header ── */
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

/* ── Stat Cards Grid ── */
.cbm-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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

/* ── KPI Row ── */
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

/* ── Charts Row ── */
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

/* ── Donut ── */
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

/* ── Legend chips ── */
.cbm-legend { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.cbm-legend-item { display: flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); }
.cbm-legend-dot { width: .5rem; height: .5rem; border-radius: 50%; }

/* ── Table at bottom ── */
.cbm-bottom-table {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: var(--cbm-card-shadow);
    overflow: hidden;
}

/* ── Responsive: main page wrapper ── */
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
@endphp

{{-- ════ PAGE HEADER ════ --}}
<div class="cbm-page-header">
    <div class="cbm-greeting">
        Selamat datang, <span>{{ explode(' ', auth()->user()->name)[0] }}</span> 👋
    </div>
    <div class="cbm-greeting-sub">Monitor seluruh operasional cabin maintenance — {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</div>
</div>

{{-- ════ TOP 4 STAT CARDS ════ --}}
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

</div>

{{-- ════ KPI ROW ════ --}}
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

{{-- ════ CHARTS ROW ════ --}}
@php
    $stationStats = $stats['stationStats'] ?? [];
    $stationLabels  = array_keys($stationStats);
    $stationWo      = array_map(function($s) { return $s['details']['wo']['closed'] + $s['details']['wo']['open']; }, array_values($stationStats));
    $stationDmi     = array_map(function($s) { return $s['details']['dmi']['closed'] + $s['details']['dmi']['open']; }, array_values($stationStats));
    $stationNsrdi   = array_map(function($s) { return $s['details']['nsrdi']['closed'] + $s['details']['nsrdi']['open']; }, array_values($stationStats));
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

    {{-- Chart 1: Bar Chart — DJA per Station --}}
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

    {{-- Chart 2: Line Chart --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Grafik Penyelesaian</div>
                <div class="cbm-card-sub">Pekerjaan harian — 7 hari terakhir</div>
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

{{-- ════ BOTTOM TABLE ════ --}}
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var isLight = document.getElementById('cbm-html').classList.contains('cbm-light');
var barChart, lineChart, donutChart;

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

    /* ─── BAR CHART ─── */
    var barEl = document.getElementById('cbm-bar-chart');
    if (!barEl) return;

    if (barChart) { barChart.destroy(); barChart = null; }
    if (lineChart) { lineChart.destroy(); lineChart = null; }
    if (donutChart) { donutChart.destroy(); donutChart = null; }

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

    /* ─── LINE CHART ─── */
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

    /* ─── DONUT CHART ─── */
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

    /* ─── THEME UPDATER ─── */
    window.cbmUpdateChartTheme = function(theme) {
        isLight = (theme === 'light');
        [barChart, lineChart, donutChart].forEach(function(c) {
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
    window.cbmCharts = [barChart, lineChart, donutChart];
}

document.addEventListener('DOMContentLoaded', function() { requestAnimationFrame(cbmInitCharts); });
document.addEventListener('livewire:navigated', function() { requestAnimationFrame(cbmInitCharts); });
</script>

</div>