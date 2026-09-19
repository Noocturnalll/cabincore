<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="cbm-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cabin Core Dashboard - Batam Aero Technic">
    <title>{{ $title ?? 'Dashboard' }} — Cabin Core - Batam Aero Technic</title>
    <link rel="icon" type="image/png" href="{{ asset('images/lion-logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Vite Assets -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles

    <style>
    .cbm-file-input::-webkit-file-upload-button {
        background: var(--cbm-bg);
        border: 1px solid var(--cbm-border);
        color: var(--cbm-text);
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        margin-right: 0.75rem;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.2s;
    }
    .cbm-file-input::-webkit-file-upload-button:hover { background: var(--cbm-nav-hover); }
    .cbm-file-input::file-selector-button {
        background: var(--cbm-bg);
        border: 1px solid var(--cbm-border);
        color: var(--cbm-text);
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        margin-right: 0.75rem;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.2s;
    }
    .cbm-file-input::file-selector-button:hover { background: var(--cbm-nav-hover); }

    /* ═══════════════════════════════════════════════════
       CSS VARIABLES — Dark (default) & Light
    ═══════════════════════════════════════════════════ */
    :root {
        --cbm-bg:            #0a0e1a;
        --cbm-sidebar-bg:    #0d1117;
        --cbm-sidebar-w:     260px;
        --cbm-topbar-bg:     rgba(13,17,23,.90);
        --cbm-card-bg:       rgba(255,255,255,.045);
        --cbm-card-border:   rgba(255,255,255,.08);
        --cbm-card-shadow:   0 4px 24px rgba(0,0,0,.30);
        --cbm-text:          #f1f5f9;
        --cbm-text-muted:    rgba(255,255,255,.50);
        --cbm-text-sub:      rgba(255,255,255,.28);
        --cbm-divider:       rgba(255,255,255,.07);
        --cbm-nav-hover:     rgba(255,255,255,.06);
        --cbm-nav-active:    rgba(59,130,246,.18);
        --cbm-nav-active-t:  #60a5fa;
        --cbm-input-bg:      rgba(255,255,255,.06);
        --cbm-input-border:  rgba(255,255,255,.10);
        --cbm-overlay-bg:    rgba(0,0,0,.55);
        --cbm-blue:          #3b82f6;
        --cbm-blue-glow:     rgba(59,130,246,.28);
        --cbm-indigo:        #6366f1;
        --cbm-green:         #4ade80;
        --cbm-red:           #f87171;
        --cbm-yellow:        #fbbf24;
        --cbm-purple:        #a78bfa;
        --cbm-cyan:          #38bdf8;
        --cbm-stat-1:        linear-gradient(135deg,#3b82f6,#6366f1);
        --cbm-stat-2:        linear-gradient(135deg,#10b981,#059669);
        --cbm-stat-3:        linear-gradient(135deg,#f59e0b,#d97706);
        --cbm-stat-4:        linear-gradient(135deg,#8b5cf6,#7c3aed);
        --cbm-transition:    background .3s ease, color .3s ease, border-color .3s ease, box-shadow .3s ease;
    }

    .cbm-light {
        --cbm-bg:            #eef2ff;
        --cbm-sidebar-bg:    #ffffff;
        --cbm-topbar-bg:     rgba(255,255,255,.92);
        --cbm-card-bg:       rgba(255,255,255,.90);
        --cbm-card-border:   rgba(148,163,184,.20);
        --cbm-card-shadow:   0 4px 20px rgba(37,99,235,.08), 0 1px 4px rgba(0,0,0,.06);
        --cbm-text:          #0f172a;
        --cbm-text-muted:    #64748b;
        --cbm-text-sub:      #94a3b8;
        --cbm-divider:       rgba(148,163,184,.15);
        --cbm-nav-hover:     rgba(99,102,241,.07);
        --cbm-nav-active:    rgba(59,130,246,.12);
        --cbm-nav-active-t:  #2563eb;
        --cbm-input-bg:      #f1f5f9;
        --cbm-input-border:  #e2e8f0;
        --cbm-overlay-bg:    rgba(15,23,42,.45);
        --cbm-blue:          #2563eb;
        --cbm-blue-glow:     rgba(37,99,235,.15);
    }

    /* ═══════════════════════════════════════════════════
       RESET & BASE
    ═══════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: var(--cbm-bg) !important;
        color: var(--cbm-text) !important;
        transition: var(--cbm-transition);
        overflow-x: hidden;
    }

    /* ═══════════════════════════════════════════════════
       APP SHELL
    ═══════════════════════════════════════════════════ */
    .cbm-app { display: flex; min-height: 100vh; }

    /* ═══════════════════════════════════════════════════
       OVERLAY (mobile)
    ═══════════════════════════════════════════════════ */
    .cbm-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: var(--cbm-overlay-bg);
        z-index: 90;
        opacity: 0;
        transition: opacity .3s ease;
    }
    .cbm-overlay.cbm-visible {
        display: block;
        opacity: 1;
    }

    /* ═══════════════════════════════════════════════════
       SIDEBAR
    ═══════════════════════════════════════════════════ */
    .cbm-sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--cbm-sidebar-w);
        background: var(--cbm-sidebar-bg);
        border-right: 1px solid var(--cbm-divider);
        display: flex;
        flex-direction: column;
        z-index: 100;
        transition: transform .3s cubic-bezier(.16,1,.3,1), background .3s ease, border-color .3s ease;
    }
    @media (max-width: 1023px) {
        .cbm-sidebar {
            transform: translateX(-100%);
            box-shadow: 4px 0 40px rgba(0,0,0,.3);
        }
        .cbm-sidebar.cbm-open { transform: translateX(0); }
    }

    /* Sidebar header */
    .cbm-sidebar-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1.5rem 1.5rem 1.25rem;
        border-bottom: 1px solid var(--cbm-divider);
        flex-shrink: 0;
    }
    .cbm-sidebar-logo {
        width: 2.5rem; height: 2.5rem;
        background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-indigo));
        border-radius: .75rem;
        display: flex; align-items: center; justify-content: center;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 4px 12px var(--cbm-blue-glow);
    }
    .cbm-sidebar-logo svg { width: 1.125rem; height: 1.125rem; }
    .cbm-sidebar-brand { line-height: 1.2; }
    .cbm-sidebar-brand-name {
        font-size: .9375rem;
        font-weight: 800;
        color: var(--cbm-text);
        letter-spacing: -.01em;
    }
    .cbm-sidebar-brand-sub {
        font-size: .6875rem;
        color: var(--cbm-text-muted);
        font-weight: 500;
        letter-spacing: .03em;
    }

    /* Sidebar nav */
    .cbm-sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 1rem;
        display: flex;
        flex-direction: column;
        gap: .25rem;
    }
    .cbm-sidebar-nav::-webkit-scrollbar { width: 4px; }
    .cbm-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .cbm-sidebar-nav::-webkit-scrollbar-thumb { background: var(--cbm-divider); border-radius: 2px; }

    .cbm-nav-section-label {
        font-size: .6875rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--cbm-text-sub);
        padding: .875rem .5rem .375rem;
    }

    .cbm-nav-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .625rem .5rem;
        border-radius: .75rem;
        color: var(--cbm-text-muted);
        text-decoration: none;
        font-size: .875rem;
        font-weight: 600;
        transition: background .15s, color .15s;
        position: relative;
        cursor: pointer;
    }
    .cbm-nav-item svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
    .cbm-nav-item:hover {
        background: var(--cbm-nav-hover);
        color: var(--cbm-text);
    }
    .cbm-nav-item.cbm-active {
        background: var(--cbm-nav-active);
        color: var(--cbm-nav-active-t);
    }
    .cbm-nav-item.cbm-active::before {
        content: '';
        position: absolute;
        left: 0; top: 20%; bottom: 20%;
        width: 3px;
        background: var(--cbm-nav-active-t);
        border-radius: 0 2px 2px 0;
    }
    .cbm-nav-badge {
        margin-left: auto;
        background: var(--cbm-blue);
        color: white;
        font-size: .6875rem;
        font-weight: 700;
        padding: .1rem .4rem;
        border-radius: 999px;
        line-height: 1.4;
    }

    /* Sidebar footer */
    .cbm-sidebar-footer {
        padding: 1rem .875rem;
        border-top: 1px solid var(--cbm-divider);
        flex-shrink: 0;
    }
    .cbm-user-card {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .625rem .75rem;
        border-radius: .875rem;
        background: var(--cbm-nav-hover);
        cursor: pointer;
        transition: background .15s;
    }
    .cbm-user-card:hover { background: var(--cbm-nav-active); }
    .cbm-user-avatar {
        width: 2.25rem; height: 2.25rem;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple));
        display: flex; align-items: center; justify-content: center;
        color: white;
        font-weight: 800;
        font-size: .8125rem;
        flex-shrink: 0;
    }
    .cbm-user-name {
        font-size: .875rem;
        font-weight: 700;
        color: var(--cbm-text);
        line-height: 1.2;
    }
    .cbm-user-role {
        font-size: .6875rem;
        color: var(--cbm-text-muted);
        font-weight: 500;
    }
    .cbm-user-card svg { width: 1rem; height: 1rem; color: var(--cbm-text-sub); margin-left: auto; }

    /* ═══════════════════════════════════════════════════
       MAIN AREA
    ═══════════════════════════════════════════════════ */
    .cbm-main {
        flex: 1;
        margin-left: var(--cbm-sidebar-w);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        transition: margin-left .3s cubic-bezier(.16,1,.3,1);
    }
    @media (max-width: 1023px) { .cbm-main { margin-left: 0; } }

    /* ═══════════════════════════════════════════════════
       TOPBAR
    ═══════════════════════════════════════════════════ */
    .cbm-topbar {
        position: sticky;
        top: 0;
        z-index: 80;
        height: 4rem;
        background: var(--cbm-topbar-bg);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--cbm-divider);
        display: flex;
        align-items: center;
        padding: 0 1.5rem;
        gap: .75rem;
        transition: var(--cbm-transition);
    }

    /* Hamburger */
    .cbm-hamburger {
        display: none;
        width: 2.25rem; height: 2.25rem;
        align-items: center; justify-content: center;
        border: none;
        background: var(--cbm-input-bg);
        border-radius: .5rem;
        cursor: pointer;
        color: var(--cbm-text-muted);
        transition: background .15s, color .15s;
        flex-shrink: 0;
    }
    .cbm-hamburger:hover { background: var(--cbm-nav-hover); color: var(--cbm-text); }
    .cbm-hamburger svg { width: 1.25rem; height: 1.25rem; }
    @media (max-width: 1023px) { .cbm-hamburger { display: flex; } }

    /* Search */
    .cbm-search {
        flex: 1;
        max-width: 22rem;
        position: relative;
    }
    .cbm-search-icon {
        position: absolute;
        left: .75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--cbm-text-sub);
        pointer-events: none;
    }
    .cbm-search-icon svg { width: 1rem; height: 1rem; }
    .cbm-search input {
        width: 100%;
        background: var(--cbm-input-bg);
        border: 1px solid var(--cbm-input-border);
        border-radius: .625rem;
        padding: .5rem .75rem .5rem 2.25rem;
        font-family: inherit;
        font-size: .875rem;
        color: var(--cbm-text);
        outline: none;
        transition: var(--cbm-transition);
    }
    .cbm-search input::placeholder { color: var(--cbm-text-sub); }
    .cbm-search input:focus {
        border-color: var(--cbm-blue);
        background: var(--cbm-nav-active);
    }
    @media (max-width: 640px) { .cbm-search { display: none; } }

    .cbm-topbar-spacer { flex: 1; }

    /* Topbar actions */
    .cbm-topbar-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .cbm-icon-btn {
        width: 2.25rem; height: 2.25rem;
        display: flex; align-items: center; justify-content: center;
        border: none;
        background: var(--cbm-input-bg);
        border-radius: .625rem;
        cursor: pointer;
        color: var(--cbm-text-muted);
        transition: background .15s, color .15s;
        position: relative;
    }
    .cbm-icon-btn:hover { background: var(--cbm-nav-hover); color: var(--cbm-text); }
    .cbm-icon-btn svg { width: 1.125rem; height: 1.125rem; }
    .cbm-notif-dot {
        position: absolute;
        top: .375rem; right: .375rem;
        width: .5rem; height: .5rem;
        background: #f87171;
        border-radius: 50%;
        border: 1.5px solid var(--cbm-topbar-bg);
    }

    /* Topbar user */
    .cbm-topbar-user {
        display: flex;
        align-items: center;
        gap: .625rem;
        padding: .375rem .75rem .375rem .375rem;
        border-radius: .75rem;
        cursor: pointer;
        background: var(--cbm-input-bg);
        border: 1px solid var(--cbm-input-border);
        transition: var(--cbm-transition);
        margin-left: .25rem;
    }
    .cbm-topbar-user:hover { background: var(--cbm-nav-hover); }
    .cbm-topbar-avatar {
        width: 1.875rem; height: 1.875rem;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple));
        display: flex; align-items: center; justify-content: center;
        color: white;
        font-weight: 800;
        font-size: .6875rem;
        flex-shrink: 0;
    }
    .cbm-topbar-uname {
        font-size: .8125rem;
        font-weight: 700;
        color: var(--cbm-text);
        white-space: nowrap;
    }
    @media (max-width: 480px) { .cbm-topbar-uname { display: none; } }
    .cbm-topbar-user svg { width: 1rem; height: 1rem; color: var(--cbm-text-sub); }

    /* Dropdowns */
    .cbm-dropdown-container {
        position: relative;
    }
    .cbm-dropdown-menu {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        background: var(--cbm-card-bg);
        border: 1px solid var(--cbm-card-border);
        box-shadow: var(--cbm-card-shadow);
        border-radius: 1rem;
        min-width: 14rem;
        padding: 0.5rem;
        z-index: 9999;
        backdrop-filter: blur(12px);
    }
    .cbm-dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 0.75rem;
        border-radius: 0.5rem;
        color: var(--cbm-text);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: var(--cbm-transition);
        cursor: pointer;
        width: 100%;
        text-align: left;
        border: none;
        background: transparent;
    }
    .cbm-dropdown-item:hover {
        background: var(--cbm-nav-hover);
        color: var(--cbm-blue);
    }
    .cbm-dropdown-item svg { width: 1.125rem; height: 1.125rem; color: var(--cbm-text-sub); }
    .cbm-dropdown-item:hover svg { color: var(--cbm-blue); }
    .cbm-dropdown-divider {
        height: 1px;
        background: var(--cbm-divider);
        margin: 0.25rem 0;
    }
    .cbm-dropdown-header {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--cbm-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* ═══════════════════════════════════════════════════
       PAGE CONTENT WRAPPER
    ═══════════════════════════════════════════════════ */
    .cbm-content {
        flex: 1;
        padding: 1.75rem 1.5rem 2.5rem;
        max-width: 1600px;
        width: 100%;
        position: relative;
        z-index: 10;
    }
    @media (max-width: 640px) { .cbm-content { padding: 1.25rem 1rem 2rem; } }

    /* ═══════════════════════════════════════════════════
       CABIN MAINTENANCE MODULE — Shared Styles (v2 Colorful)
    ═══════════════════════════════════════════════════ */

    /* ── Page Header ── */
    .mod-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;
    }
    .mod-title-block { min-width: 0; }
    .mod-title-accent {
        display: inline-block;
        font-size: .6875rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase;
        padding: .2rem .625rem; border-radius: 999px; margin-bottom: .5rem;
    }
    .mod-title-accent-blue   { background: rgba(59,130,246,.15);  color: #60a5fa;  border: 1px solid rgba(59,130,246,.25); }
    .mod-title-accent-green  { background: rgba(52,211,153,.15);  color: #34d399;  border: 1px solid rgba(52,211,153,.25); }
    .mod-title-accent-orange { background: rgba(251,146,60,.15);  color: #fb923c;  border: 1px solid rgba(251,146,60,.25); }
    .mod-title-accent-purple { background: rgba(167,139,250,.15); color: #a78bfa;  border: 1px solid rgba(167,139,250,.25); }
    .cbm-light .mod-title-accent-blue   { background: rgba(37,99,235,.08);  color: #2563eb;  border-color: rgba(37,99,235,.2); }
    .cbm-light .mod-title-accent-green  { background: rgba(5,150,105,.08);   color: #059669;  border-color: rgba(5,150,105,.2); }
    .cbm-light .mod-title-accent-orange { background: rgba(217,119,6,.08);   color: #d97706;  border-color: rgba(217,119,6,.2); }
    .cbm-light .mod-title-accent-purple { background: rgba(124,58,237,.08);  color: #7c3aed;  border-color: rgba(124,58,237,.2); }
    .mod-title {
        font-size: clamp(1.375rem, 2.5vw, 1.75rem);
        font-weight: 800; color: var(--cbm-text);
        letter-spacing: -.025em; line-height: 1.2;
    }
    .mod-subtitle { font-size: .875rem; color: var(--cbm-text-muted); margin-top: .2rem; font-weight: 500; }
    .mod-actions { display: flex; gap: .5rem; align-items: center; flex-shrink: 0; flex-wrap: wrap; }

    /* ── Buttons ── */
    .mod-btn-outline {
        display: inline-flex; align-items: center; gap: .4rem;
        background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border);
        color: var(--cbm-text-muted); padding: .5625rem 1rem;
        border-radius: .75rem; font-size: .8125rem; font-weight: 700;
        text-decoration: none; cursor: pointer; font-family: inherit;
        transition: all .2s cubic-bezier(.4,0,.2,1);
        white-space: nowrap;
    }
    .mod-btn-outline:hover {
        border-color: var(--cbm-blue); color: var(--cbm-blue);
        background: rgba(59,130,246,.06);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--cbm-blue-glow);
    }
    .mod-btn-outline:active { transform: translateY(0); box-shadow: none; }
    .mod-btn-outline svg { width: .875rem; height: .875rem; flex-shrink: 0; }

    .mod-btn-primary {
        display: inline-flex; align-items: center; gap: .4rem;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: white; border: none;
        padding: .5625rem 1.125rem; border-radius: .75rem;
        font-size: .8125rem; font-weight: 700; cursor: pointer; font-family: inherit;
        box-shadow: 0 4px 14px rgba(59,130,246,.4);
        transition: all .2s cubic-bezier(.4,0,.2,1);
        white-space: nowrap;
    }
    .mod-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(59,130,246,.5); }
    .mod-btn-primary:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(59,130,246,.35); }
    .mod-btn-primary:disabled { opacity: .7; cursor: not-allowed; transform: none; }
    .mod-btn-primary svg { width: .875rem; height: .875rem; flex-shrink: 0; }

    /* ── Card ── */
    .mod-card {
        background: var(--cbm-card-bg);
        border: 1px solid var(--cbm-card-border);
        border-radius: 1.375rem;
        box-shadow: var(--cbm-card-shadow);
        overflow: hidden;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        transition: box-shadow .25s ease;
    }
    .mod-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.35); }
    .cbm-light .mod-card:hover { box-shadow: 0 8px 28px rgba(37,99,235,.12); }

    /* Colored top-border accent per module */
    .mod-card-accent-blue   { border-top: 2px solid rgba(59,130,246,.6); }
    .mod-card-accent-green  { border-top: 2px solid rgba(52,211,153,.6); }
    .mod-card-accent-orange { border-top: 2px solid rgba(251,146,60,.6); }
    .mod-card-accent-purple { border-top: 2px solid rgba(167,139,250,.6); }

    /* ── Toolbar ── */
    .mod-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--cbm-divider);
        flex-wrap: wrap;
    }
    .mod-search-wrap { position: relative; }
    .mod-search-wrap svg {
        position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
        width: .875rem; height: .875rem; color: var(--cbm-text-sub); pointer-events: none;
    }
    .mod-search-input {
        background: var(--cbm-input-bg); border: 1px solid var(--cbm-input-border);
        color: var(--cbm-text); padding: .5rem .875rem .5rem 2.25rem;
        border-radius: .75rem; font-size: .875rem; font-family: inherit;
        width: clamp(180px, 30vw, 260px);
        outline: none; transition: border-color .2s, box-shadow .2s;
    }
    .mod-search-input:focus { border-color: var(--cbm-blue); box-shadow: 0 0 0 3px var(--cbm-blue-glow); }
    .mod-search-input::placeholder { color: var(--cbm-text-sub); }

    .mod-record-count {
        font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted);
        background: var(--cbm-input-bg); border: 1px solid var(--cbm-input-border);
        padding: .35rem .75rem; border-radius: 999px;
        white-space: nowrap;
    }

    /* ── Table ── */
    .mod-table-wrap { overflow-x: auto; }
    .mod-table { width: 100%; border-collapse: collapse; min-width: 640px; font-size: .8125rem; }
    .mod-table thead tr { background: var(--cbm-nav-hover); border-bottom: 1px solid var(--cbm-divider); }
    .mod-table thead th {
        padding: .75rem .875rem; font-size: .65rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: .08em; color: var(--cbm-text-sub);
        text-align: left; white-space: nowrap;
    }
    .mod-table tbody tr { border-bottom: 1px solid var(--cbm-divider); transition: background .15s ease; }
    .mod-table tbody tr:last-child { border-bottom: none; }
    .mod-table tbody tr:hover { background: var(--cbm-nav-hover); }
    .mod-table tbody td { padding: .75rem .875rem; color: var(--cbm-text); font-weight: 500; vertical-align: middle; }

    .mod-aircraft-name { font-weight: 700; color: var(--cbm-text); letter-spacing: -.01em; }
    .mod-aircraft-sub  { font-size: .7rem; color: var(--cbm-text-muted); margin-top: .15rem; }

    /* ── Badges ── */
    @keyframes cbm-pulse { 0%,100%{opacity:1;} 50%{opacity:.5;} }
    .mod-badge-closed {
        display: inline-flex; align-items: center; gap: .3rem;
        background: rgba(52,211,153,.12); color: #34d399;
        padding: .25rem .7rem; border-radius: 999px; font-size: .7rem; font-weight: 800;
        white-space: nowrap; border: 1px solid rgba(52,211,153,.25);
    }
    .mod-badge-open {
        display: inline-flex; align-items: center; gap: .3rem;
        background: rgba(248,113,113,.12); color: #f87171;
        padding: .25rem .7rem; border-radius: 999px; font-size: .7rem; font-weight: 800;
        white-space: nowrap; border: 1px solid rgba(248,113,113,.25);
    }
    .mod-badge-dot {
        width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0;
    }
    .mod-badge-open .mod-badge-dot { animation: cbm-pulse 2s ease-in-out infinite; }

    .mod-type-planned   { color: var(--cbm-blue); font-weight: 700; font-size: .8rem; }
    .mod-type-unplanned { color: var(--cbm-text-muted); font-size: .8rem; font-weight: 600; }

    /* ── Action button in table ── */
    .mod-action-btn {
        background: transparent; border: 1px solid var(--cbm-card-border);
        color: var(--cbm-text-muted); cursor: pointer;
        padding: .375rem .625rem; border-radius: .625rem;
        font-size: .75rem; font-weight: 700; font-family: inherit;
        transition: all .18s cubic-bezier(.4,0,.2,1); line-height: 1;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .mod-action-btn:hover {
        background: rgba(59,130,246,.08);
        border-color: rgba(59,130,246,.35);
        color: var(--cbm-blue);
        transform: translateY(-1px);
    }
    .mod-action-btn:active { transform: translateY(0); }
    .mod-action-btn svg { width: .875rem; height: .875rem; }

    /* ── Empty state ── */
    .mod-empty { padding: 4rem 1.5rem; text-align: center; }
    .mod-empty-icon {
        width: 4rem; height: 4rem; margin: 0 auto 1.25rem;
        display: flex; align-items: center; justify-content: center;
        background: var(--cbm-input-bg); border-radius: 1.125rem;
        color: var(--cbm-text-sub);
    }
    .mod-empty-icon svg { width: 2rem; height: 2rem; }
    .mod-empty-title { font-size: 1rem; font-weight: 800; color: var(--cbm-text); }
    .mod-empty-sub   { font-size: .8125rem; color: var(--cbm-text-muted); margin-top: .375rem; font-weight: 500; }

    /* ── Pagination ── */
    .mod-pagination { padding: .875rem 1.25rem; border-top: 1px solid var(--cbm-divider); }

    /* ── Tabs ── */
    .cbm-tabs {
        display: flex; gap: .375rem;
        padding: .875rem 1.25rem 0;
        border-bottom: 1px solid var(--cbm-divider);
        overflow-x: auto; scrollbar-width: none;
    }
    .cbm-tabs::-webkit-scrollbar { display: none; }
    .cbm-tab {
        padding: .5625rem 1.125rem; font-size: .8125rem; font-weight: 700;
        color: var(--cbm-text-muted); border: none; background: transparent;
        border-bottom: 2px solid transparent; cursor: pointer;
        transition: all .2s cubic-bezier(.4,0,.2,1);
        white-space: nowrap; margin-bottom: -1px;
    }
    .cbm-tab:hover { color: var(--cbm-text); }
    .cbm-tab.active {
        color: var(--cbm-blue);
        border-bottom-color: var(--cbm-blue);
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .mod-header { gap: .875rem; }
        .mod-actions { gap: .375rem; }
        .mod-btn-outline, .mod-btn-primary { padding: .5rem .75rem; font-size: .75rem; }
        .mod-toolbar { padding: .75rem 1rem; }
        .mod-search-input { width: 100%; }
        .mod-toolbar { flex-direction: column; align-items: stretch; }
        .mod-table thead th { padding: .625rem .625rem; }
        .mod-table tbody td { padding: .625rem .625rem; }
    }
    @media (max-width: 480px) {
        .mod-title { font-size: 1.25rem; }
        .mod-actions { width: 100%; justify-content: flex-start; }
    }


    /* ═══════════════════════════════════════════════════
       ENTERPRISE MODAL SYSTEM
    ═══════════════════════════════════════════════════ */
    .cbm-modal-overlay {
        position: fixed;
        inset: 0;
        background: var(--cbm-overlay-bg);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
    }
    .cbm-modal-panel {
        background: var(--cbm-sidebar-bg);
        border: 1px solid var(--cbm-card-border);
        box-shadow: 0 25px 60px rgba(0,0,0,.45);
        border-radius: 1.25rem;
        width: 100%;
        max-width: 480px;
        overflow: hidden;
    }
    .cbm-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.375rem 1.5rem 1.125rem;
        border-bottom: 1px solid var(--cbm-divider);
    }
    .cbm-modal-title {
        font-size: 1rem;
        font-weight: 800;
        color: var(--cbm-text);
        letter-spacing: -.01em;
    }
    .cbm-modal-subtitle {
        font-size: .75rem;
        color: var(--cbm-text-muted);
        font-weight: 500;
        margin-top: .125rem;
    }
    .cbm-modal-close {
        width: 2rem; height: 2rem;
        display: flex; align-items: center; justify-content: center;
        border: none; background: var(--cbm-input-bg);
        border-radius: .5rem; cursor: pointer;
        color: var(--cbm-text-muted);
        transition: background .15s, color .15s;
        flex-shrink: 0;
    }
    .cbm-modal-close:hover { background: var(--cbm-nav-hover); color: var(--cbm-text); }
    .cbm-modal-close svg { width: 1rem; height: 1rem; }
    .cbm-modal-body { padding: 1.5rem; }
    .cbm-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .75rem;
        padding: 1rem 1.5rem 1.375rem;
        border-top: 1px solid var(--cbm-divider);
    }

    /* ═══════════════════════════════════════════════════
       SHARED FORM ELEMENTS
    ═══════════════════════════════════════════════════ */
    .cbm-form-group { margin-bottom: 1.125rem; }
    .cbm-form-label {
        display: block;
        font-size: .8125rem;
        font-weight: 700;
        color: var(--cbm-text-muted);
        margin-bottom: .4rem;
        letter-spacing: .01em;
    }
    .cbm-form-select,
    .cbm-form-textarea,
    .cbm-form-input {
        width: 100%;
        background: var(--cbm-input-bg);
        border: 1px solid var(--cbm-input-border);
        border-radius: .625rem;
        padding: .5625rem .875rem;
        font-size: .875rem;
        font-family: inherit;
        color: var(--cbm-text);
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        appearance: none;
        -webkit-appearance: none;
    }
    .cbm-form-select:focus,
    .cbm-form-textarea:focus,
    .cbm-form-input:focus {
        border-color: var(--cbm-blue);
        box-shadow: 0 0 0 3px var(--cbm-blue-glow);
    }
    .cbm-form-select option {
        background: var(--cbm-sidebar-bg);
        color: var(--cbm-text);
    }
    .cbm-form-textarea { resize: vertical; min-height: 80px; line-height: 1.5; }
    .cbm-select-wrap { position: relative; }
    .cbm-select-wrap::after {
        content: '';
        position: absolute;
        right: .875rem; top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        width: 0; height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid var(--cbm-text-muted);
    }

    /* ═══════════════════════════════════════════════════
       STATUS SELECT — colour-coded
    ═══════════════════════════════════════════════════ */
    .cbm-status-open  { border-color: rgba(248,113,113,.5) !important; background: rgba(248,113,113,.06) !important; color: #f87171 !important; }
    .cbm-status-closed{ border-color: rgba(74,222,128,.5)  !important; background: rgba(74,222,128,.06)  !important; color: #4ade80 !important; }

    /* ═══════════════════════════════════════════════════
       UPLOAD ZONE
    ═══════════════════════════════════════════════════ */
    .cbm-upload-zone {
        border: 2px dashed var(--cbm-input-border);
        border-radius: .875rem;
        padding: 1.75rem 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        position: relative;
        overflow: hidden;
    }
    .cbm-upload-zone:hover {
        border-color: var(--cbm-blue);
        background: rgba(59,130,246,.04);
    }
    .cbm-upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .cbm-upload-icon {
        width: 2.5rem; height: 2.5rem;
        background: var(--cbm-input-bg);
        border-radius: .75rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto .875rem;
        color: var(--cbm-blue);
    }
    .cbm-upload-icon svg { width: 1.25rem; height: 1.25rem; }
    .cbm-upload-title { font-size: .875rem; font-weight: 700; color: var(--cbm-text); }
    .cbm-upload-sub   { font-size: .75rem; color: var(--cbm-text-muted); margin-top: .25rem; font-weight: 500; }
    .cbm-upload-badge {
        display: inline-flex; gap: .25rem; flex-wrap: wrap; justify-content: center;
        margin-top: .625rem;
    }
    .cbm-upload-badge span {
        font-size: .6875rem; font-weight: 700;
        background: var(--cbm-input-bg);
        color: var(--cbm-text-muted);
        padding: .15rem .5rem; border-radius: 999px;
        border: 1px solid var(--cbm-input-border);
    }

    /* ═══════════════════════════════════════════════════
       FLASH BANNERS (inside modals)
    ═══════════════════════════════════════════════════ */
    .cbm-flash {
        display: flex; align-items: flex-start; gap: .625rem;
        padding: .75rem 1rem;
        border-radius: .625rem;
        font-size: .8125rem;
        font-weight: 600;
        margin-bottom: 1rem;
        line-height: 1.4;
    }
    .cbm-flash svg { width: 1rem; height: 1rem; flex-shrink: 0; margin-top: .05rem; }
    .cbm-flash-success { background: rgba(74,222,128,.1); color: #4ade80; border: 1px solid rgba(74,222,128,.25); }
    .cbm-flash-error   { background: rgba(248,113,113,.1); color: #f87171; border: 1px solid rgba(248,113,113,.25); }

    /* ═══════════════════════════════════════════════════
       LOADING SPINNER
    ═══════════════════════════════════════════════════ */
    @keyframes cbm-spin { to { transform: rotate(360deg); } }
    .cbm-spinner {
        display: inline-block;
        width: .875rem; height: .875rem;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: white;
        border-radius: 50%;
        animation: cbm-spin .65s linear infinite;
        flex-shrink: 0;
    }

    /* ═══════════════════════════════════════════════════
       HOLD REASON ANIMATED SECTION
    ═══════════════════════════════════════════════════ */
    .cbm-hold-section {
        border-top: 1px solid var(--cbm-divider);
        padding-top: 1.125rem;
        margin-top: .5rem;
    }
    .cbm-hold-label {
        display: flex; align-items: center; gap: .4rem;
        font-size: .6875rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #f87171; margin-bottom: .875rem;
    }
    .cbm-hold-label::before {
        content: '';
        display: inline-block; width: .375rem; height: .375rem;
        background: #f87171; border-radius: 50%;
    }

    /* ═══════════════════════════════════════════════════
       ALPINE TRANSITIONS (global helpers)
    ═══════════════════════════════════════════════════ */
    [x-cloak] { display: none !important; }

    </style>
</head>
<body>
<div id="cbm-app" class="cbm-app">

    {{-- Mobile overlay --}}
    <div id="cbm-overlay" class="cbm-overlay" onclick="cbmCloseSidebar()"></div>

    {{-- ══════════ SIDEBAR ══════════ --}}
    @include('components.layouts.partials.sidebar')

    {{-- ══════════ MAIN ══════════ --}}
    <div class="cbm-main">

        {{-- Topbar --}}
        <header class="cbm-topbar">

            {{-- Hamburger --}}
            <button class="cbm-hamburger" onclick="cbmOpenSidebar()" aria-label="Open sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            {{-- Search --}}
            <div class="cbm-search">
                <div class="cbm-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>
                <input type="text" placeholder="Cari laporan, station...">
            </div>

            <div class="cbm-topbar-spacer"></div>

            {{-- Actions --}}
            <div class="cbm-topbar-actions">

                {{-- Theme Toggle --}}
                <button id="cbm-theme-btn" class="cbm-icon-btn" onclick="cbmToggleTheme()" aria-label="Toggle theme">
                    <svg id="cbm-app-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.772 17.303a.75.75 0 00-1.06 1.06l1.59 1.591a.75.75 0 001.061-1.06l-1.59-1.591zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.166 5.106a.75.75 0 011.06 1.06L5.636 7.756a.75.75 0 01-1.061-1.06l1.59-1.59z" />
                    </svg>
                    <svg id="cbm-app-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                        <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd" />
                    </svg>
                </button>

                {{-- Notifications --}}
                <div class="cbm-dropdown-container" x-data="{ open: false }">
                    <button class="cbm-icon-btn" @click="open = !open" @click.outside="open = false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0113.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 01-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 11-7.48 0 24.585 24.585 0 01-4.831-1.244.75.75 0 01-.298-1.205A8.217 8.217 0 005.25 9.75V9zm4.502 8.9a2.25 2.25 0 104.496 0 25.057 25.057 0 01-4.496 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="cbm-notif-dot"></span>
                    </button>
                    <div class="cbm-dropdown-menu" x-show="open" style="display: none; right: -2rem;" x-transition>
                        <div class="cbm-dropdown-header">Notifikasi Terbaru</div>
                        <div class="cbm-dropdown-divider"></div>
                        <div style="padding: 1rem 0.5rem; text-align: center; color: var(--cbm-text-muted); font-size: 0.8125rem;">
                            Tidak ada notifikasi baru
                        </div>
                        <div class="cbm-dropdown-divider"></div>
                        <a href="{{ route('notifications.index') }}" class="cbm-dropdown-item" style="justify-content: center; color: var(--cbm-blue);">
                            Lihat Semua Notifikasi
                        </a>
                    </div>
                </div>

                {{-- User --}}
                <div class="cbm-dropdown-container" x-data="{ open: false }">
                    <div class="cbm-topbar-user" @click="open = !open" @click.outside="open = false" style="cursor:pointer;">
                        <div class="cbm-topbar-avatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                        </div>
                        <span class="cbm-topbar-uname">{{ explode(' ', auth()->user()->name ?? 'User')[0] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.53 16.28a.75.75 0 01-1.06 0l-7.5-7.5a.75.75 0 011.06-1.06L12 14.69l6.97-6.97a.75.75 0 111.06 1.06l-7.5 7.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="cbm-dropdown-menu" x-show="open" style="display: none;" x-transition>
                        <div class="cbm-dropdown-header">Akun Saya</div>
                        <div class="cbm-dropdown-divider"></div>
                        <a href="{{ route('profile.index') }}" class="cbm-dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            Profil & Pengaturan
                        </a>
                        <button onclick="event.preventDefault(); document.getElementById('cbm-logout-form').submit();" class="cbm-dropdown-item" style="color: var(--cbm-red);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color: var(--cbm-red);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                            </svg>
                            Log Out
                        </button>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="cbm-content">
            {{ $slot }}
            
            {{-- Footer --}}
            <footer style="text-align: center; padding: 3rem 0 1rem; margin-top: auto; color: var(--cbm-text-muted); font-size: 0.8125rem; font-weight: 500;">
                &copy; {{ date('Y') }} copyright by chairul anwar
            </footer>
        </main>

    </div>
</div>

@livewireScripts

@include('components.layouts.partials.scripts')
</body>
</html>
