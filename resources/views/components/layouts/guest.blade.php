<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cabin Core — Aviation Maintenance Management System">
    <title>{{ $title ?? 'Cabin Core — Login' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/lion-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --g-bg:      #0a0e1a;
        --g-card:    rgba(15,20,35,.90);
        --g-border:  rgba(255,255,255,.10);
        --g-text:    #f1f5f9;
        --g-muted:   rgba(255,255,255,.50);
        --g-input:   rgba(255,255,255,.07);
        --g-input-b: rgba(255,255,255,.12);
        --g-blue:    #3b82f6;
        --g-indigo:  #6366f1;
        --g-purple:  #a78bfa;
    }
    html, body { height: 100%; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--g-bg); color: var(--g-text); display: flex; min-height: 100vh; overflow-x: hidden; }
    .g-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
    .g-bg::before { content:''; position:absolute; width:800px; height:800px; background:radial-gradient(circle,rgba(99,102,241,.18) 0%,transparent 65%); top:-200px; left:-200px; animation:g-float 12s ease-in-out infinite; }
    .g-bg::after  { content:''; position:absolute; width:700px; height:700px; background:radial-gradient(circle,rgba(59,130,246,.14) 0%,transparent 65%); bottom:-200px; right:-150px; animation:g-float 16s ease-in-out infinite reverse; }
    .g-blob3 { position:absolute; width:600px; height:600px; background:radial-gradient(circle,rgba(167,139,250,.10) 0%,transparent 65%); top:40%; left:40%; transform:translate(-50%,-50%); animation:g-float 20s ease-in-out infinite 4s; }
    @keyframes g-float { 0%,100%{transform:translateY(0) scale(1);} 50%{transform:translateY(-40px) scale(1.05);} }
    .g-grid { position:fixed; inset:0; z-index:0; background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px); background-size:40px 40px; }
    .g-wrap { position:relative; z-index:1; display:flex; width:100%; min-height:100vh; }
    .g-left { display:none; flex-direction:column; justify-content:space-between; width:45%; min-height:100vh; padding:3rem; background:linear-gradient(135deg,rgba(59,130,246,.12) 0%,rgba(99,102,241,.08) 50%,rgba(167,139,250,.12) 100%); border-right:1px solid var(--g-border); position:relative; overflow:hidden; }
    @media(min-width:1024px){.g-left{display:flex;}}
    .g-left-glow { position:absolute; width:500px; height:500px; border-radius:50%; background:radial-gradient(circle,rgba(99,102,241,.20) 0%,transparent 65%); top:20%; left:-100px; pointer-events:none; }
    .g-brand { display:flex; align-items:center; gap:.875rem; position:relative; z-index:1; }
    .g-brand-logo { width:3rem; height:3rem; border-radius:.875rem; background:linear-gradient(135deg,var(--g-blue),var(--g-indigo)); display:flex; align-items:center; justify-content:center; box-shadow:0 8px 24px rgba(59,130,246,.35); overflow:hidden; }
    .g-brand-logo img { width:100%; height:100%; object-fit:contain; }
    .g-brand-name { font-size:1.125rem; font-weight:800; color:var(--g-text); letter-spacing:-.01em; }
    .g-brand-sub  { font-size:.75rem; color:var(--g-muted); font-weight:500; margin-top:.1rem; }
    .g-hero { position:relative; z-index:1; }
    .g-hero-badge { display:inline-flex; align-items:center; gap:.5rem; background:rgba(59,130,246,.15); border:1px solid rgba(59,130,246,.25); color:#60a5fa; font-size:.75rem; font-weight:700; padding:.375rem .875rem; border-radius:999px; margin-bottom:1.5rem; letter-spacing:.04em; text-transform:uppercase; }
    .g-hero-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:#60a5fa; animation:g-pulse 2s ease-in-out infinite; }
    @keyframes g-pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }
    .g-hero-title { font-size:clamp(2rem,3.5vw,2.75rem); font-weight:800; color:var(--g-text); letter-spacing:-.03em; line-height:1.15; margin-bottom:1.25rem; }
    .g-hero-title span { background:linear-gradient(90deg,#60a5fa,#a78bfa,#f472b6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .g-hero-desc { font-size:.9375rem; color:var(--g-muted); line-height:1.7; font-weight:500; max-width:420px; margin-bottom:2rem; }
    .g-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; }
    .g-stat-card { background:rgba(255,255,255,.05); border:1px solid var(--g-border); border-radius:.875rem; padding:1rem; text-align:center; }
    .g-stat-num { font-size:1.5rem; font-weight:800; color:var(--g-text); letter-spacing:-.02em; }
    .g-stat-lbl { font-size:.7rem; color:var(--g-muted); font-weight:700; margin-top:.25rem; text-transform:uppercase; letter-spacing:.06em; }
    .g-footer { position:relative; z-index:1; font-size:.75rem; color:var(--g-muted); font-weight:500; }
    .g-footer span { color:var(--g-text); font-weight:700; }
    .g-right { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1.5rem; min-height:100vh; }
    .g-form-wrap { width:100%; max-width:420px; }
    .g-mobile-brand { display:flex; flex-direction:column; align-items:center; margin-bottom:2.5rem; }
    @media(min-width:1024px){.g-mobile-brand{display:none;}}
    .g-mobile-logo { width:3.5rem; height:3.5rem; border-radius:1rem; overflow:hidden; margin-bottom:.875rem; box-shadow:0 8px 24px rgba(59,130,246,.3); background:linear-gradient(135deg,var(--g-blue),var(--g-indigo)); display:flex; align-items:center; justify-content:center; }
    .g-mobile-logo img { width:100%; height:100%; object-fit:contain; }
    .g-card { background:var(--g-card); border:1px solid var(--g-border); border-radius:1.375rem; padding:2rem; backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); box-shadow:0 25px 60px rgba(0,0,0,.35); }
    .g-copyright { text-align:center; font-size:.75rem; color:var(--g-muted); font-weight:500; margin-top:1.5rem; }
    .g-copyright span { color:var(--g-text); font-weight:700; }

    /* Form field utilities used by login.blade.php */
    .g-form-title { font-size:1.625rem; font-weight:800; color:var(--g-text); letter-spacing:-.025em; line-height:1.2; margin-bottom:.5rem; text-align:center; }
    .g-form-sub { font-size:.875rem; color:var(--g-muted); font-weight:500; text-align:center; margin-bottom:2rem; }
    .g-input-group { margin-bottom:1.25rem; }
    .g-label { display:block; font-size:.8125rem; font-weight:700; color:var(--g-muted); margin-bottom:.4rem; letter-spacing:.01em; }
    .g-input-wrap { position:relative; }
    .g-input-icon { position:absolute; left:.875rem; top:50%; transform:translateY(-50%); color:rgba(255,255,255,.25); pointer-events:none; }
    .g-input-icon svg { width:1rem; height:1rem; }
    .g-input { width:100%; background:var(--g-input); border:1px solid var(--g-input-b); border-radius:.75rem; padding:.75rem .875rem .75rem 2.625rem; font-family:inherit; font-size:.9375rem; color:var(--g-text); outline:none; transition:border-color .2s,box-shadow .2s,background .2s; -webkit-appearance:none; }
    .g-input::placeholder { color:rgba(255,255,255,.20); }
    .g-input:focus { border-color:var(--g-blue); box-shadow:0 0 0 3px rgba(59,130,246,.18); background:rgba(59,130,246,.05); }
    .g-error-msg { font-size:.75rem; font-weight:600; color:#f87171; margin-top:.375rem; display:block; }
    .g-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .g-check-label { display:flex; align-items:center; gap:.5rem; font-size:.8125rem; font-weight:600; color:var(--g-muted); cursor:pointer; }
    .g-check-label input[type="checkbox"] { width:1rem; height:1rem; border-radius:.25rem; cursor:pointer; accent-color:var(--g-blue); }
    .g-forgot { font-size:.8125rem; font-weight:700; color:var(--g-blue); text-decoration:none; transition:color .15s; }
    .g-forgot:hover { color:#60a5fa; }
    .g-submit { width:100%; background:linear-gradient(135deg,var(--g-blue),var(--g-indigo)); color:white; border:none; border-radius:.875rem; padding:.875rem 1.5rem; font-family:inherit; font-size:.9375rem; font-weight:700; cursor:pointer; box-shadow:0 8px 24px rgba(59,130,246,.4); transition:all .25s cubic-bezier(.4,0,.2,1); display:flex; align-items:center; justify-content:center; gap:.5rem; letter-spacing:.01em; position:relative; overflow:hidden; }
    .g-submit:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(59,130,246,.5); }
    .g-submit:active { transform:translateY(0); }
    .g-submit svg { width:1.125rem; height:1.125rem; }
    .g-status { padding:.75rem 1rem; background:rgba(74,222,128,.1); border:1px solid rgba(74,222,128,.25); border-radius:.75rem; color:#4ade80; font-size:.8125rem; font-weight:600; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
    .g-err-alert { padding:.75rem 1rem; background:rgba(248,113,113,.1); border:1px solid rgba(248,113,113,.25); border-radius:.75rem; color:#f87171; font-size:.8125rem; font-weight:600; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
    </style>
</head>
<body style="margin:0;padding:0;background:#090d1a;">
    {{ $slot }}
    @livewireScripts
    @stack('scripts')
</body>
</html>