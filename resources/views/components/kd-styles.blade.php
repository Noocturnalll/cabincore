{{-- Shared look of the KPI-style pages (summary cards, sticky-column tables, period bar). Included once per page. --}}
<style>
    .kd { transition: opacity .18s ease; }
    .kd.kd-busy { opacity: .72; }
    .kd-bar { position: sticky; top: 4.25rem; z-index: 20; display: flex; flex-wrap: wrap; gap: .625rem; align-items: center; justify-content: space-between; padding: .75rem 1rem; margin-bottom: 1rem; background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: 1rem; box-shadow: var(--cbm-card-shadow); }
    .kd-snav { flex: 1 0 100%; display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; padding-top: .5rem; margin-top: .25rem; border-top: 1px solid var(--cbm-divider); }
    .kd-snav::-webkit-scrollbar { display: none; }
    .kd-snav button { flex: 0 0 auto; border: 0; background: transparent; color: var(--cbm-text-muted); padding: .35rem .8rem; border-radius: .6rem; font-weight: 700; font-size: .78rem; cursor: pointer; white-space: nowrap; transition: background .15s, color .15s; }
    .kd-snav button:hover { background: var(--cbm-nav-hover); color: var(--cbm-text); }
    .kd-snav button.on { background: var(--cbm-nav-active); color: var(--cbm-nav-active-t); }
    [data-kd-sec] { scroll-margin-top: 9.5rem; }
    .kd-chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .kd-chart-card { margin: 0; padding: .85rem 1rem 1rem; background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: 1.1rem; box-shadow: var(--cbm-card-shadow); min-width: 0; }
    .kd-chart-card figcaption { display: flex; flex-direction: column; gap: .1rem; margin-bottom: .6rem; color: var(--cbm-text); font-size: .9rem; }
    .kd-chart-box { position: relative; min-width: 0; }
    .kd-chart-box canvas { max-width: 100%; }
    .kd-cell { line-height: 1.25; }
    .kd-cell small { display: block; color: var(--cbm-text-muted); font-size: .7rem; }
    .kd-group { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .kd-seg { display: inline-flex; background: var(--cbm-input-bg); border: 1px solid var(--cbm-input-border); border-radius: .75rem; padding: .2rem; }
    .kd-seg button { border: 0; background: transparent; color: var(--cbm-text-muted); padding: .4rem .85rem; border-radius: .55rem; font-weight: 600; font-size: .85rem; cursor: pointer; transition: background .15s, color .15s; }
    .kd-seg button.on { background: var(--cbm-nav-active); color: var(--cbm-nav-active-t); }
    .kd-nav { display: inline-flex; align-items: center; gap: .35rem; }
    .kd-nav button { width: 2.25rem; height: 2.25rem; border-radius: .65rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-input-bg); color: var(--cbm-text); font-size: 1.1rem; cursor: pointer; }
    .kd-nav button:active { transform: scale(.94); }
    .kd-label { font-weight: 700; color: var(--cbm-text); min-width: 8.5rem; text-align: center; }
    .kd select, .kd input[type=date] { background: var(--cbm-input-bg); color: var(--cbm-text); border: 1px solid var(--cbm-input-border); border-radius: .65rem; padding: .45rem .65rem; font-size: .85rem; }
    .kd-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(10.5rem, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .kd-card { position: relative; padding: .9rem 1rem; border-radius: 1rem; background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); box-shadow: var(--cbm-card-shadow); overflow: hidden; }
    .kd-card::before { content: ''; position: absolute; inset: 0 auto 0 0; width: .28rem; background: var(--tone, #94a3b8); }
    .kd-card .t { font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; color: var(--cbm-text-muted); }
    .kd-card .v { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: var(--cbm-text); margin-top: .15rem; font-variant-numeric: tabular-nums; }
    .kd-card .s { font-size: .75rem; color: var(--cbm-text-muted); margin-top: .1rem; }
    .kd-tile { display: block; text-decoration: none; transition: transform .14s ease, box-shadow .14s ease; }
.kd-tile:hover { transform: translateY(-2px); box-shadow: 0 .6rem 1.4rem rgba(0,0,0,.3); }
.kd-tile:active { transform: translateY(0); }
.kd-tile .v { font-size: 1.5rem; }
.kd-tile .s { overflow-wrap: anywhere; }
.kd-sec { margin: 1.25rem 0 .55rem; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: var(--cbm-text-muted); font-weight: 700; }
.kd-chip { display: inline-block; min-width: 3.4rem; text-align: center; padding: .15rem .55rem; border-radius: 999px; font-size: .78rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .kd-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; }
    .kd-table th { position: sticky; top: 0; background: var(--cbm-card-bg); color: var(--cbm-text-muted); text-align: right; font-size: .7rem; letter-spacing: .06em; text-transform: uppercase; padding: .7rem .75rem; white-space: nowrap; box-shadow: inset 0 -2px 0 var(--cbm-divider); cursor: pointer; user-select: none; }
    .kd-table th:first-child, .kd-table td:first-child { text-align: left; position: sticky; left: 0; z-index: 1; background: var(--cbm-card-bg); }
    .kd-table th:first-child { z-index: 3; }
    .kd-table td { text-align: right; padding: .65rem .75rem; border-bottom: 1px solid var(--cbm-divider); color: var(--cbm-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .kd-table tbody tr { transition: background .12s; }
    .kd-table tbody tr:hover td { background: var(--cbm-nav-hover); }
    .kd-table tfoot td { font-weight: 800; border-top: 2px solid var(--cbm-divider); border-bottom: 0; }
    .kd-table th .arrow { opacity: .8; margin-left: .15rem; color: var(--cbm-nav-active-t); }
    .kd-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .kd-sub { color: var(--cbm-text-muted); font-size: .8rem; }
    .kd-panel { background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: 1.1rem; box-shadow: var(--cbm-card-shadow); overflow: hidden; margin-bottom: 1rem; }
    .kd-panel h2 { margin: 0; padding: .85rem 1rem; font-size: .95rem; font-weight: 700; color: var(--cbm-text); border-bottom: 1px solid var(--cbm-divider); display: flex; justify-content: space-between; gap: .5rem; flex-wrap: wrap; }
    .kd-empty { padding: 2rem 1rem; text-align: center; color: var(--cbm-text-muted); }
    .kd-skel { display: none; }
    .kd-busy .kd-skel { display: block; height: 3px; background: linear-gradient(90deg, transparent, var(--cbm-blue), transparent); background-size: 200% 100%; animation: kdslide 1s linear infinite; position: fixed; top: 0; left: 0; right: 0; z-index: 99; }
    @keyframes kdslide { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    @media (max-width: 640px) {
        .kd-bar { position: static; padding: .65rem .75rem; }
        .kd-label { min-width: 0; flex: 1; }
        .kd-group { width: 100%; justify-content: space-between; }
        .kd-card .v { font-size: 1.45rem; }
        .kd-table { font-size: .8rem; }
        .kd-table th, .kd-table td { padding: .55rem .5rem; }
    }
    @media (prefers-reduced-motion: reduce) { .kd, .kd-table tbody tr, .kd-seg button { transition: none; } .kd-busy .kd-skel { animation: none; } }
</style>
