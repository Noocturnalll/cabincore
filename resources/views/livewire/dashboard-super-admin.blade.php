<div>
<style>
/* ═══ Dashboard ─ Enterprise Edition ═══ */

/* ── Page header ── */
.cbm-page-header { margin-bottom: 1.75rem; }
.cbm-greeting {
    font-size: clamp(1.4rem, 3vw, 2rem);
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
    font-size: .9rem;
    color: var(--cbm-text-muted);
    margin-top: .35rem;
    font-weight: 500;
}

/* ── Section title ── */
.cbm-section-title {
    font-size: .75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--cbm-text-muted);
    margin: 1.75rem 0 1rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.cbm-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--cbm-card-border);
}

/* ── Stats Grid ── */
.cbm-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
@media (max-width: 1400px) { .cbm-stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .cbm-stats-grid { grid-template-columns: 1fr; } }

.cbm-stats-grid-6 {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
@media (max-width: 1400px) { .cbm-stats-grid-6 { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .cbm-stats-grid-6 { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .cbm-stats-grid-6 { grid-template-columns: 1fr; } }

/* ── Stat Cards ── */
.cbm-stat-card {
    position: relative;
    border-radius: 1.125rem;
    padding: 1.25rem 1.125rem 1rem;
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
    border: 1px solid transparent;
}
.cbm-stat-card:hover { transform: translateY(-3px); }

.cbm-sc-blue   { background: linear-gradient(145deg, rgba(59,130,246,.18) 0%, rgba(99,102,241,.10) 100%); border-color: rgba(59,130,246,.25); box-shadow: 0 4px 24px rgba(59,130,246,.18); }
.cbm-sc-green  { background: linear-gradient(145deg, rgba(34,197,94,.16) 0%, rgba(16,185,129,.08) 100%); border-color: rgba(34,197,94,.25); box-shadow: 0 4px 24px rgba(34,197,94,.18); }
.cbm-sc-orange { background: linear-gradient(145deg, rgba(251,146,60,.16) 0%, rgba(245,158,11,.08) 100%); border-color: rgba(251,146,60,.25); box-shadow: 0 4px 24px rgba(251,146,60,.18); }
.cbm-sc-purple { background: linear-gradient(145deg, rgba(168,85,247,.16) 0%, rgba(236,72,153,.08) 100%); border-color: rgba(168,85,247,.25); box-shadow: 0 4px 24px rgba(168,85,247,.18); }
.cbm-sc-cyan   { background: linear-gradient(145deg, rgba(6,182,212,.16) 0%, rgba(14,165,233,.08) 100%); border-color: rgba(6,182,212,.25); box-shadow: 0 4px 24px rgba(6,182,212,.18); }
.cbm-sc-rose   { background: linear-gradient(145deg, rgba(244,63,94,.16) 0%, rgba(220,38,127,.08) 100%); border-color: rgba(244,63,94,.25); box-shadow: 0 4px 24px rgba(244,63,94,.18); }
.cbm-sc-teal   { background: linear-gradient(145deg, rgba(20,184,166,.16) 0%, rgba(16,185,129,.08) 100%); border-color: rgba(20,184,166,.25); box-shadow: 0 4px 24px rgba(20,184,166,.18); }
.cbm-sc-indigo { background: linear-gradient(145deg, rgba(99,102,241,.16) 0%, rgba(139,92,246,.08) 100%); border-color: rgba(99,102,241,.25); box-shadow: 0 4px 24px rgba(99,102,241,.18); }

.cbm-sc-blue::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #3b82f6, #6366f1); }
.cbm-sc-green::before  { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #22c55e, #10b981); }
.cbm-sc-orange::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #fb923c, #f59e0b); }
.cbm-sc-purple::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #a855f7, #ec4899); }
.cbm-sc-cyan::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #06b6d4, #0ea5e9); }
.cbm-sc-rose::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #f43f5e, #ec4899); }
.cbm-sc-teal::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #14b8a6, #10b981); }
.cbm-sc-indigo::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #6366f1, #8b5cf6); }

.cbm-light .cbm-sc-blue   { background: linear-gradient(145deg, rgba(59,130,246,.09) 0%, rgba(99,102,241,.05) 100%); box-shadow: 0 4px 20px rgba(59,130,246,.15); }
.cbm-light .cbm-sc-green  { background: linear-gradient(145deg, rgba(34,197,94,.09) 0%, rgba(16,185,129,.04) 100%); box-shadow: 0 4px 20px rgba(34,197,94,.15); }
.cbm-light .cbm-sc-orange { background: linear-gradient(145deg, rgba(251,146,60,.09) 0%, rgba(245,158,11,.04) 100%); box-shadow: 0 4px 20px rgba(251,146,60,.15); }
.cbm-light .cbm-sc-purple { background: linear-gradient(145deg, rgba(168,85,247,.09) 0%, rgba(236,72,153,.04) 100%); box-shadow: 0 4px 20px rgba(168,85,247,.15); }
.cbm-light .cbm-sc-cyan   { background: linear-gradient(145deg, rgba(6,182,212,.09) 0%, rgba(14,165,233,.04) 100%); box-shadow: 0 4px 20px rgba(6,182,212,.15); }
.cbm-light .cbm-sc-rose   { background: linear-gradient(145deg, rgba(244,63,94,.09) 0%, rgba(220,38,127,.04) 100%); box-shadow: 0 4px 20px rgba(244,63,94,.15); }
.cbm-light .cbm-sc-teal   { background: linear-gradient(145deg, rgba(20,184,166,.09) 0%, rgba(16,185,129,.04) 100%); box-shadow: 0 4px 20px rgba(20,184,166,.15); }
.cbm-light .cbm-sc-indigo { background: linear-gradient(145deg, rgba(99,102,241,.09) 0%, rgba(139,92,246,.04) 100%); box-shadow: 0 4px 20px rgba(99,102,241,.15); }

.cbm-stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .75rem; }
.cbm-stat-label { font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); letter-spacing: .01em; }
.cbm-stat-icon {
    width: 2.5rem; height: 2.5rem;
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.cbm-stat-icon svg { width: 1.25rem; height: 1.25rem; color: #fff; }
.cbm-si-blue   { background: linear-gradient(135deg,#3b82f6,#6366f1); box-shadow: 0 6px 16px rgba(59,130,246,.4); }
.cbm-si-green  { background: linear-gradient(135deg,#22c55e,#10b981); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
.cbm-si-orange { background: linear-gradient(135deg,#fb923c,#f59e0b); box-shadow: 0 6px 16px rgba(251,146,60,.4); }
.cbm-si-purple { background: linear-gradient(135deg,#a855f7,#ec4899); box-shadow: 0 6px 16px rgba(168,85,247,.4); }
.cbm-si-cyan   { background: linear-gradient(135deg,#06b6d4,#0ea5e9); box-shadow: 0 6px 16px rgba(6,182,212,.4); }
.cbm-si-rose   { background: linear-gradient(135deg,#f43f5e,#ec4899); box-shadow: 0 6px 16px rgba(244,63,94,.4); }
.cbm-si-teal   { background: linear-gradient(135deg,#14b8a6,#10b981); box-shadow: 0 6px 16px rgba(20,184,166,.4); }
.cbm-si-indigo { background: linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow: 0 6px 16px rgba(99,102,241,.4); }

.cbm-stat-value { font-size: 2rem; font-weight: 800; color: var(--cbm-text); line-height: 1; margin-bottom: .5rem; letter-spacing: -.02em; }
.cbm-stat-sub-row { display: flex; gap: .4rem; flex-wrap: wrap; }
.cbm-stat-chip {
    font-size: .65rem; font-weight: 700;
    background: rgba(255,255,255,.08);
    color: var(--cbm-text-muted);
    padding: .175rem .45rem; border-radius: 999px;
    border: 1px solid rgba(255,255,255,.1);
}
.cbm-light .cbm-stat-chip { background: rgba(0,0,0,.05); border-color: rgba(0,0,0,.08); }

/* ── Progress bar inside card ── */
.cbm-progress-bar-wrap { margin-top: .625rem; }
.cbm-progress-label { display: flex; justify-content: space-between; font-size: .7rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: .3rem; }
.cbm-progress-track { height: 5px; border-radius: 999px; background: rgba(255,255,255,.1); overflow: hidden; }
.cbm-light .cbm-progress-track { background: rgba(0,0,0,.08); }
.cbm-progress-fill { height: 100%; border-radius: 999px; transition: width .6s ease; }

/* ── KPI Row ── */
.cbm-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 1200px) { .cbm-kpi-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px)  { .cbm-kpi-row { grid-template-columns: 1fr; } }

.cbm-kpi-card {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1rem;
    padding: .875rem 1.125rem;
    display: flex;
    align-items: center;
    gap: .875rem;
    box-shadow: var(--cbm-card-shadow);
    transition: transform .2s ease, box-shadow .2s ease;
}
.cbm-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.2); }
.cbm-kpi-icon { width: 2.5rem; height: 2.5rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cbm-kpi-icon svg { width: 1.25rem; height: 1.25rem; color: #fff; }
.cbm-kpi-label { font-size: .65rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .06em; }
.cbm-kpi-value { font-size: 1.25rem; font-weight: 800; color: var(--cbm-text); margin-top: .1rem; letter-spacing: -.015em; }
.cbm-kpi-sub   { font-size: .7rem; color: var(--cbm-text-muted); margin-top: .15rem; font-weight: 500; }

/* ── Charts Row ── */
.cbm-charts-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 1280px) { .cbm-charts-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px)  { .cbm-charts-row { grid-template-columns: 1fr; } }

.cbm-chart-card {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.125rem;
    padding: 1.375rem;
    display: flex;
    flex-direction: column;
    box-shadow: var(--cbm-card-shadow);
    transition: box-shadow .2s ease;
}
.cbm-chart-card:hover { box-shadow: 0 12px 36px rgba(0,0,0,.25); }

.cbm-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.125rem; }
.cbm-card-title { font-size: .9375rem; font-weight: 800; color: var(--cbm-text); letter-spacing: -.01em; }
.cbm-card-sub   { font-size: .775rem; color: var(--cbm-text-muted); margin-top: .2rem; font-weight: 500; }
.cbm-chart-container { flex: 1; min-height: 210px; position: relative; }

/* ── Donut ── */
.cbm-donut-wrap { position: relative; display: flex; flex-direction: column; align-items: center; }
.cbm-donut-center {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    text-align: center; pointer-events: none;
}
.cbm-donut-center-value { font-size: 1.5rem; font-weight: 800; color: var(--cbm-text); line-height: 1; }
.cbm-donut-center-label { font-size: .65rem; color: var(--cbm-text-muted); font-weight: 600; margin-top: .2rem; }
.cbm-donut-legend { display: flex; flex-direction: column; gap: .45rem; margin-top: .875rem; width: 100%; }
.cbm-donut-legend-item { display: flex; align-items: center; justify-content: space-between; font-size: .775rem; font-weight: 600; }
.cbm-dli-left  { display: flex; align-items: center; gap: .45rem; color: var(--cbm-text); }
.cbm-dli-dot   { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.cbm-dli-val   { font-weight: 800; color: var(--cbm-text); }
.cbm-dli-detail{ font-size: .675rem; color: var(--cbm-text-muted); padding-left: 1.125rem; margin-top: .1rem; }

/* ── Legend ── */
.cbm-legend { display: flex; align-items: center; gap: .625rem; flex-wrap: wrap; }
.cbm-legend-item { display: flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; color: var(--cbm-text-muted); }
.cbm-legend-dot { width: .45rem; height: .45rem; border-radius: 50%; }

/* ── AC Type Cards ── */
.cbm-ac-type-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
@media (max-width: 1100px) { .cbm-ac-type-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .cbm-ac-type-grid { grid-template-columns: 1fr; } }

.cbm-ac-card {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1rem;
    padding: 1.125rem;
    box-shadow: var(--cbm-card-shadow);
    transition: transform .2s ease;
}
.cbm-ac-card:hover { transform: translateY(-2px); }
.cbm-ac-card-header { display: flex; align-items: center; gap: .625rem; margin-bottom: .875rem; }
.cbm-ac-type-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .625rem; border-radius: .5rem;
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
}
.cbm-ac-stat-row { display: flex; justify-content: space-between; align-items: center; }
.cbm-ac-big-num { font-size: 2.25rem; font-weight: 800; color: var(--cbm-text); letter-spacing: -.02em; line-height: 1; }
.cbm-ac-sub-col { display: flex; flex-direction: column; gap: .2rem; align-items: flex-end; }
.cbm-ac-sub-item { font-size: .7rem; font-weight: 700; }

/* ── Bottom Table ── */
.cbm-bottom-table {
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.125rem;
    padding: 1.375rem;
    box-shadow: var(--cbm-card-shadow);
    overflow: hidden;
}

/* ── Badge ── */
.cbm-badge-open   { background: rgba(248,113,113,.15); color: #ef4444; padding: .3rem .625rem; border-radius: .375rem; font-weight: 600; font-size: .7rem; }
.cbm-badge-closed { background: rgba(52,211,153,.15); color: #10b981; padding: .3rem .625rem; border-radius: .375rem; font-weight: 600; font-size: .7rem; }
</style>

@php
    $dja        = $stats['dja']       ?? [];
    $unplanned  = $stats['unplanned'] ?? [];
    $cmlData    = $stats['cml']       ?? [];
    $ictData    = $stats['ict']       ?? [];
    $acData     = $stats['ac']        ?? [];

    $djaTotal        = $dja['total']      ?? 0;
    $djaClosed       = $dja['closed']     ?? 0;
    $djaOpen         = $dja['open']       ?? 0;
    $djaCloseRate    = $dja['close_rate'] ?? 0;

    $cmlClosed       = $stats['cml_closed']   ?? 0;
    $unplannedTotal  = $unplanned['total']     ?? 0;
    $unplannedClosed = $unplanned['closed']    ?? 0;
    $unplannedOpen   = $unplanned['open']      ?? 0;
    $unplannedRate   = $unplanned['all_rate']  ?? 0;

    $acTotal      = $acData['total']       ?? 0;
    $acClosed     = $acData['closed']      ?? 0;
    $acOpen       = $acData['open']        ?? 0;
    $acCloseRate  = $acData['close_rate']  ?? 0;

    $targetDate = $stats['target_date'] ?? now()->format('Y-m-d');
    $targetDateFormatted = \Carbon\Carbon::parse($targetDate)->locale('id')->isoFormat('dddd, D MMMM YYYY');
@endphp

{{-- ════ PAGE HEADER ════ --}}
<div class="cbm-page-header">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
        <div>
            <div class="cbm-greeting">
                Selamat datang, <span>{{ explode(' ', auth()->user()->name)[0] }}</span> 👋
            </div>
            <div class="cbm-greeting-sub">Monitor operasional cabin maintenance harian Anda</div>
        </div>
        <div style="display:inline-flex;align-items:center;gap:.5rem;background:var(--cbm-nav-active);border:1px solid var(--cbm-card-border);border-radius:.875rem;padding:.5rem 1rem;font-size:.8rem;font-weight:700;color:var(--cbm-nav-active-t);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
            Data: {{ $targetDateFormatted }}
        </div>
    </div>
</div>

{{-- ════ MAN POWER & MAN HOURS ════ --}}
<div class="cbm-kpi-row" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 1rem;">
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(59,130,246,.06) 0%, rgba(99,102,241,.03) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1);box-shadow:0 6px 16px rgba(59,130,246,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Man Power (PIC)</div>
            <div class="cbm-kpi-value">{{ $stats['man_power'] ?? 0 }} <span style="font-size:.875rem;color:var(--cbm-text-muted);">org</span></div>
            <div class="cbm-kpi-sub">Total teknisi / PIC aktif</div>
        </div>
    </div>
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(168,85,247,.06) 0%, rgba(236,72,153,.03) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#a855f7,#ec4899);box-shadow:0 6px 16px rgba(168,85,247,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Man Hours (WO)</div>
            <div class="cbm-kpi-value">{{ $stats['man_hours'] ?? 0 }} <span style="font-size:.875rem;color:var(--cbm-text-muted);">jam</span></div>
            <div class="cbm-kpi-sub">Total estimasi man hour WO</div>
        </div>
    </div>
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(34,197,94,.06) 0%, rgba(16,185,129,.03) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#22c55e,#10b981);box-shadow:0 6px 16px rgba(34,197,94,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">DJA Close Rate</div>
            <div class="cbm-kpi-value">{{ $djaCloseRate }}<span style="font-size:.875rem;font-weight:600;color:var(--cbm-text-muted);">%</span></div>
            <div class="cbm-kpi-sub">Planned task selesai</div>
        </div>
    </div>
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(251,146,60,.06) 0%, rgba(245,158,11,.03) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#fb923c,#f59e0b);box-shadow:0 6px 16px rgba(251,146,60,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned Close Rate</div>
            <div class="cbm-kpi-value">{{ $unplannedRate }}<span style="font-size:.875rem;font-weight:600;color:var(--cbm-text-muted);">%</span></div>
            <div class="cbm-kpi-sub">Unplanned task selesai</div>
        </div>
    </div>
</div>

{{-- ════ SECTION: DJA & UNPLANNED ════ --}}
<div class="cbm-section-title">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 16.352V17.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd"/></svg>
    DJA & Unplanned
</div>

<div class="cbm-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    {{-- DJA Total --}}
    <div class="cbm-stat-card cbm-sc-blue">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Total Laporan DJA</div>
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
        <div class="cbm-progress-bar-wrap">
            <div class="cbm-progress-label"><span>Close Rate</span><span>{{ $djaCloseRate }}%</span></div>
            <div class="cbm-progress-track"><div class="cbm-progress-fill" style="width:{{ $djaCloseRate }}%;background:linear-gradient(90deg,#3b82f6,#6366f1);"></div></div>
        </div>
    </div>

    {{-- DJA Closed --}}
    <div class="cbm-stat-card cbm-sc-green">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">DJA Closed</div>
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

    {{-- DJA Open --}}
    <div class="cbm-stat-card cbm-sc-orange">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">DJA Open</div>
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
</div>

{{-- Unplanned row --}}
<div class="cbm-kpi-row" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 0.5rem;">
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#06b6d4,#0ea5e9);box-shadow:0 6px 16px rgba(6,182,212,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned WO</div>
            <div class="cbm-kpi-value">{{ $unplanned['wo_total'] ?? 0 }}</div>
            <div class="cbm-kpi-sub">Closed: {{ $unplanned['wo_closed'] ?? 0 }} | Open: {{ $unplanned['wo_open'] ?? 0 }}</div>
        </div>
    </div>
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#f87171,#dc2626);box-shadow:0 6px 16px rgba(248,113,113,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned DMI</div>
            <div class="cbm-kpi-value">{{ $unplanned['dmi_total'] ?? 0 }}</div>
            <div class="cbm-kpi-sub">Closed: {{ $unplanned['dmi_closed'] ?? 0 }} | Open: {{ $unplanned['dmi_open'] ?? 0 }}</div>
        </div>
    </div>
    <div class="cbm-kpi-card">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 6px 16px rgba(99,102,241,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6zM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Unplanned NSRDI</div>
            <div class="cbm-kpi-value">{{ $unplanned['nsrdi_total'] ?? 0 }}</div>
            <div class="cbm-kpi-sub">Closed: {{ $unplanned['nsrdi_closed'] ?? 0 }} | Open: {{ $unplanned['nsrdi_open'] ?? 0 }}</div>
        </div>
    </div>
</div>

{{-- ════ SECTION: CML & ICT ════ --}}
<div class="cbm-section-title">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
    CML & ICT Findings
</div>

<div class="cbm-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    {{-- CML --}}
    <div class="cbm-stat-card cbm-sc-purple">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">Total CML</div>
            <div class="cbm-stat-icon cbm-si-purple">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 116 0h3a.75.75 0 00.75-.75V15z"/><path d="M8.25 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM15.75 6.75a.75.75 0 00-.75.75v11.25c0 .087.015.17.042.248a3 3 0 015.958.464c.853-.175 1.522-.935 1.464-1.883a18.845 18.845 0 00-3.414-9.787 2.037 2.037 0 00-1.6-.773H15.75z"/><path d="M19.5 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z"/></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $cmlData['total'] ?? 0 }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">Closed: {{ $cmlData['closed'] ?? 0 }}</span>
            <span class="cbm-stat-chip">Open: {{ $cmlData['open'] ?? 0 }}</span>
        </div>
    </div>

    {{-- ICT Total --}}
    <div class="cbm-stat-card cbm-sc-rose">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">ICT Findings Hari Ini</div>
            <div class="cbm-stat-icon cbm-si-rose">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" /><path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" /></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $ictData['total'] ?? 0 }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">Open: {{ $ictData['open'] ?? 0 }}</span>
            <span class="cbm-stat-chip">Closed: {{ $ictData['closed'] ?? 0 }}</span>
        </div>
    </div>

    {{-- ICT Overall Open --}}
    <div class="cbm-stat-card cbm-sc-indigo">
        <div class="cbm-stat-top">
            <div class="cbm-stat-label">ICT Open (All-time)</div>
            <div class="cbm-stat-icon cbm-si-indigo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/></svg>
            </div>
        </div>
        <div class="cbm-stat-value">{{ $stats['overall']['ict_open'] ?? 0 }}</div>
        <div class="cbm-stat-sub-row">
            <span class="cbm-stat-chip">Total: {{ $stats['overall']['ict'] ?? 0 }}</span>
        </div>
    </div>
</div>

{{-- ════ SECTION: AIRCRAFT CLEANING ════ --}}
<div class="cbm-section-title">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
    Aircraft Cleaning
</div>

{{-- AC Summary Card --}}
<div class="cbm-stats-grid" style="grid-template-columns: 1fr; margin-bottom: .75rem;">
    <div class="cbm-stat-card cbm-sc-teal" style="padding: 1.25rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap: 1rem;">
            <div style="display:flex; align-items:center; gap: 1.25rem;">
                <div class="cbm-stat-icon cbm-si-teal" style="width:3rem;height:3rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.5rem;height:1.5rem;"><path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <div style="font-size:.75rem;font-weight:700;color:var(--cbm-text-muted);text-transform:uppercase;letter-spacing:.07em;">Aircraft Cleaning Total Hari Ini</div>
                    <div style="font-size:2.5rem;font-weight:800;color:var(--cbm-text);line-height:1;letter-spacing:-.02em;">{{ $acData['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- AC Type Cards --}}
<div class="cbm-ac-type-grid">
    {{-- General Cleaning --}}
    <div class="cbm-ac-card">
        <div class="cbm-ac-card-header">
            <div class="cbm-ac-type-badge" style="background:rgba(34,197,94,.15);color:#22c55e;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px;"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"/></svg>
                GC
            </div>
            <span style="font-size:.75rem;color:var(--cbm-text-muted);font-weight:600;">General Cleaning</span>
        </div>
        <div class="cbm-ac-stat-row">
            <div class="cbm-ac-big-num">{{ $acData['gc_total'] ?? 0 }}</div>
        </div>
    </div>

    {{-- DCI --}}
    <div class="cbm-ac-card">
        <div class="cbm-ac-card-header">
            <div class="cbm-ac-type-badge" style="background:rgba(6,182,212,.15);color:#06b6d4;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px;"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                DCI
            </div>
            <span style="font-size:.75rem;color:var(--cbm-text-muted);font-weight:600;">Deep Cleaning Interior</span>
        </div>
        <div class="cbm-ac-stat-row">
            <div class="cbm-ac-big-num">{{ $acData['dci_total'] ?? 0 }}</div>
        </div>
    </div>

    {{-- DCE --}}
    <div class="cbm-ac-card">
        <div class="cbm-ac-card-header">
            <div class="cbm-ac-type-badge" style="background:rgba(168,85,247,.15);color:#a855f7;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px;"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                DCE
            </div>
            <span style="font-size:.75rem;color:var(--cbm-text-muted);font-weight:600;">Deep Cleaning Exterior</span>
        </div>
        <div class="cbm-ac-stat-row">
            <div class="cbm-ac-big-num">{{ $acData['dce_total'] ?? 0 }}</div>
        </div>
    </div>

    {{-- Transit Cleaning --}}
    <div class="cbm-ac-card">
        <div class="cbm-ac-card-header">
            <div class="cbm-ac-type-badge" style="background:rgba(251,146,60,.15);color:#fb923c;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px;"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                TC
            </div>
            <span style="font-size:.75rem;color:var(--cbm-text-muted);font-weight:600;">Transit Cleaning</span>
        </div>
        <div class="cbm-ac-stat-row">
            <div class="cbm-ac-big-num">{{ $acData['tc_total'] ?? 0 }}</div>
        </div>
    </div>
</div>

{{-- ════ CHARTS ROW 1: Station Charts ════ --}}
<div class="cbm-section-title">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path d="M15.98 1.804a1 1 0 00-1.96 0l-.24 1.192a1 1 0 01-.784.785l-1.192.24a1 1 0 000 1.962l1.192.24a1 1 0 01.785.785l.24 1.192a1 1 0 001.962 0l.24-1.192a1 1 0 01.785-.785l1.192-.24a1 1 0 000-1.962l-1.192-.24a1 1 0 01-.785-.785l-.24-1.192zM6.949 5.684a1 1 0 00-1.898 0l-.683 2.051a1 1 0 01-.633.633l-2.051.683a1 1 0 000 1.898l2.051.683a1 1 0 01.633.633l.683 2.051a1 1 0 001.898 0l.683-2.051a1 1 0 01.633-.633l2.051-.683a1 1 0 000-1.898l-2.051-.683a1 1 0 01-.633-.633L6.95 5.684z"/></svg>
    Analitik & Grafik
</div>

@php
    $stationStats   = $stats['stationStats']   ?? [];
    $stationLabels  = array_keys($stationStats);
    $stationWo      = array_map(fn($s) => ($s['details']['wo']['closed'] ?? 0) + ($s['details']['wo']['open'] ?? 0), array_values($stationStats));
    $stationDmi     = array_map(fn($s) => ($s['details']['dmi']['closed'] ?? 0) + ($s['details']['dmi']['open'] ?? 0), array_values($stationStats));
    $stationNsrdi   = array_map(fn($s) => ($s['details']['nsrdi']['closed'] ?? 0) + ($s['details']['nsrdi']['open'] ?? 0), array_values($stationStats));
    $stationGc      = array_map(fn($s) => ($s['details']['ac_gc']['closed'] ?? 0) + ($s['details']['ac_gc']['open'] ?? 0), array_values($stationStats));
    $stationDci     = array_map(fn($s) => ($s['details']['ac_dci']['closed'] ?? 0) + ($s['details']['ac_dci']['open'] ?? 0), array_values($stationStats));
    $stationDce     = array_map(fn($s) => ($s['details']['ac_dce']['closed'] ?? 0) + ($s['details']['ac_dce']['open'] ?? 0), array_values($stationStats));
    $stationTc      = array_map(fn($s) => ($s['details']['ac_tc']['closed'] ?? 0) + ($s['details']['ac_tc']['open'] ?? 0), array_values($stationStats));
    $stationDetails = array_column(array_values($stationStats), 'details');

    $trendLabels    = $stats['trendLabels']    ?? [];
    $trendCmlClosed = $stats['trendCmlClosed'] ?? [];
    $trendAcTotal   = $stats['trendAcTotal']   ?? [];
    $trendDja       = $stats['trendDja']       ?? [];
    $trendUnplanned = $stats['trendUnplanned'] ?? [];

    $dClosedTotal = $dja['closed']       ?? 0;
    $dOpenTotal   = $dja['open']         ?? 0;
    $uTotal       = ($unplanned['wo_total'] ?? 0) + ($unplanned['dmi_total'] ?? 0) + ($unplanned['nsrdi_total'] ?? 0);
    $totalKeseluruhan = $dClosedTotal + $dOpenTotal + $uTotal;
@endphp

<div class="cbm-charts-row" style="grid-template-columns: 1.6fr 1fr;">
    {{-- Bar Chart: DJA per Station --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">DJA per Stasiun</div>
                <div class="cbm-card-sub">WO, DMI, NSRDI per bandara hari ini</div>
            </div>
            <div class="cbm-legend">
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#3b82f6;"></div>WO</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#f59e0b;"></div>DMI</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#a855f7;"></div>NSRDI</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="cbm-bar-chart" height="210"
                data-labels='@json($stationLabels)'
                data-wo='@json($stationWo)'
                data-dmi='@json($stationDmi)'
                data-nsrdi='@json($stationNsrdi)'
                data-details='@json($stationDetails)'>
            </canvas>
        </div>
    </div>

    {{-- Donut: Status Keseluruhan --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Status Keseluruhan</div>
                <div class="cbm-card-sub">DJA Closed, Open & Unplanned</div>
            </div>
        </div>
        <div class="cbm-donut-wrap" wire:ignore>
            <div style="position:relative;width:150px;height:150px;margin:0 auto;">
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
            <div>
                <div class="cbm-donut-legend-item">
                    <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#34d399;"></span>DJA Closed</span>
                    <span class="cbm-dli-val">{{ $dClosedTotal }}</span>
                </div>
            </div>
            <div>
                <div class="cbm-donut-legend-item">
                    <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#f87171;"></span>DJA Open</span>
                    <span class="cbm-dli-val">{{ $dOpenTotal }}</span>
                </div>
            </div>
            <div>
                <div class="cbm-donut-legend-item">
                    <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#818cf8;"></span>Unplanned</span>
                    <span class="cbm-dli-val">{{ $uTotal }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row 2: Trend + AC per Station --}}
<div class="cbm-charts-row" style="grid-template-columns: 1fr 1fr;">
    {{-- Line Chart: 7 Hari Trend --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Grafik Penyelesaian 7 Hari</div>
                <div class="cbm-card-sub">CML, DJA, Unplanned & AC</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="cbm-line-chart" height="210"></canvas>
        </div>
    </div>

    {{-- AC Bar Chart per Station --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Aircraft Cleaning per Stasiun</div>
                <div class="cbm-card-sub">GC, DCI, DCE, TC per bandara</div>
            </div>
            <div class="cbm-legend">
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#22c55e;"></div>GC</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#06b6d4;"></div>DCI</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#a855f7;"></div>DCE</div>
                <div class="cbm-legend-item"><div class="cbm-legend-dot" style="background:#fb923c;"></div>TC</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="ac-bar-chart" height="210"
                data-labels='@json($stationLabels)'
                data-gc='@json($stationGc)'
                data-dci='@json($stationDci)'
                data-dce='@json($stationDce)'
                data-tc='@json($stationTc)'
                data-details='@json($stationDetails)'>
            </canvas>
        </div>
    </div>
</div>

{{-- Charts Row 3: ICT + AC by Operator --}}
<div class="cbm-charts-row" style="grid-template-columns: repeat(2, 1fr);">
    {{-- ICT Daily --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">ICT Harian per Maskapai</div>
                <div class="cbm-card-sub">Data {{ $targetDateFormatted }}: Open vs Closed</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="ict-daily-chart" height="210"
                data-labels='@json($stats["ict"]["charts"]["daily"]["labels"] ?? [])'
                data-open='@json($stats["ict"]["charts"]["daily"]["open"] ?? [])'
                data-closed='@json($stats["ict"]["charts"]["daily"]["closed"] ?? [])'>
            </canvas>
        </div>
    </div>

    {{-- AC Composition donut --}}
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">Komposisi Aircraft Cleaning</div>
                <div class="cbm-card-sub">Proporsi tiap tipe cleaning hari ini</div>
            </div>
        </div>
        <div class="cbm-donut-wrap" wire:ignore style="margin-top:.5rem;">
            <div style="position:relative;width:150px;height:150px;margin:0 auto;">
                <canvas id="ac-type-donut"
                    data-gc="{{ $acData['gc_total'] ?? 0 }}"
                    data-dci="{{ $acData['dci_total'] ?? 0 }}"
                    data-dce="{{ $acData['dce_total'] ?? 0 }}"
                    data-tc="{{ $acData['tc_total'] ?? 0 }}"
                    data-total="{{ $acData['total'] ?? 0 }}">
                </canvas>
                <div class="cbm-donut-center">
                    <div class="cbm-donut-center-value">{{ $acData['total'] ?? 0 }}</div>
                    <div class="cbm-donut-center-label">Total</div>
                </div>
            </div>
        </div>
        <div class="cbm-donut-legend">
            <div class="cbm-donut-legend-item">
                <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#22c55e;"></span>General Cleaning</span>
                <span class="cbm-dli-val">{{ $acData['gc_total'] ?? 0 }}</span>
            </div>
            <div class="cbm-donut-legend-item">
                <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#06b6d4;"></span>Deep Cleaning Interior</span>
                <span class="cbm-dli-val">{{ $acData['dci_total'] ?? 0 }}</span>
            </div>
            <div class="cbm-donut-legend-item">
                <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#a855f7;"></span>Deep Cleaning Exterior</span>
                <span class="cbm-dli-val">{{ $acData['dce_total'] ?? 0 }}</span>
            </div>
            <div class="cbm-donut-legend-item">
                <span class="cbm-dli-left"><span class="cbm-dli-dot" style="background:#fb923c;"></span>Transit Cleaning</span>
                <span class="cbm-dli-val">{{ $acData['tc_total'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ICT Monthly & NSRDI Overdue --}}
<div class="cbm-charts-row" style="grid-template-columns: repeat(2, 1fr);">
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">ICT Bulanan per Maskapai</div>
                <div class="cbm-card-sub">Data bulan ini: Open vs Closed</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="ict-monthly-chart" height="210"
                data-labels='@json($stats["ict"]["charts"]["monthly"]["labels"] ?? [])'
                data-open='@json($stats["ict"]["charts"]["monthly"]["open"] ?? [])'
                data-closed='@json($stats["ict"]["charts"]["monthly"]["closed"] ?? [])'>
            </canvas>
        </div>
    </div>
    <div class="cbm-chart-card">
        <div class="cbm-card-header">
            <div>
                <div class="cbm-card-title">NSRDI Overdue (Open)</div>
                <div class="cbm-card-sub">Laporan NSRDI melewati due date</div>
            </div>
        </div>
        <div class="cbm-chart-container" wire:ignore>
            <canvas id="nsrdi-overdue-chart" height="210"
                data-values='@json(array_values($stats["nsrdi_overdue"] ?? []))'
                data-labels='@json(array_keys($stats["nsrdi_overdue"] ?? []))'>
            </canvas>
        </div>
    </div>
</div>

{{-- ════ BOTTOM TABLE: Open ICT ════ --}}
<div class="cbm-bottom-table" style="margin-top: 1rem;">
    <div class="cbm-card-header" style="margin-bottom:1.125rem;">
        <div>
            <div class="cbm-card-title">Prioritas Laporan – ICT Finding Open</div>
            <div class="cbm-card-sub">ICT Finding berstatus Open yang butuh ditindaklanjuti segera.</div>
        </div>
        <a href="{{ route('modules.ict') }}" style="font-size:.75rem;font-weight:700;color:var(--cbm-blue);text-decoration:none;">Lihat Semua →</a>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;text-align:left;border-collapse:collapse;font-size:.8375rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--cbm-divider);color:var(--cbm-text-muted);font-size:.65rem;text-transform:uppercase;letter-spacing:.07em;">
                    <th style="padding:.625rem .625rem;font-weight:700;">No</th>
                    <th style="padding:.625rem .625rem;font-weight:700;">Finding / Pesawat</th>
                    <th style="padding:.625rem .625rem;font-weight:700;">Maskapai</th>
                    <th style="padding:.625rem .625rem;font-weight:700;">Tanggal</th>
                    <th style="padding:.625rem .625rem;font-weight:700;">Status</th>
                    <th style="padding:.625rem .625rem;font-weight:700;text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($openIctFindings ?? [] as $index => $finding)
                <tr style="border-bottom: 1px solid var(--cbm-border);" onmouseover="this.style.background='var(--cbm-nav-hover)'" onmouseout="this.style.background=''">
                    <td style="padding: .75rem .625rem; color: var(--cbm-text-muted);">{{ $index + 1 }}</td>
                    <td style="padding: .75rem .625rem;">
                        <div style="font-weight: 700; color: var(--cbm-blue); font-size:.8375rem;">{{ $finding->no_finding }}</div>
                        <div style="font-size: .7rem; color: var(--cbm-text-muted); margin-top: .2rem;">Reg: {{ $finding->aircraft_registration }}</div>
                    </td>
                    <td style="padding: .75rem .625rem; font-size:.8rem; color:var(--cbm-text);">{{ $finding->operator ?? '-' }}</td>
                    <td style="padding: .75rem .625rem; font-size:.8rem; color:var(--cbm-text-muted);">{{ \Carbon\Carbon::parse($finding->date)->format('d M Y') }}</td>
                    <td style="padding: .75rem .625rem;"><span class="cbm-badge-open">Open</span></td>
                    <td style="padding: .75rem .625rem; text-align: right;">
                        <a href="{{ route('modules.ict') }}" style="color: var(--cbm-blue); font-weight: 600; font-size:.8rem; text-decoration:none;">Update →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 1.5rem .625rem; text-align: center; color: var(--cbm-text-muted); font-size:.8125rem;">
                        🎉 Tidak ada ICT Finding yang berstatus Open.
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
var charts = {};

function textColor()  { return isLight ? '#0f172a' : '#f1f5f9'; }
function mutedColor() { return isLight ? '#64748b' : '#94a3b8'; }
function gridColor()  { return isLight ? 'rgba(148,163,184,.15)' : 'rgba(255,255,255,.06)'; }
function tooltipBg()  { return isLight ? '#ffffff' : '#1e293b'; }

function intTicks() {
    return { stepSize:1, precision:0, callback: function(v) { return Number.isInteger(v) ? v : null; } };
}

function destroyAll() {
    Object.values(charts).forEach(function(c) { if (c) c.destroy(); });
    charts = {};
}

function makeBar(id, labels, datasets) {
    var el = document.getElementById(id);
    if (!el) return null;
    return new Chart(el.getContext('2d'), {
        type: 'bar',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode:'index', intersect:false },
            plugins: {
                legend: { display:false },
                tooltip: { backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
            },
            scales: {
                x: { stacked:true, grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                y: { stacked:true, grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, intTicks()), border:{display:false} }
            }
        }
    });
}

function makeDonut(id, dataArr, colors, cutout) {
    var el = document.getElementById(id);
    if (!el) return null;
    return new Chart(el.getContext('2d'), {
        type: 'doughnut',
        data: {
            datasets: [{ data:dataArr, backgroundColor:colors, borderColor:isLight?'#eef2ff':'#0d1117', borderWidth:4, hoverOffset:8 }]
        },
        options: {
            responsive:true, maintainAspectRatio:true, cutout: cutout || '72%',
            plugins: {
                legend:{display:false},
                tooltip:{ backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:10 }
            }
        }
    });
}

function cbmInitCharts() {
    if (typeof Chart === 'undefined') return setTimeout(cbmInitCharts, 100);
    isLight = document.getElementById('cbm-html').classList.contains('cbm-light');
    destroyAll();

    /* ─── AC Summary Donut ─── */
    var acSumEl = document.getElementById('ac-donut-summary');
    if (acSumEl) {
        charts.acSummary = makeDonut('ac-donut-summary',
            [parseInt(acSumEl.dataset.closed||0), parseInt(acSumEl.dataset.open||0)],
            ['#34d399','#f87171'], '70%');
    }

    /* ─── DJA Bar Chart ─── */
    var barEl = document.getElementById('cbm-bar-chart');
    if (barEl) {
        var bLabels  = JSON.parse(barEl.dataset.labels  || '[]');
        var bWo      = JSON.parse(barEl.dataset.wo      || '[]');
        var bDmi     = JSON.parse(barEl.dataset.dmi     || '[]');
        var bNsrdi   = JSON.parse(barEl.dataset.nsrdi   || '[]');
        var bDetails = JSON.parse(barEl.dataset.details || '[]');

        var tooltipPlugin = {
            tooltip: {
                backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(),
                borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10,
                callbacks: {
                    afterBody: function(ctx) {
                        var idx = ctx[0].dataIndex;
                        if (bDetails && bDetails[idx]) {
                            var d = bDetails[idx];
                            return ['','Open/Closed:','  WO: ' + d.wo.open + '/' + d.wo.closed,'  DMI: ' + d.dmi.open + '/' + d.dmi.closed,'  NSRDI: ' + d.nsrdi.open + '/' + d.nsrdi.closed];
                        }
                        return [];
                    }
                }
            }
        };
        charts.bar = new Chart(barEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: bLabels,
                datasets: [
                    { label:'WO',    data:bWo,    backgroundColor:'rgba(59,130,246,.85)',  borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'DMI',   data:bDmi,   backgroundColor:'rgba(245,158,11,.85)',  borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'NSRDI', data:bNsrdi, backgroundColor:'rgba(168,85,247,.85)', borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 }
                ]
            },
            options: {
                responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
                plugins: Object.assign({legend:{display:false}}, tooltipPlugin),
                scales: {
                    x: { stacked:true, grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y: { stacked:true, grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, intTicks()), border:{display:false} }
                }
            }
        });
    }

    /* ─── AC Bar Chart ─── */
    var acBarEl = document.getElementById('ac-bar-chart');
    if (acBarEl) {
        var acLabels  = JSON.parse(acBarEl.dataset.labels || '[]');
        var acGc      = JSON.parse(acBarEl.dataset.gc    || '[]');
        var acDci     = JSON.parse(acBarEl.dataset.dci   || '[]');
        var acDce     = JSON.parse(acBarEl.dataset.dce   || '[]');
        var acTc      = JSON.parse(acBarEl.dataset.tc    || '[]');
        var acDetails = JSON.parse(acBarEl.dataset.details || '[]');

        charts.acBar = new Chart(acBarEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: acLabels,
                datasets: [
                    { label:'GC',  data:acGc,  backgroundColor:'rgba(34,197,94,.85)',  borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'DCI', data:acDci, backgroundColor:'rgba(6,182,212,.85)',  borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'DCE', data:acDce, backgroundColor:'rgba(168,85,247,.85)', borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 },
                    { label:'TC',  data:acTc,  backgroundColor:'rgba(251,146,60,.85)', borderRadius:4, barPercentage:0.65, categoryPercentage:0.75 }
                ]
            },
            options: {
                responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
                plugins: { legend:{display:false}, tooltip:{backgroundColor:tooltipBg(),titleColor:textColor(),bodyColor:mutedColor(),borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)',borderWidth:1,padding:12,cornerRadius:10} },
                scales: {
                    x: { stacked:true, grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y: { stacked:true, grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, intTicks()), border:{display:false} }
                }
            }
        });
    }

    /* ─── AC Type Donut ─── */
    var acTypeEl = document.getElementById('ac-type-donut');
    if (acTypeEl) {
        charts.acType = makeDonut('ac-type-donut',
            [parseInt(acTypeEl.dataset.gc||0), parseInt(acTypeEl.dataset.dci||0), parseInt(acTypeEl.dataset.dce||0), parseInt(acTypeEl.dataset.tc||0)],
            ['#22c55e','#06b6d4','#a855f7','#fb923c'], '70%');
    }

    /* ─── Line Chart: 7-Day Trend ─── */
    var lineEl = document.getElementById('cbm-line-chart');
    if (lineEl) {
        var lineCtx = lineEl.getContext('2d');
        var gPurple = lineCtx.createLinearGradient(0,0,0,200); gPurple.addColorStop(0,'rgba(168,85,247,.3)'); gPurple.addColorStop(1,'rgba(168,85,247,0)');
        var gBlue   = lineCtx.createLinearGradient(0,0,0,200); gBlue.addColorStop(0,'rgba(59,130,246,.3)');  gBlue.addColorStop(1,'rgba(59,130,246,0)');
        var gRed    = lineCtx.createLinearGradient(0,0,0,200); gRed.addColorStop(0,'rgba(248,113,113,.3)');  gRed.addColorStop(1,'rgba(248,113,113,0)');
        var gTeal   = lineCtx.createLinearGradient(0,0,0,200); gTeal.addColorStop(0,'rgba(20,184,166,.3)');  gTeal.addColorStop(1,'rgba(20,184,166,0)');

        charts.line = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: @json($trendLabels ?? []),
                datasets: [
                    { label:'CML Closed', data:@json($trendCmlClosed ?? []), borderColor:'#a855f7', backgroundColor:gPurple, borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#a855f7', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 },
                    { label:'Total DJA',  data:@json($trendDja ?? []),       borderColor:'#3b82f6', backgroundColor:gBlue,   borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#3b82f6', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 },
                    { label:'Unplanned',  data:@json($trendUnplanned ?? []), borderColor:'#f87171', backgroundColor:gRed,    borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#f87171', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 },
                    { label:'AC Total',   data:@json($trendAcTotal ?? []),   borderColor:'#14b8a6', backgroundColor:gTeal,   borderWidth:2.5, fill:true, tension:.4, pointRadius:4, pointBackgroundColor:'#14b8a6', pointBorderColor:isLight?'#fff':'#0d1117', pointBorderWidth:2, pointHoverRadius:6 }
                ]
            },
            options: {
                responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
                plugins: {
                    legend:{ display:true, position:'top', align:'end', labels:{color:mutedColor(),font:{weight:'700',size:11},boxWidth:10,usePointStyle:true,pointStyle:'circle'} },
                    tooltip:{ backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
                },
                scales: {
                    x:{ grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:10}}, border:{display:false} },
                    y:{ grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, intTicks()), border:{display:false}, suggestedMin:0 }
                }
            }
        });
    }

    /* ─── DJA Donut ─── */
    var donutEl = document.getElementById('cbm-donut-chart');
    if (donutEl) {
        var dClosed    = parseInt(donutEl.dataset.closed    || 0);
        var dOpen      = parseInt(donutEl.dataset.open      || 0);
        var dUnplanned = parseInt(donutEl.dataset.unplanned || 0);
        charts.donut = makeDonut('cbm-donut-chart', [dClosed, dOpen, dUnplanned], ['#34d399','#f87171','#818cf8']);
    }

    /* ─── ICT Daily Bar ─── */
    var ictDailyEl = document.getElementById('ict-daily-chart');
    if (ictDailyEl) {
        var dLabels = JSON.parse(ictDailyEl.dataset.labels || '[]');
        var dOpen   = JSON.parse(ictDailyEl.dataset.open   || '[]');
        var dClosed = JSON.parse(ictDailyEl.dataset.closed || '[]');
        charts.ictDaily = new Chart(ictDailyEl.getContext('2d'), {
            type: 'bar',
            data: { labels:dLabels, datasets: [
                { label:'Closed', data:dClosed, backgroundColor:'rgba(52,211,153,.85)', borderRadius:4, barPercentage:0.7, categoryPercentage:0.8 },
                { label:'Open',   data:dOpen,   backgroundColor:'rgba(248,113,113,.85)', borderRadius:4, barPercentage:0.7, categoryPercentage:0.8 }
            ]},
            options: {
                responsive:true, maintainAspectRatio:false,
                plugins: { legend:{display:true,position:'top',align:'end',labels:{color:mutedColor(),font:{weight:'700',size:11},usePointStyle:true,pointStyle:'circle'}}, tooltip:{backgroundColor:tooltipBg(),titleColor:textColor(),bodyColor:mutedColor(),borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)',borderWidth:1,padding:12,cornerRadius:10} },
                scales: { x:{grid:{display:false},ticks:{color:mutedColor(),font:{weight:'700',size:10}},border:{display:false}}, y:{grid:{color:gridColor()},ticks:Object.assign({color:mutedColor(),font:{weight:'700'}},intTicks()),border:{display:false}} }
            }
        });
    }

    /* ─── ICT Monthly Bar ─── */
    var ictMonthlyEl = document.getElementById('ict-monthly-chart');
    if (ictMonthlyEl) {
        var mLabels = JSON.parse(ictMonthlyEl.dataset.labels || '[]');
        var mOpen   = JSON.parse(ictMonthlyEl.dataset.open   || '[]');
        var mClosed = JSON.parse(ictMonthlyEl.dataset.closed || '[]');
        charts.ictMonthly = new Chart(ictMonthlyEl.getContext('2d'), {
            type: 'bar',
            data: { labels:mLabels, datasets: [
                { label:'Closed', data:mClosed, backgroundColor:'rgba(52,211,153,.85)', borderRadius:4, barPercentage:0.7, categoryPercentage:0.8 },
                { label:'Open',   data:mOpen,   backgroundColor:'rgba(248,113,113,.85)', borderRadius:4, barPercentage:0.7, categoryPercentage:0.8 }
            ]},
            options: {
                responsive:true, maintainAspectRatio:false,
                plugins: { legend:{display:true,position:'top',align:'end',labels:{color:mutedColor(),font:{weight:'700',size:11},usePointStyle:true,pointStyle:'circle'}}, tooltip:{backgroundColor:tooltipBg(),titleColor:textColor(),bodyColor:mutedColor(),borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)',borderWidth:1,padding:12,cornerRadius:10} },
                scales: { x:{grid:{display:false},ticks:{color:mutedColor(),font:{weight:'700',size:10}},border:{display:false}}, y:{grid:{color:gridColor()},ticks:Object.assign({color:mutedColor(),font:{weight:'700'}},intTicks()),border:{display:false}} }
            }
        });
    }

    /* ─── NSRDI Overdue Bar ─── */
    var nsrdiEl = document.getElementById('nsrdi-overdue-chart');
    if (nsrdiEl) {
        var nValues = JSON.parse(nsrdiEl.dataset.values || '[]');
        var nLabels = JSON.parse(nsrdiEl.dataset.labels || '[]');
        charts.nsrdiOverdue = new Chart(nsrdiEl.getContext('2d'), {
            type: 'bar',
            data: { labels:nLabels, datasets:[{ label:'Overdue', data:nValues, backgroundColor:'rgba(239,68,68,.85)', borderRadius:5, barPercentage:0.6, categoryPercentage:0.7 }]},
            options: {
                responsive:true, maintainAspectRatio:false,
                plugins: { legend:{display:false}, tooltip:{backgroundColor:tooltipBg(),titleColor:textColor(),bodyColor:mutedColor(),borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)',borderWidth:1,padding:12,cornerRadius:10} },
                scales: { x:{grid:{display:false},ticks:{color:mutedColor(),font:{weight:'700',size:10}},border:{display:false}}, y:{grid:{color:gridColor()},ticks:Object.assign({color:mutedColor(),font:{weight:'700'}},intTicks()),border:{display:false}} }
            }
        });
    }

    /* ─── Theme updater ─── */
    window.cbmUpdateChartTheme = function(theme) {
        isLight = (theme === 'light');
        Object.values(charts).forEach(function(c) {
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
        if (charts.donut) { charts.donut.data.datasets[0].borderColor = isLight ? '#eef2ff' : '#0d1117'; charts.donut.update(); }
        if (charts.acType) { charts.acType.data.datasets[0].borderColor = isLight ? '#eef2ff' : '#0d1117'; charts.acType.update(); }
    };
    window.cbmCharts = charts;
}

document.addEventListener('DOMContentLoaded', function() { requestAnimationFrame(cbmInitCharts); });
document.addEventListener('livewire:navigated', function() { requestAnimationFrame(cbmInitCharts); });
</script>

</div>
