@push('styles')
<style>
/* ============================================================
   CABIN CORE — Force Password Reset (Modern Enterprise Theme)
   Scoped under .cl to avoid CSS collisions
============================================================ */

.cl {
    --bg: #070b19;
    --panel: #0b1022;
    --card-bg: rgba(255, 255, 255, 0.04);
    --card-border: rgba(255, 255, 255, 0.08);
    --txt: #f1f5f9;
    --txt-heading: #ffffff;
    --muted: rgba(255, 255, 255, 0.48);
    --input-bg: rgba(255, 255, 255, 0.06);
    --input-bd: rgba(255, 255, 255, 0.12);
    --blue: #3b82f6;
    --indigo: #6366f1;
    --amber: #f59e0b;
    --emerald: #10b981;
    --red: #f87171;

    position: fixed; inset: 0;
    display: flex;
    background: var(--bg);
    color: var(--txt);
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    overflow: hidden;
}

/* --- Blobs --- */
.cl-blob { position: absolute; border-radius: 50%; filter: blur(100px); pointer-events: none; animation: clFloat 14s ease-in-out infinite; }
.cl-b1 { width: 34rem; height: 34rem; opacity: .14; background: radial-gradient(circle, #3b82f6, #1e40af); top: -140px; left: -140px; }
.cl-b2 { width: 28rem; height: 28rem; opacity: .10; background: radial-gradient(circle, #6366f1, #4338ca); bottom: -80px; right: 15%; animation-delay: -5s; }
.cl-b3 { width: 22rem; height: 22rem; opacity: .08; background: radial-gradient(circle, #0ea5e9, #0284c7); top: 35%; right: -60px; animation-delay: -8s; }
@keyframes clFloat { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(-26px) scale(1.05)} }

/* --- Theme toggle --- */
.cl-theme {
    position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999;
    width: 2.5rem; height: 2.5rem;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,.14); border-radius: 50%;
    background: rgba(255,255,255,.07); color: rgba(255,255,255,.7);
    backdrop-filter: blur(12px); cursor: pointer;
    transition: all .2s ease; padding: 0;
}
.cl-theme:hover { background: rgba(255,255,255,.16); color: #fff; transform: scale(1.05); }

/* --- Layout --- */
.cl-wrap { position: relative; z-index: 1; display: flex; width: 100%; height: 100%; }

/* --- Left Hero Panel (Desktop) --- */
.cl-left {
    display: none; flex-direction: column; width: 48%; position: relative; overflow: hidden;
    background-color: #0b1329; background-size: cover; background-position: center;
}
.cl-left::before {
    content: ''; position: absolute; inset: 0; z-index: 0;
    background: linear-gradient(155deg, rgba(7,11,25,.94) 0%, rgba(20,35,80,.65) 50%, rgba(7,11,25,.96) 100%);
}
@media(min-width:1024px) { .cl-left { display: flex; } }
.cl-left-in {
    display: flex; flex-direction: column; justify-content: space-between;
    height: 100%; padding: 2.75rem 3rem; position: relative; z-index: 1;
    overflow-y: auto; overflow-x: hidden;
}

/* Brand */
.cl-brand { display: flex; align-items: center; gap: .75rem; }
.cl-brand-logo {
    width: 2.75rem; height: 2.75rem; border-radius: .75rem; flex-shrink: 0;
    background: linear-gradient(135deg,#3b82f6,#6366f1);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; box-shadow: 0 4px 1.25rem rgba(59,130,246,.35);
}
.cl-brand-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
.cl-brand-name { font-size: 1.0625rem; font-weight: 800; color: #fff; letter-spacing: -.02em; }
.cl-brand-sub { font-size: .6875rem; color: rgba(255,255,255,.5); font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }

/* Hero */
.cl-hero { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 2.5rem 0; }
.cl-pill {
    display: inline-flex; align-items: center; gap: .5rem;
    background: rgba(245, 158, 11, 0.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(245, 158, 11, 0.28);
    color: #fbbf24; font-size: .6875rem; font-weight: 700;
    padding: .375rem .875rem; border-radius: 9999px; margin-bottom: 1.25rem;
    letter-spacing: .06em; text-transform: uppercase; width: fit-content;
}
.cl-pill-dot { width: 7px; height: 7px; background: #fbbf24; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 8px #fbbf24; animation: clPulse 2s ease-in-out infinite; }
@keyframes clPulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.45;transform:scale(.85)} }

.cl-h1 { font-size: clamp(1.85rem, 3.2vw, 2.75rem); font-weight: 800; color: #fff; line-height: 1.2; letter-spacing: -.03em; margin: 0 0 1rem; }
.cl-h1-g { background: linear-gradient(90deg, #60a5fa, #a78bfa, #38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.cl-hero-p { color: rgba(255,255,255,.62); font-size: .875rem; line-height: 1.7; max-width: 29rem; margin: 0 0 2rem; }

/* Feature highlights */
.cl-features { display: flex; flex-direction: column; gap: .875rem; max-width: 29rem; }
.cl-feat-item {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .75rem 1rem; border-radius: .875rem;
    background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.07);
    backdrop-filter: blur(8px);
}
.cl-feat-ic {
    width: 2rem; height: 2rem; border-radius: .5rem; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(59,130,246,.15); color: #60a5fa;
}
.cl-feat-t { font-size: .8125rem; font-weight: 700; color: #fff; line-height: 1.3; }
.cl-feat-d { font-size: .75rem; color: rgba(255,255,255,.5); margin-top: .15rem; line-height: 1.45; }

.cl-foot { font-size: .6875rem; color: rgba(255,255,255,.32); }

/* --- Right Interactive Panel --- */
.cl-right {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 2.25rem 1.5rem; background: var(--panel); overflow-y: auto;
}

/* Mobile brand */
.cl-mob { display: flex; flex-direction: column; align-items: center; margin-bottom: 1.5rem; text-align: center; }
@media(min-width:1024px) { .cl-mob { display: none; } }
.cl-mob-logo {
    width: 3.25rem; height: 3.25rem; border-radius: .875rem;
    background: linear-gradient(135deg,#3b82f6,#6366f1);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; margin-bottom: .625rem; box-shadow: 0 6px 1.25rem rgba(59,130,246,.35);
}
.cl-mob-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
.cl-mob-name { font-size: 1.0625rem; font-weight: 800; color: var(--txt-heading); }
.cl-mob-sub { font-size: .75rem; color: var(--muted); margin-top: .15rem; }

/* --- Card Container --- */
.cl-card {
    width: 100%; max-width: 26.5rem;
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: 1.25rem; padding: 2rem 1.75rem;
    backdrop-filter: blur(24px);
    box-shadow: 0 1.25rem 3.5rem rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.05);
    animation: clUp .45s cubic-bezier(.16,1,.3,1) both;
}
@media(min-width:480px) { .cl-card { padding: 2.25rem 2rem; } }
@keyframes clUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

/* Card Header */
.cl-hdr { display: flex; align-items: center; gap: .875rem; margin-bottom: 1.5rem; }
.cl-hdr-icon {
    width: 2.75rem; height: 2.75rem; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg,#3b82f6,#4f46e5);
    border-radius: .875rem; color: #fff;
    box-shadow: 0 6px 1.25rem rgba(59,130,246,.32);
}
.cl-hdr-t { font-size: 1.375rem; font-weight: 800; color: var(--txt-heading); letter-spacing: -.025em; margin: 0; line-height: 1.2; }
.cl-hdr-s { font-size: .8125rem; color: var(--muted); margin: .25rem 0 0; }

/* Current User Badge Banner */
.cl-user-box {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem .875rem; margin-bottom: 1.375rem;
    background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.07);
    border-radius: .875rem;
}
.cl-user-avatar {
    width: 2.375rem; height: 2.375rem; border-radius: .625rem; flex-shrink: 0;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff; font-weight: 800; font-size: .875rem;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 3px 8px rgba(245,158,11,.25);
}
.cl-user-info { flex: 1; min-width: 0; }
.cl-user-name { font-size: .8125rem; font-weight: 700; color: var(--txt-heading); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cl-user-meta { font-size: .6875rem; color: var(--muted); display: flex; align-items: center; gap: .375rem; margin-top: .1rem; }
.cl-badge-warn {
    font-size: .625rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    padding: .15rem .45rem; border-radius: 9999px;
    background: rgba(245,158,11,.15); color: #fbbf24; border: 1px solid rgba(245,158,11,.25);
}

/* Form Styles */
.cl-form { display: flex; flex-direction: column; gap: 1.125rem; }
.cl-fld { display: flex; flex-direction: column; gap: .375rem; }
.cl-lbl { font-size: .6875rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); display: block; }

.cl-iw {
    position: relative; display: flex; align-items: center;
    background: var(--input-bg); border: 1.5px solid var(--input-bd);
    border-radius: .75rem; transition: border-color .2s, background .2s, box-shadow .2s;
}
.cl-iw:hover { border-color: rgba(255,255,255,.2); background: rgba(255,255,255,.08); }
.cl-iw.f { border-color: var(--blue) !important; background: rgba(59,130,246,.08) !important; box-shadow: 0 0 0 3px rgba(59,130,246,.18) !important; }
.cl-iw.err { border-color: var(--red) !important; background: rgba(248,113,113,.08) !important; }
.cl-iw-ic { position: absolute; left: .75rem; display: flex; align-items: center; pointer-events: none; color: rgba(255,255,255,.3); transition: color .2s; }
.cl-iw.f .cl-iw-ic { color: #60a5fa; }

.cl-inp {
    width: 100%; background: transparent !important; border: none !important; outline: none !important;
    padding: .8125rem .875rem .8125rem 2.5rem;
    font-size: .875rem; font-family: inherit; color: var(--txt); font-weight: 500;
    -webkit-appearance: none; appearance: none;
    box-shadow: none !important; --tw-ring-shadow: none !important;
}
.cl-inp::placeholder { color: rgba(255,255,255,.24); font-weight: 400; }

.cl-eye {
    position: absolute; right: .625rem;
    display: flex; align-items: center; justify-content: center;
    width: 1.875rem; height: 1.875rem;
    border: none; background: transparent; cursor: pointer;
    color: rgba(255,255,255,.35); border-radius: .375rem;
    transition: color .15s, background .15s; padding: 0;
}
.cl-eye:hover { color: #fff; background: rgba(255,255,255,.08); }

.cl-err { display: flex; align-items: center; gap: .375rem; color: var(--red); font-size: .75rem; font-weight: 600; margin-top: .15rem; animation: clShake .3s ease; }
@keyframes clShake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-3px)} 75%{transform:translateX(3px)} }

/* Password Strength & Live Checklist */
.cl-meter-box {
    padding: .875rem; border-radius: .75rem;
    background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06);
    display: flex; flex-direction: column; gap: .625rem;
}
.cl-meter-head { display: flex; justify-content: space-between; align-items: center; font-size: .6875rem; font-weight: 600; }
.cl-meter-lbl { color: var(--muted); }
.cl-meter-state { font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }

.cl-meter-bar { display: flex; gap: 4px; height: 5px; width: 100%; }
.cl-meter-seg { flex: 1; height: 100%; border-radius: 9999px; background: rgba(255,255,255,.1); transition: background .25s ease; }

.cl-checklist { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem .6rem; margin-top: .2rem; }
.cl-chk-item { display: flex; align-items: center; gap: .375rem; font-size: .6875rem; color: var(--muted); transition: color .2s; }
.cl-chk-dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.2); flex-shrink: 0; transition: all .2s; }
.cl-chk-item.ok { color: #34d399; font-weight: 600; }
.cl-chk-item.ok .cl-chk-dot { background: #34d399; box-shadow: 0 0 6px #34d399; }

/* Action Buttons */
.cl-btn {
    width: 100%; margin-top: .5rem;
    display: flex; align-items: center; justify-content: center;
    padding: .875rem 1.25rem; gap: .5rem;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff; font-family: inherit; font-size: .875rem; font-weight: 700;
    border: none; border-radius: .75rem; cursor: pointer;
    box-shadow: 0 4px 1.25rem rgba(37,99,235,.35);
    transition: opacity .2s, transform .15s, box-shadow .2s;
    letter-spacing: .01em; position: relative; overflow: hidden;
}
.cl-btn::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.15),transparent 55%); }
.cl-btn:hover { opacity: .94; transform: translateY(-1px); box-shadow: 0 8px 1.75rem rgba(37,99,235,.45); }
.cl-btn:active { transform: translateY(0) scale(.98); }
@keyframes clSpin { to{transform:rotate(360deg)} }
.cl-spin { animation: clSpin .7s linear infinite; }

/* Secondary Actions / Logout */
.cl-extra-actions {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: .25rem; font-size: .75rem;
}
.cl-logout-link {
    color: var(--muted); text-decoration: none; cursor: pointer;
    background: none; border: none; padding: 0; font-family: inherit; font-size: .75rem;
    display: inline-flex; align-items: center; gap: .375rem; transition: color .15s;
}
.cl-logout-link:hover { color: #f87171; }

/* Security Tips Callout */
.cl-note {
    display: flex; align-items: flex-start; gap: .5rem;
    margin-top: 1.25rem; padding: .75rem .875rem;
    background: rgba(59,130,246,.06); border: 1px solid rgba(59,130,246,.14);
    border-radius: .75rem; color: rgba(255,255,255,.7);
}
.cl-note svg { width: .9375rem; height: .9375rem; flex-shrink: 0; color: #60a5fa; margin-top: .1rem; }
.cl-note p { font-size: .75rem; line-height: 1.5; margin: 0; }

.cl-pw { margin-top: 1.5rem; font-size: .6875rem; color: rgba(255,255,255,.25); text-align: center; }
.cl-pw strong { color: rgba(255,255,255,.45); font-weight: 600; }

/* ============================================================
   LIGHT MODE THEME
============================================================ */
.cl.lt {
    --bg: #f0f4f8;
    --panel: #f7f9fc;
    --card-bg: #ffffff;
    --card-border: rgba(100,116,139,.18);
    --txt: #1e293b;
    --txt-heading: #0f172a;
    --muted: #64748b;
    --input-bg: #f8fafc;
    --input-bd: #cbd5e1;
    --blue: #1d4ed8;
    --red: #dc2626;
}
.cl.lt .cl-blob { opacity: .06; }
.cl.lt .cl-card {
    box-shadow: 0 10px 2.5rem rgba(30,58,138,.08), 0 1px 3px rgba(0,0,0,.05);
}
.cl.lt .cl-user-box { background: #f8fafc; border-color: #e2e8f0; }
.cl.lt .cl-meter-box { background: #f8fafc; border-color: #e2e8f0; }
.cl.lt .cl-meter-seg { background: #e2e8f0; }
.cl.lt .cl-iw { border-color: #cbd5e1; }
.cl.lt .cl-iw:hover { background: #f1f5f9; border-color: #94a3b8; }
.cl.lt .cl-iw.f { background: #eff6ff !important; border-color: #1d4ed8 !important; box-shadow: 0 0 0 3px rgba(29,78,216,.12) !important; }
.cl.lt .cl-iw-ic { color: #94a3b8; }
.cl.lt .cl-iw.f .cl-iw-ic { color: #1d4ed8; }
.cl.lt .cl-inp { color: #0f172a; }
.cl.lt .cl-inp::placeholder { color: #94a3b8; }
.cl.lt .cl-eye { color: #94a3b8; }
.cl.lt .cl-eye:hover { color: #0f172a; background: rgba(0,0,0,.04); }
.cl.lt .cl-note { background: #eff6ff; border-color: rgba(29,78,216,.18); color: #1e40af; }
.cl.lt .cl-note svg { color: #1d4ed8; }
.cl.lt .cl-pw { color: #64748b; }
.cl.lt .cl-pw strong { color: #334155; }
.cl.lt .cl-theme { background: rgba(255,255,255,.9); color: #1e293b; border-color: #cbd5e1; }
.cl.lt .cl-theme:hover { background: #fff; color: #1d4ed8; }
.cl.lt .cl-logout-link:hover { color: #dc2626; }

.cl, .cl-right, .cl-card, .cl-iw, .cl-inp, .cl-note, .cl-theme, .cl-user-box, .cl-meter-box {
    transition: background .3s, color .3s, border-color .3s, box-shadow .3s;
}
</style>
@endpush

<div class="cl" id="clRoot">

    {{-- Ambient glowing background blobs --}}
    <div class="cl-blob cl-b1"></div>
    <div class="cl-blob cl-b2"></div>
    <div class="cl-blob cl-b3"></div>

    {{-- Dark / Light mode toggle --}}
    <button class="cl-theme" aria-label="Toggle theme" onclick="clToggle()" title="Ganti mode tampilan">
        <svg id="clSun" style="width:1.0625rem;height:1.0625rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.772 17.303a.75.75 0 00-1.06 1.06l1.59 1.591a.75.75 0 001.061-1.06l-1.59-1.591zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.166 5.106a.75.75 0 011.06 1.06L5.636 7.756a.75.75 0 01-1.061-1.06l1.59-1.59z"/>
        </svg>
        <svg id="clMoon" style="width:1.0625rem;height:1.0625rem;display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd"/>
        </svg>
    </button>

    <div class="cl-wrap">

        {{-- ===== LEFT PANEL (Desktop Hero) ===== --}}
        <div class="cl-left">
            <div class="cl-left-in">
                <div class="cl-brand">
                    <div class="cl-brand-logo">
                        <img src="{{ asset('images/lion-logo.png') }}" alt="Cabin Core" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <div class="cl-brand-name">Cabin Core</div>
                        <div class="cl-brand-sub">Batam Aero Technic</div>
                    </div>
                </div>

                <div class="cl-hero">
                    <div class="cl-pill">
                        <span class="cl-pill-dot"></span>
                        Pembaruan Keamanan Wajib
                    </div>
                    <h1 class="cl-h1">
                        Amankan Akun Anda,<br>
                        <span class="cl-h1-g">Buat Password Baru</span>
                    </h1>
                    <p class="cl-hero-p">
                        Akun Anda saat ini terdeteksi menggunakan kata sandi default sistem. Demi menjaga kerahasiaan dan integritas data perawatan kabin armada, silakan tetapkan kata sandi baru pribadi Anda.
                    </p>

                    <div class="cl-features">
                        <div class="cl-feat-item">
                            <div class="cl-feat-ic">
                                <svg style="width:1.125rem;height:1.125rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <div class="cl-feat-t">Kerahasiaan Eksklusif</div>
                                <div class="cl-feat-d">Hanya Anda yang mengetahui kata sandi baru ini. Password tidak dapat dilihat kembali oleh pihak lain.</div>
                            </div>
                        </div>

                        <div class="cl-feat-item">
                            <div class="cl-feat-ic">
                                <svg style="width:1.125rem;height:1.125rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <div class="cl-feat-t">Standar Enkripsi Industri</div>
                                <div class="cl-feat-d">Sandi dienkripsi menggunakan hashing Bcrypt berkekuatan tinggi sesuai regulasi IT Lion Air Group.</div>
                            </div>
                        </div>

                        <div class="cl-feat-item">
                            <div class="cl-feat-ic">
                                <svg style="width:1.125rem;height:1.125rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <div class="cl-feat-t">Akses Instan ke Dashboard</div>
                                <div class="cl-feat-d">Setelah kata sandi berhasil diperbarui, Anda akan otomatis dialihkan ke menu utama operasional.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cl-foot">&copy; {{ date('Y') }} Cabin Core &mdash; Batam Aero Technic. All rights reserved.</div>
            </div>
        </div>

        {{-- ===== RIGHT PANEL (Interactive Form) ===== --}}
        <div class="cl-right">

            {{-- Mobile branding --}}
            <div class="cl-mob">
                <div class="cl-mob-logo">
                    <img src="{{ asset('images/lion-logo.png') }}" alt="Cabin Core" onerror="this.style.display='none'">
                </div>
                <div class="cl-mob-name">Cabin Core</div>
                <div class="cl-mob-sub">Pembaruan Password Akun</div>
            </div>

            <div class="cl-card">
                {{-- Card Header --}}
                <div class="cl-hdr">
                    <div class="cl-hdr-icon">
                        <svg style="width:1.25rem;height:1.25rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="cl-hdr-t">Ubah Password Default</h2>
                        <div class="cl-hdr-s">Tetapkan kata sandi baru untuk akun Anda</div>
                    </div>
                </div>

                {{-- User Identity Context Badge --}}
                @if(auth()->check())
                <div class="cl-user-box">
                    <div class="cl-user-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="cl-user-info">
                        <div class="cl-user-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                        <div class="cl-user-meta">
                            <span>ID: {{ auth()->user()->nik ?? '-' }}</span>
                            <span>&bull;</span>
                            <span style="text-transform:capitalize;">{{ auth()->user()->role ?? 'Staff' }}</span>
                        </div>
                    </div>
                    <div class="cl-badge-warn">Default PW</div>
                </div>
                @endif

                {{-- Form Component --}}
                <form wire:submit="updatePassword" class="cl-form" novalidate id="clResetForm">

                    {{-- Field: Password Baru --}}
                    <div class="cl-fld">
                        <label for="password" class="cl-lbl">Password Baru</label>
                        <div class="cl-iw @error('password') err @enderror" id="clPwWrapper">
                            <div class="cl-iw-ic">
                                <svg style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input wire:model="password" type="password" id="password" class="cl-inp"
                                placeholder="Ketik minimal 8 karakter"
                                autocomplete="new-password"
                                onfocus="this.closest('.cl-iw').classList.add('f')"
                                onblur="this.closest('.cl-iw').classList.remove('f')"
                                oninput="clCheckStrength()">
                            <button type="button" class="cl-eye" aria-label="Lihat password" onclick="clToggleEye('password', 'clEyeS1', 'clEyeH1')" title="Tampilkan/sembunyikan sandi">
                                <svg id="clEyeS1" style="width:1.0625rem;height:1.0625rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg id="clEyeH1" style="width:1.0625rem;height:1.0625rem;display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z"/>
                                    <path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.22 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                        <div class="cl-err">
                            <svg style="width:.875rem;height:.875rem;flex-shrink:0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>

                    {{-- Field: Konfirmasi Password --}}
                    <div class="cl-fld">
                        <label for="password_confirmation" class="cl-lbl">Konfirmasi Password Baru</label>
                        <div class="cl-iw" id="clPwcWrapper">
                            <div class="cl-iw-ic">
                                <svg style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input wire:model="password_confirmation" type="password" id="password_confirmation" class="cl-inp"
                                placeholder="Ketik ulang password baru"
                                autocomplete="new-password"
                                onfocus="this.closest('.cl-iw').classList.add('f')"
                                onblur="this.closest('.cl-iw').classList.remove('f')"
                                oninput="clCheckStrength()">
                            <button type="button" class="cl-eye" aria-label="Lihat konfirmasi password" onclick="clToggleEye('password_confirmation', 'clEyeS2', 'clEyeH2')" title="Tampilkan/sembunyikan sandi">
                                <svg id="clEyeS2" style="width:1.0625rem;height:1.0625rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg id="clEyeH2" style="width:1.0625rem;height:1.0625rem;display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z"/>
                                    <path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.22 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Live Password Strength & Criteria Meter --}}
                    <div class="cl-meter-box">
                        <div class="cl-meter-head">
                            <span class="cl-meter-lbl">Kekuatan Sandi</span>
                            <span class="cl-meter-state" id="clStrengthText">Belum diisi</span>
                        </div>
                        <div class="cl-meter-bar">
                            <div class="cl-meter-seg" id="clBar1"></div>
                            <div class="cl-meter-seg" id="clBar2"></div>
                            <div class="cl-meter-seg" id="clBar3"></div>
                            <div class="cl-meter-seg" id="clBar4"></div>
                        </div>

                        <div class="cl-checklist">
                            <div class="cl-chk-item" id="chkLen">
                                <span class="cl-chk-dot"></span>
                                <span>Minimal 8 karakter</span>
                            </div>
                            <div class="cl-chk-item" id="chkCase">
                                <span class="cl-chk-dot"></span>
                                <span>Huruf besar & kecil</span>
                            </div>
                            <div class="cl-chk-item" id="chkNum">
                                <span class="cl-chk-dot"></span>
                                <span>Angka atau simbol</span>
                            </div>
                            <div class="cl-chk-item" id="chkMatch">
                                <span class="cl-chk-dot"></span>
                                <span>Konfirmasi cocok</span>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="cl-btn" id="clSubmitBtn">
                        <span wire:loading.remove wire:target="updatePassword">
                            <span style="display:flex;align-items:center;gap:.5rem">
                                <svg style="width:1.0625rem;height:1.0625rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 19.5V18h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5zm0 3a.75.75 0 000 1.5A2.25 2.25 0 0118 8.25a.75.75 0 001.5 0 3.75 3.75 0 00-3.75-3.75z" clip-rule="evenodd"/>
                                </svg>
                                Simpan & Lanjutkan ke Dashboard
                            </span>
                        </span>
                        <span wire:loading wire:target="updatePassword">
                            <span style="display:flex;align-items:center;gap:.5rem">
                                <svg class="cl-spin" style="width:1.0625rem;height:1.0625rem;display:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Memperbarui Password...
                            </span>
                        </span>
                    </button>

                    {{-- Extra Actions (Logout) --}}
                    <div class="cl-extra-actions">
                        <span style="color:var(--muted)">Bukan akun Anda?</span>
                        <button type="button" wire:click="logout" wire:confirm="Keluar dari akun ini dan kembali ke halaman login?" class="cl-logout-link">
                            <svg style="width:.875rem;height:.875rem" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h5.5A2.25 2.25 0 0113 4.25v2a.75.75 0 01-1.5 0v-2a.75.75 0 00-.75-.75h-5.5a.75.75 0 00-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 00.75-.75v-2a.75.75 0 011.5 0v2A2.25 2.25 0 0110.75 18h-5.5A2.25 2.25 0 013 15.75V4.25z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M19 10a.75.75 0 00-.75-.75H8.704l2.473-2.47a.75.75 0 10-1.06-1.06l-3.75 3.75a.75.75 0 000 1.06l3.75 3.75a.75.75 0 101.06-1.06l-2.473-2.47H18.25A.75.75 0 0019 10z" clip-rule="evenodd" />
                            </svg>
                            Keluar / Ganti Akun
                        </button>
                    </div>

                </form>

                {{-- Informational note --}}
                <div class="cl-note">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                    <p>Tips: Jangan gunakan kata sandi yang mudah ditebak seperti tanggal lahir atau NIK. Buat kombinasi unik yang mudah Anda ingat.</p>
                </div>

            </div>

            <p class="cl-pw">&copy; {{ date('Y') }} <strong>Batam Aero Technic</strong> &mdash; Lion Air Group.</p>
        </div>

    </div>
</div>

@push('scripts')
<script>
(function(){
    // --- Dark / Light Theme Sync ---
    var r = document.getElementById('clRoot');
    var s = document.getElementById('clSun');
    var m = document.getElementById('clMoon');

    function setTheme(mode) {
        if (mode === 'light') {
            r.classList.add('lt');
            s.style.display = 'none';
            m.style.display = 'block';
        } else {
            r.classList.remove('lt');
            s.style.display = 'block';
            m.style.display = 'none';
        }
    }

    var saved = localStorage.getItem('cbm-theme');
    if (saved) {
        setTheme(saved);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
        setTheme('light');
    } else {
        setTheme('dark');
    }

    window.clToggle = function() {
        var next = r.classList.contains('lt') ? 'dark' : 'light';
        localStorage.setItem('cbm-theme', next);
        setTheme(next);
    };

    // --- Show / Hide Password Toggle ---
    window.clToggleEye = function(inputId, eyeShowId, eyeHideId) {
        var inp = document.getElementById(inputId);
        var sIcon = document.getElementById(eyeShowId);
        var hIcon = document.getElementById(eyeHideId);
        if (!inp) return;
        if (inp.type === 'password') {
            inp.type = 'text';
            sIcon.style.display = 'none';
            hIcon.style.display = 'block';
        } else {
            inp.type = 'password';
            sIcon.style.display = 'block';
            hIcon.style.display = 'none';
        }
    };

    // --- Dynamic Password Strength Calculation & Checklist ---
    window.clCheckStrength = function() {
        var p = (document.getElementById('password') ? document.getElementById('password').value : '') || '';
        var pc = (document.getElementById('password_confirmation') ? document.getElementById('password_confirmation').value : '') || '';

        var hasLen = p.length >= 8;
        var hasCase = /[a-z]/.test(p) && /[A-Z]/.test(p);
        var hasNum = /[0-9]/.test(p) || /[^a-zA-Z0-9]/.test(p);
        var hasMatch = p.length > 0 && pc.length > 0 && p === pc;

        // Toggle checklist classes
        var toggle = function(id, ok) {
            var el = document.getElementById(id);
            if (el) {
                if (ok) el.classList.add('ok');
                else el.classList.remove('ok');
            }
        };
        toggle('chkLen', hasLen);
        toggle('chkCase', hasCase);
        toggle('chkNum', hasNum);
        toggle('chkMatch', hasMatch);

        // Strength score calculation
        var score = 0;
        if (p.length >= 6) score++;
        if (hasLen) score++;
        if (hasCase && hasNum) score++;
        if (hasLen && hasCase && hasNum && p.length >= 10) score++;

        var b1 = document.getElementById('clBar1');
        var b2 = document.getElementById('clBar2');
        var b3 = document.getElementById('clBar3');
        var b4 = document.getElementById('clBar4');
        var txt = document.getElementById('clStrengthText');

        var resetBars = function() {
            [b1, b2, b3, b4].forEach(function(b){ if(b) b.style.background = ''; });
        };
        resetBars();

        if (p.length === 0) {
            if (txt) { txt.textContent = 'Belum diisi'; txt.style.color = ''; }
            return;
        }

        if (score <= 1) {
            if (b1) b1.style.background = '#f87171';
            if (txt) { txt.textContent = 'Lemah'; txt.style.color = '#f87171'; }
        } else if (score === 2) {
            if (b1) b1.style.background = '#f59e0b';
            if (b2) b2.style.background = '#f59e0b';
            if (txt) { txt.textContent = 'Sedang'; txt.style.color = '#f59e0b'; }
        } else if (score === 3) {
            if (b1) b1.style.background = '#3b82f6';
            if (b2) b2.style.background = '#3b82f6';
            if (b3) b3.style.background = '#3b82f6';
            if (txt) { txt.textContent = 'Kuat'; txt.style.color = '#60a5fa'; }
        } else {
            if (b1) b1.style.background = '#10b981';
            if (b2) b2.style.background = '#10b981';
            if (b3) b3.style.background = '#10b981';
            if (b4) b4.style.background = '#10b981';
            if (txt) { txt.textContent = 'Sangat Kuat'; txt.style.color = '#34d399'; }
        }
    };
})();
</script>
@endpush
