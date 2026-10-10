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

/* ── Tabs Navigation ── */
.cbm-section { scroll-margin-top: 9rem; }
.cbm-tabs-container {
    position: sticky; top: 4.375rem; z-index: 40;
    margin: 0 0 2rem 0;
    padding: 0.5rem 0.75rem;
    background: var(--cbm-card-bg);
    border: 1px solid var(--cbm-card-border);
    border-radius: 1.125rem;
    box-shadow: var(--cbm-card-shadow);
    display: flex; gap: 0.5rem;
    overflow-x: auto;
    backdrop-filter: blur(12px);
}
.cbm-tab-btn {
    padding: 0.6rem 1.25rem;
    font-size: 0.8125rem; font-weight: 700;
    color: var(--cbm-text-muted);
    border-radius: 0.75rem;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.25s ease;
    border: 1px solid transparent; 
    background: transparent;
}
.cbm-tab-btn:hover { 
    color: var(--cbm-text); 
    background: rgba(148, 163, 184, 0.1); 
}
.cbm-tab-btn.active {
    background: var(--cbm-nav-active);
    color: var(--cbm-nav-active-t);
    border: 1px solid var(--cbm-card-border);
}

/* ── Stats Grid ── */
.cbm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.cbm-stats-grid-6 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

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

.cbm-sc-blue   { background: linear-gradient(145deg, rgba(59,130,246,.18) 0%, rgba(99,102,241,.10) 100%); border-color: rgba(59,130,246,.25); box-shadow: 0 4px 1.5rem rgba(59,130,246,.18); }
.cbm-sc-green  { background: linear-gradient(145deg, rgba(34,197,94,.16) 0%, rgba(16,185,129,.08) 100%); border-color: rgba(34,197,94,.25); box-shadow: 0 4px 1.5rem rgba(34,197,94,.18); }
.cbm-sc-orange { background: linear-gradient(145deg, rgba(251,146,60,.16) 0%, rgba(245,158,11,.08) 100%); border-color: rgba(251,146,60,.25); box-shadow: 0 4px 1.5rem rgba(251,146,60,.18); }
.cbm-sc-purple { background: linear-gradient(145deg, rgba(168,85,247,.16) 0%, rgba(236,72,153,.08) 100%); border-color: rgba(168,85,247,.25); box-shadow: 0 4px 1.5rem rgba(168,85,247,.18); }
.cbm-sc-cyan   { background: linear-gradient(145deg, rgba(6,182,212,.16) 0%, rgba(14,165,233,.08) 100%); border-color: rgba(6,182,212,.25); box-shadow: 0 4px 1.5rem rgba(6,182,212,.18); }
.cbm-sc-rose   { background: linear-gradient(145deg, rgba(244,63,94,.16) 0%, rgba(220,38,127,.08) 100%); border-color: rgba(244,63,94,.25); box-shadow: 0 4px 1.5rem rgba(244,63,94,.18); }
.cbm-sc-teal   { background: linear-gradient(145deg, rgba(20,184,166,.16) 0%, rgba(16,185,129,.08) 100%); border-color: rgba(20,184,166,.25); box-shadow: 0 4px 1.5rem rgba(20,184,166,.18); }
.cbm-sc-indigo { background: linear-gradient(145deg, rgba(99,102,241,.16) 0%, rgba(139,92,246,.08) 100%); border-color: rgba(99,102,241,.25); box-shadow: 0 4px 1.5rem rgba(99,102,241,.18); }

.cbm-sc-blue::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #3b82f6, #6366f1); }
.cbm-sc-green::before  { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #22c55e, #10b981); }
.cbm-sc-orange::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #fb923c, #f59e0b); }
.cbm-sc-purple::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #a855f7, #ec4899); }
.cbm-sc-cyan::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #06b6d4, #0ea5e9); }
.cbm-sc-rose::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #f43f5e, #ec4899); }
.cbm-sc-teal::before   { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #14b8a6, #10b981); }
.cbm-sc-indigo::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #6366f1, #8b5cf6); }

.cbm-light .cbm-sc-blue   { background: linear-gradient(145deg, rgba(59,130,246,.09) 0%, rgba(99,102,241,.05) 100%); box-shadow: 0 4px 1.25rem rgba(59,130,246,.15); }
.cbm-light .cbm-sc-green  { background: linear-gradient(145deg, rgba(34,197,94,.09) 0%, rgba(16,185,129,.04) 100%); box-shadow: 0 4px 1.25rem rgba(34,197,94,.15); }
.cbm-light .cbm-sc-orange { background: linear-gradient(145deg, rgba(251,146,60,.09) 0%, rgba(245,158,11,.04) 100%); box-shadow: 0 4px 1.25rem rgba(251,146,60,.15); }
.cbm-light .cbm-sc-purple { background: linear-gradient(145deg, rgba(168,85,247,.09) 0%, rgba(236,72,153,.04) 100%); box-shadow: 0 4px 1.25rem rgba(168,85,247,.15); }
.cbm-light .cbm-sc-cyan   { background: linear-gradient(145deg, rgba(6,182,212,.09) 0%, rgba(14,165,233,.04) 100%); box-shadow: 0 4px 1.25rem rgba(6,182,212,.15); }
.cbm-light .cbm-sc-rose   { background: linear-gradient(145deg, rgba(244,63,94,.09) 0%, rgba(220,38,127,.04) 100%); box-shadow: 0 4px 1.25rem rgba(244,63,94,.15); }
.cbm-light .cbm-sc-teal   { background: linear-gradient(145deg, rgba(20,184,166,.09) 0%, rgba(16,185,129,.04) 100%); box-shadow: 0 4px 1.25rem rgba(20,184,166,.15); }
.cbm-light .cbm-sc-indigo { background: linear-gradient(145deg, rgba(99,102,241,.09) 0%, rgba(139,92,246,.04) 100%); box-shadow: 0 4px 1.25rem rgba(99,102,241,.15); }

.cbm-stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .75rem; }
.cbm-stat-label { font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); letter-spacing: .01em; }
.cbm-stat-icon {
    width: 2.5rem; height: 2.5rem;
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.cbm-stat-icon svg { width: 1.25rem; height: 1.25rem; color: #fff; }
.cbm-si-blue   { background: linear-gradient(135deg,#3b82f6,#6366f1); box-shadow: 0 6px 1rem rgba(59,130,246,.4); }
.cbm-si-green  { background: linear-gradient(135deg,#22c55e,#10b981); box-shadow: 0 6px 1rem rgba(34,197,94,.4); }
.cbm-si-orange { background: linear-gradient(135deg,#fb923c,#f59e0b); box-shadow: 0 6px 1rem rgba(251,146,60,.4); }
.cbm-si-purple { background: linear-gradient(135deg,#a855f7,#ec4899); box-shadow: 0 6px 1rem rgba(168,85,247,.4); }
.cbm-si-cyan   { background: linear-gradient(135deg,#06b6d4,#0ea5e9); box-shadow: 0 6px 1rem rgba(6,182,212,.4); }
.cbm-si-rose   { background: linear-gradient(135deg,#f43f5e,#ec4899); box-shadow: 0 6px 1rem rgba(244,63,94,.4); }
.cbm-si-teal   { background: linear-gradient(135deg,#14b8a6,#10b981); box-shadow: 0 6px 1rem rgba(20,184,166,.4); }
.cbm-si-indigo { background: linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow: 0 6px 1rem rgba(99,102,241,.4); }

.cbm-stat-value { font-size: 2rem; font-weight: 800; color: var(--cbm-text); line-height: 1; margin-bottom: .5rem; letter-spacing: -.02em; }
.cbm-stat-sub-row { display: flex; gap: .4rem; flex-wrap: wrap; }
.cbm-stat-chip {
    font-size: .65rem; font-weight: 700;
    background: rgba(255,255,255,.08);
    color: var(--cbm-text-muted);
    padding: .175rem .45rem; border-radius: 62.4375rem;
    border: 1px solid rgba(255,255,255,.1);
}
.cbm-light .cbm-stat-chip { background: rgba(0,0,0,.05); border-color: rgba(0,0,0,.08); }

/* ── Progress bar inside card ── */
.cbm-progress-bar-wrap { margin-top: .625rem; }
.cbm-progress-label { display: flex; justify-content: space-between; font-size: .7rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: .3rem; }
.cbm-progress-track { height: 5px; border-radius: 62.4375rem; background: rgba(255,255,255,.1); overflow: hidden; }
.cbm-light .cbm-progress-track { background: rgba(0,0,0,.08); }
.cbm-progress-fill { height: 100%; border-radius: 62.4375rem; transition: width .6s ease; }

/* ── KPI Row ── */
.cbm-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

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
.cbm-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 1.75rem rgba(0,0,0,.2); }
.cbm-kpi-icon { width: 2.5rem; height: 2.5rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cbm-kpi-icon svg { width: 1.25rem; height: 1.25rem; color: #fff; }
.cbm-kpi-label { font-size: .65rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .06em; }
.cbm-kpi-value { font-size: 1.25rem; font-weight: 800; color: var(--cbm-text); margin-top: .1rem; letter-spacing: -.015em; }
.cbm-kpi-sub   { font-size: .7rem; color: var(--cbm-text-muted); margin-top: .15rem; font-weight: 500; }

/* ── Charts Row ── */
.cbm-charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 26rem), 1fr));
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}
.cbm-charts-row > * { min-width: 0; }

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
.cbm-chart-card:hover { box-shadow: 0 0.75rem 2.25rem rgba(0,0,0,.25); }

.cbm-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.125rem; }
.cbm-card-title { font-size: .9375rem; font-weight: 800; color: var(--cbm-text); letter-spacing: -.01em; }
.cbm-card-sub   { font-size: .775rem; color: var(--cbm-text-muted); margin-top: .2rem; font-weight: 500; }
.cbm-chart-container { flex: 1; min-height: 13.125rem; position: relative; }

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
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.cbm-period-tabs { display: inline-flex; gap: .25rem; padding: .25rem; margin-bottom: 1rem; background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: .875rem; }
.cbm-range-pill { display: inline-flex; align-items: center; gap: .5rem; background: var(--cbm-nav-active); border: 1px solid var(--cbm-card-border); border-radius: .875rem; padding: .5rem 1rem; font-size: .8rem; font-weight: 700; color: var(--cbm-nav-active-t); }
.cbm-range-pill svg { width: 1rem; height: 1rem; }
.cbm-ac-mini { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .75rem; }
[wire\:loading].cbm-loading { opacity: .6; }

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
