<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Cabin Core — Aviation Maintenance Management System">

    <title>Cabin Core — Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #090d1a;
            --bg2:      #0d1220;
            --card:     rgba(13,18,32,0.92);
            --border:   rgba(255,255,255,0.09);
            --text:     #f1f5f9;
            --muted:    rgba(255,255,255,0.45);
            --input:    rgba(255,255,255,0.06);
            --input-b:  rgba(255,255,255,0.10);
            --blue:     #3b82f6;
            --indigo:   #6366f1;
            --rose:     #e11d48;
        }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        /* ── ANIMATED BACKGROUND ── */
        .g-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: var(--bg);
        }
        .g-bg::before {
            content: '';
            position: absolute;
            width: 900px; height: 900px;
            background: radial-gradient(circle, rgba(99,102,241,.15) 0%, transparent 65%);
            top: -250px; left: -250px;
            animation: g-float 14s ease-in-out infinite;
        }
        .g-bg::after {
            content: '';
            position: absolute;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(225,29,72,.10) 0%, transparent 65%);
            bottom: -200px; right: -150px;
            animation: g-float 18s ease-in-out infinite reverse;
        }
        .g-blob3 {
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,.10) 0%, transparent 65%);
            top: 45%; left: 55%; transform: translate(-50%, -50%);
            animation: g-float 22s ease-in-out infinite 5s;
        }
        @keyframes g-float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-40px) scale(1.06); }
        }
        .g-grid {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }

        /* ── LAYOUT ── */
        .g-wrap {
            position: relative; z-index: 1;
            display: flex; width: 100%; min-height: 100vh;
        }

        /* ── LEFT PANEL ── */
        .g-left {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 48%;
            min-height: 100vh;
            padding: 2.5rem 3rem;
            background: linear-gradient(140deg, rgba(99,102,241,.12) 0%, rgba(59,130,246,.06) 50%, rgba(225,29,72,.08) 100%);
            border-right: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 1024px) { .g-left { display: flex; } }

        .g-left-glow {
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.18) 0%, transparent 65%);
            top: 25%; left: -150px;
            pointer-events: none;
        }

        /* ── BRAND ── */
        .g-brand {
            display: flex; align-items: center; gap: .875rem;
            position: relative; z-index: 1;
        }
        .g-brand-logo {
            width: 3rem; height: 3rem;
            border-radius: .875rem;
            background: linear-gradient(135deg, var(--rose), #9f1239);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(225,29,72,.35);
            font-size: 1.25rem; font-weight: 900; color: white;
            overflow: hidden; flex-shrink: 0;
        }
        .g-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .g-brand-name { font-size: 1.125rem; font-weight: 800; letter-spacing: -.015em; }
        .g-brand-sub  { font-size: .75rem; color: var(--muted); font-weight: 500; margin-top: .1rem; }

        /* ── HERO ── */
        .g-hero { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 2rem 0; }
        .g-hero-badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(225,29,72,.12); border: 1px solid rgba(225,29,72,.22);
            color: #fb7185; font-size: .7rem; font-weight: 700;
            padding: .35rem .875rem; border-radius: 999px;
            margin-bottom: 1.5rem; letter-spacing: .06em; text-transform: uppercase;
        }
        .g-hero-badge::before {
            content: ''; width: 6px; height: 6px;
            border-radius: 50%; background: #fb7185;
            animation: g-pulse 2s ease-in-out infinite;
        }
        @keyframes g-pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

        h1.g-hero-title {
            font-size: clamp(2rem, 3vw, 2.75rem);
            font-weight: 900; letter-spacing: -.035em; line-height: 1.1;
            margin-bottom: 1.25rem; color: var(--text);
        }
        h1.g-hero-title span {
            background: linear-gradient(90deg, #fb7185, #c084fc, #60a5fa);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .g-hero-desc {
            font-size: .9375rem; color: var(--muted); line-height: 1.7;
            max-width: 400px; margin-bottom: 2.5rem; font-weight: 500;
        }

        /* ── STATS ── */
        .g-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: .875rem; }
        .g-stat-card {
            background: rgba(255,255,255,.05); border: 1px solid var(--border);
            border-radius: .875rem; padding: 1rem; text-align: center;
            transition: background .2s;
        }
        .g-stat-card:hover { background: rgba(255,255,255,.08); }
        .g-stat-num { font-size: 1.625rem; font-weight: 900; letter-spacing: -.03em; }
        .g-stat-lbl { font-size: .65rem; color: var(--muted); font-weight: 700; margin-top: .2rem; text-transform: uppercase; letter-spacing: .08em; }

        /* ── MODULE PILLS ── */
        .g-modules {
            display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.75rem;
        }
        .g-module-pill {
            display: flex; align-items: center; gap: .4rem;
            padding: .3rem .75rem;
            background: rgba(255,255,255,.05); border: 1px solid var(--border);
            border-radius: 999px; font-size: .7rem; font-weight: 600; color: var(--muted);
        }
        .g-module-dot { width: .4rem; height: .4rem; border-radius: 50%; flex-shrink: 0; }

        /* ── FOOTER ── */
        .g-footer { position: relative; z-index: 1; font-size: .75rem; color: var(--muted); font-weight: 500; }
        .g-footer span { color: var(--text); font-weight: 700; }

        /* ── RIGHT PANEL ── */
        .g-right {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 2rem 1.5rem; min-height: 100vh;
        }
        .g-form-wrap { width: 100%; max-width: 420px; }

        /* Mobile brand */
        .g-mobile-brand {
            display: flex; flex-direction: column; align-items: center;
            margin-bottom: 2rem; gap: .625rem;
        }
        @media (min-width: 1024px) { .g-mobile-brand { display: none; } }
        .g-mobile-logo {
            width: 3.5rem; height: 3.5rem; border-radius: 1rem; overflow: hidden;
            background: linear-gradient(135deg, var(--rose), #9f1239);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(225,29,72,.3);
            font-size: 1.5rem; font-weight: 900; color: white;
        }
        .g-mobile-logo img { width: 100%; height: 100%; object-fit: contain; }

        /* ── FORM CARD ── */
        .g-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1.375rem;
            padding: 2.25rem 2rem;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 25px 60px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.04);
            position: relative; overflow: hidden;
        }
        .g-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent);
        }

        /* ── FORM ELEMENTS ── */
        .g-form-title {
            font-size: 1.625rem; font-weight: 800;
            letter-spacing: -.03em; line-height: 1.15;
            color: var(--text); text-align: center; margin-bottom: .375rem;
        }
        .g-form-sub {
            font-size: .875rem; color: var(--muted);
            text-align: center; margin-bottom: 2rem; font-weight: 500;
        }
        .g-input-group { margin-bottom: 1.125rem; }
        .g-label {
            display: block; font-size: .8rem; font-weight: 700;
            color: var(--muted); margin-bottom: .375rem; letter-spacing: .01em;
        }
        .g-input-wrap { position: relative; }
        .g-input-icon {
            position: absolute; left: .875rem; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,.22); pointer-events: none;
        }
        .g-input-icon svg { width: 1rem; height: 1rem; display: block; }
        .g-input {
            width: 100%;
            background: var(--input); border: 1px solid var(--input-b);
            border-radius: .75rem;
            padding: .8rem .875rem .8rem 2.625rem;
            font-family: inherit; font-size: .9375rem; color: var(--text);
            outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
            -webkit-appearance: none;
        }
        .g-input::placeholder { color: rgba(255,255,255,.18); }
        .g-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
            background: rgba(59,130,246,.04);
        }
        .g-input.g-input-error { border-color: rgba(248,113,113,.5); }
        .g-error-msg { font-size: .75rem; font-weight: 600; color: #f87171; margin-top: .375rem; display: block; }

        .g-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .g-check-label {
            display: flex; align-items: center; gap: .4rem;
            font-size: .8125rem; font-weight: 600; color: var(--muted); cursor: pointer;
        }
        .g-check-label input[type="checkbox"] {
            width: 1rem; height: 1rem; border-radius: .25rem;
            cursor: pointer; accent-color: var(--blue);
        }
        .g-forgot {
            font-size: .8125rem; font-weight: 700; color: var(--blue);
            text-decoration: none; transition: color .15s;
        }
        .g-forgot:hover { color: #93c5fd; }

        .g-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--rose), #9f1239);
            color: white; border: none; border-radius: .875rem;
            padding: .9rem 1.5rem;
            font-family: inherit; font-size: .9375rem; font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(225,29,72,.35);
            transition: all .25s cubic-bezier(.4,0,.2,1);
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            letter-spacing: .01em;
        }
        .g-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(225,29,72,.5); }
        .g-submit:active { transform: translateY(0); box-shadow: 0 6px 18px rgba(225,29,72,.4); }
        .g-submit svg { width: 1.125rem; height: 1.125rem; }

        /* Alerts */
        .g-status {
            padding: .75rem 1rem;
            background: rgba(74,222,128,.08); border: 1px solid rgba(74,222,128,.2);
            border-radius: .75rem; color: #4ade80;
            font-size: .8125rem; font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .g-status svg, .g-err-alert svg { width: 1rem; height: 1rem; flex-shrink: 0; }
        .g-err-alert {
            padding: .75rem 1rem;
            background: rgba(248,113,113,.08); border: 1px solid rgba(248,113,113,.2);
            border-radius: .75rem; color: #f87171;
            font-size: .8125rem; font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .5rem;
        }

        /* Copyright */
        .g-copyright {
            text-align: center; font-size: .75rem;
            color: var(--muted); font-weight: 500; margin-top: 1.5rem;
        }
        .g-copyright span { color: var(--text); font-weight: 700; }

        /* Divider between fields */
        .g-divider { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }
    </style>
</head>
<body>
    <div class="g-bg"><div class="g-blob3"></div></div>
    <div class="g-grid"></div>

    <div class="g-wrap">

        {{-- LEFT PANEL --}}
        <div class="g-left">
            <div class="g-left-glow"></div>

            {{-- Brand --}}
            <div class="g-brand">
                <div class="g-brand-logo">
                    @if(file_exists(public_path('images/lion-logo.png')))
                        <img src="{{ asset('images/lion-logo.png') }}" alt="Cabin Core">
                    @else
                        C
                    @endif
                </div>
                <div>
                    <div class="g-brand-name">Cabin Core</div>
                    <div class="g-brand-sub">Batam Aero Technic</div>
                </div>
            </div>

            {{-- Hero --}}
            <div class="g-hero">
                <div class="g-hero-badge">Aviation MMS</div>
                <h1 class="g-hero-title">
                    Manage Your<br>
                    Cabin Maintenance<br>
                    <span>Smarter &amp; Faster</span>
                </h1>
                <p class="g-hero-desc">
                    Platform terpadu untuk monitoring, pelaporan, dan pengelolaan pekerjaan perawatan kabin pesawat secara <strong style="color: var(--text);">real-time</strong> di seluruh stasiun.
                </p>

                {{-- Stats --}}
                <div class="g-stats">
                    <div class="g-stat-card">
                        <div class="g-stat-num">7</div>
                        <div class="g-stat-lbl">Modules</div>
                    </div>
                    <div class="g-stat-card">
                        <div class="g-stat-num">18+</div>
                        <div class="g-stat-lbl">Airports</div>
                    </div>
                    <div class="g-stat-card">
                        <div class="g-stat-num">24/7</div>
                        <div class="g-stat-lbl">Monitoring</div>
                    </div>
                </div>

                {{-- Module pills --}}
                <div class="g-modules">
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#3b82f6;"></div>WO Log</div>
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#8b5cf6;"></div>NSRDI</div>
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#ec4899;"></div>DMI</div>
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#14b8a6;"></div>CML Log</div>
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#f59e0b;"></div>Cleaning</div>
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#ef4444;"></div>ICT Finding</div>
                    <div class="g-module-pill"><div class="g-module-dot" style="background:#22c55e;"></div>Summary</div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="g-footer">&copy; {{ date('Y') }} <span>Batam Aero Technic</span>. All rights reserved.</div>
        </div>

        {{-- RIGHT PANEL --}}
        <div class="g-right">
            <div class="g-form-wrap">

                {{-- Mobile-only brand --}}
                <div class="g-mobile-brand">
                    <div class="g-mobile-logo">
                        @if(file_exists(public_path('images/lion-logo.png')))
                            <img src="{{ asset('images/lion-logo.png') }}" alt="Cabin Core">
                        @else
                            C
                        @endif
                    </div>
                    <div class="g-brand-name" style="text-align:center;">Cabin Core</div>
                    <div class="g-brand-sub" style="text-align:center;">Batam Aero Technic</div>
                </div>

                {{ $slot }}

                <p class="g-copyright">&copy; {{ date('Y') }} <span>Cabin Core</span> — Built with care.</p>
            </div>
        </div>

    </div>
</body>
</html>
