<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cabin Core — Cabin Maintenance System</title>
    <meta name="description" content="Platform terpadu untuk monitoring, pelaporan, dan pengelolaan pekerjaan perawatan kabin pesawat secara real-time di seluruh stasiun.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #090d1a;
            --bg2:       #0d1220;
            --surface:   rgba(255,255,255,0.04);
            --border:    rgba(255,255,255,0.08);
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --accent:    #e11d48;
            --accent2:   #9333ea;
            --blue:      #3b82f6;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── ANIMATED BACKGROUND ── */
        .hero-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: var(--bg);
            overflow: hidden;
        }
        .hero-bg::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(225,29,72,0.12) 0%, transparent 65%);
            border-radius: 50%;
            animation: pulse-glow 8s ease-in-out infinite alternate;
        }
        .hero-bg::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(59,130,246,0.10) 0%, transparent 65%);
            border-radius: 50%;
            animation: pulse-glow 10s ease-in-out infinite alternate-reverse;
        }
        @keyframes pulse-glow {
            0%   { opacity: 0.6; transform: scale(1); }
            100% { opacity: 1;   transform: scale(1.15); }
        }

        /* ── GRID OVERLAY ── */
        .grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* ── LAYOUT ── */
        .page-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAV ── */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(20px);
            background: rgba(9,13,26,0.8);
            border-bottom: 1px solid var(--border);
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text);
        }
        .nav-logo {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, var(--accent), #9f1239);
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            font-weight: 900;
            color: white;
            box-shadow: 0 4px 15px rgba(225,29,72,0.35);
            flex-shrink: 0;
        }
        .nav-title { font-weight: 800; font-size: 1.125rem; letter-spacing: -0.025em; }
        .nav-sub { font-size: 0.7rem; color: var(--muted); font-weight: 500; }
        .nav-actions { display: flex; gap: 0.75rem; align-items: center; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 0.625rem;
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.875rem; font-weight: 500;
            text-decoration: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-ghost:hover { border-color: rgba(255,255,255,0.2); color: var(--text); background: var(--surface); }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1.5rem;
            border-radius: 0.625rem;
            border: none;
            color: white;
            font-size: 0.875rem; font-weight: 600;
            text-decoration: none;
            background: linear-gradient(135deg, var(--accent), #9f1239);
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(225,29,72,0.3);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(225,29,72,0.45); }

        /* ── HERO ── */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5rem 2rem 4rem;
            text-align: center;
        }
        .hero-inner { max-width: 800px; width: 100%; }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            background: rgba(225,29,72,0.12);
            border: 1px solid rgba(225,29,72,0.25);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fb7185;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1.75rem;
        }
        .hero-badge span.dot {
            width: 0.375rem; height: 0.375rem;
            border-radius: 50%;
            background: #fb7185;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: var(--text);
            margin-bottom: 1rem;
        }
        h1 .grad {
            background: linear-gradient(135deg, #fb7185 0%, #e11d48 40%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-desc {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--muted);
            line-height: 1.7;
            max-width: 580px;
            margin: 0 auto 2.5rem;
        }
        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 4rem;
        }
        .btn-hero-primary {
            display: inline-flex; align-items: center; gap: 0.75rem;
            padding: 0.875rem 2rem;
            border-radius: 0.875rem;
            border: none;
            color: white;
            font-size: 1rem; font-weight: 700;
            text-decoration: none;
            background: linear-gradient(135deg, var(--accent), #9f1239);
            transition: all 0.25s;
            box-shadow: 0 6px 25px rgba(225,29,72,0.35);
        }
        .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 35px rgba(225,29,72,0.5); }
        .btn-hero-ghost {
            display: inline-flex; align-items: center; gap: 0.75rem;
            padding: 0.875rem 2rem;
            border-radius: 0.875rem;
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--text);
            font-size: 1rem; font-weight: 600;
            text-decoration: none;
            background: rgba(255,255,255,0.04);
            transition: all 0.25s;
            backdrop-filter: blur(10px);
        }
        .btn-hero-ghost:hover { border-color: rgba(255,255,255,0.25); background: rgba(255,255,255,0.08); transform: translateY(-2px); }

        /* ── STATS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: 1.25rem;
            overflow: hidden;
            border: 1px solid var(--border);
            max-width: 560px;
            margin: 0 auto;
        }
        .stat-item {
            background: var(--bg2);
            padding: 1.5rem 1rem;
            text-align: center;
            transition: background 0.2s;
        }
        .stat-item:hover { background: rgba(255,255,255,0.05); }
        .stat-val {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -0.04em;
            color: var(--text);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 0.25rem;
        }

        /* ── FEATURES ── */
        .features {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        .section-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 0.75rem;
        }
        .section-title {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            text-align: center;
            margin-bottom: 0.75rem;
        }
        .section-desc {
            font-size: 1rem;
            color: var(--muted);
            text-align: center;
            max-width: 500px;
            margin: 0 auto 3.5rem;
            line-height: 1.6;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }
        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 1.75rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
        }
        .feature-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.12);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .feature-icon {
            width: 2.75rem; height: 2.75rem;
            border-radius: 0.75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .feature-title { font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .feature-desc { font-size: 0.875rem; color: var(--muted); line-height: 1.6; }

        /* ── MODULES ── */
        .modules-section {
            padding: 0 2rem 5rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        .module-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.875rem;
        }
        .module-pill {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 1rem 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            transition: all 0.2s;
        }
        .module-pill:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.12);
            transform: translateY(-2px);
        }
        .module-dot {
            width: 0.5rem; height: 0.5rem;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .module-name { font-size: 0.875rem; font-weight: 600; }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid var(--border);
            padding: 1.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            background: rgba(9,13,26,0.6);
            backdrop-filter: blur(20px);
        }
        footer p { font-size: 0.8125rem; color: var(--muted); }
        footer p b { color: var(--text); font-weight: 600; }

        /* ── RESPONSIVE ── */
        @media (max-width: 640px) {
            nav { padding: 0.875rem 1.25rem; }
            .nav-sub { display: none; }
            .hero { padding: 3rem 1.25rem 3rem; }
            .features, .modules-section { padding-left: 1.25rem; padding-right: 1.25rem; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .hero-cta { flex-direction: column; align-items: center; }
            footer { flex-direction: column; align-items: center; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="hero-bg"></div>
    <div class="grid-overlay"></div>

    <div class="page-wrap">
        <!-- NAV -->
        <nav>
            <a href="/" class="nav-brand">
                <div class="nav-logo">C</div>
                <div>
                    <div class="nav-title">Cabin Core</div>
                    <div class="nav-sub">Batam Aero Technic</div>
                </div>
            </a>
            <div class="nav-actions">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard →</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">Masuk</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary">Daftar Sekarang</a>
                        @endif
                    @endauth
                @endif
            </div>
        </nav>

        <!-- HERO -->
        <section class="hero">
            <div class="hero-inner">
                <div class="hero-badge">
                    <span class="dot"></span>
                    Aviation MMS — Aktif 24/7
                </div>

                <h1>
                    Manage Your<br>
                    Cabin Maintenance<br>
                    <span class="grad">Smarter &amp; Faster</span>
                </h1>

                <p class="hero-desc">
                    Platform terpadu untuk monitoring, pelaporan, dan pengelolaan pekerjaan perawatan kabin pesawat secara <strong style="color: var(--text);">real-time</strong> di seluruh stasiun.
                </p>

                <div class="hero-cta">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-hero-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                                Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-hero-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                                Masuk ke Sistem
                            </a>
                            <a href="#features" class="btn-hero-ghost">
                                Pelajari Fitur
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                            </a>
                        @endauth
                    @endif
                </div>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-val">7</div>
                        <div class="stat-label">Modules</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val">18+</div>
                        <div class="stat-label">Airports</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val">24/7</div>
                        <div class="stat-label">Monitoring</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="features" id="features">
            <div class="section-label">Fitur Utama</div>
            <h2 class="section-title">Semua yang Anda Butuhkan</h2>
            <p class="section-desc">Dari Work Order hingga laporan eksekutif — semua dalam satu platform yang dirancang untuk efisiensi operasional penerbangan.</p>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(59,130,246,0.12);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#3b82f6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                    </div>
                    <div class="feature-title">Work Order (WO) Log</div>
                    <div class="feature-desc">Pencatatan dan pelacakan work order cabin maintenance secara menyeluruh dengan status real-time dan manajemen operator.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(139,92,246,0.12);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#8b5cf6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" /></svg>
                    </div>
                    <div class="feature-title">NSRDI &amp; DMI Log</div>
                    <div class="feature-desc">Sistem pencatatan NSRDI dan DMI terintegrasi lengkap dengan due date tracking dan manajemen status overdue.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(20,184,166,0.12);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#14b8a6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="feature-title">CML Log</div>
                    <div class="feature-desc">Pencatatan Cabin Maintenance Log (CML) dengan filter tanggal, ekspor data, dan laporan per stasiun secara otomatis.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(236,72,153,0.12);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#ec4899"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                    </div>
                    <div class="feature-title">Executive Summary</div>
                    <div class="feature-desc">Dashboard KPI eksekutif dengan analitik harian, mingguan, dan bulanan. Dilengkapi Pareto analysis dan station performance tracking.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(245,158,11,0.12);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#f59e0b"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                    </div>
                    <div class="feature-title">Aircraft Cleaning</div>
                    <div class="feature-desc">Manajemen pembersihan pesawat (General, Interior, Exterior) dengan shift tracking, status workflow, dan laporan per registrasi.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(239,68,68,0.12);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#ef4444"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" /></svg>
                    </div>
                    <div class="feature-title">ICT Finding</div>
                    <div class="feature-desc">Pencatatan temuan In-Cabin Technician (ICT) dengan tracking status open/closed dan histori per pesawat dan operator.</div>
                </div>
            </div>
        </section>

        <!-- MODULES -->
        <section class="modules-section">
            <div class="section-label">Modul Sistem</div>
            <h2 class="section-title" style="margin-bottom: 2rem;">7 Modul Operasional</h2>
            <div class="module-list">
                <div class="module-pill">
                    <div class="module-dot" style="background: #3b82f6;"></div>
                    <span class="module-name">WO Log</span>
                </div>
                <div class="module-pill">
                    <div class="module-dot" style="background: #8b5cf6;"></div>
                    <span class="module-name">NSRDI Log</span>
                </div>
                <div class="module-pill">
                    <div class="module-dot" style="background: #ec4899;"></div>
                    <span class="module-name">DMI Log</span>
                </div>
                <div class="module-pill">
                    <div class="module-dot" style="background: #14b8a6;"></div>
                    <span class="module-name">CML Log</span>
                </div>
                <div class="module-pill">
                    <div class="module-dot" style="background: #f59e0b;"></div>
                    <span class="module-name">Aircraft Cleaning</span>
                </div>
                <div class="module-pill">
                    <div class="module-dot" style="background: #ef4444;"></div>
                    <span class="module-name">ICT Finding</span>
                </div>
                <div class="module-pill">
                    <div class="module-dot" style="background: #22c55e;"></div>
                    <span class="module-name">Summary Report</span>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer>
            <p>&copy; {{ date('Y') }} <b>Cabin Core</b> — Batam Aero Technic. All rights reserved.</p>
            <p style="font-size: 0.75rem;">Cabin Maintenance System v{{ app()->version() }}</p>
        </footer>
    </div>
</body>
</html>
