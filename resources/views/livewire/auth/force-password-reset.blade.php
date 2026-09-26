@push('styles')
<style>
/* ============================================================
   CABIN CORE — Force Password Reset (Scoped CSS)
============================================================ */

.cl {
    --bg: #070b19;
    --panel: #0b1022;
    --card-bg: rgba(255,255,255,0.04);
    --card-border: rgba(255,255,255,0.08);
    --txt: #f1f5f9;
    --muted: rgba(255,255,255,0.42);
    --input-bg: rgba(255,255,255,0.06);
    --input-bd: rgba(255,255,255,0.10);
    --blue: #3b82f6;
    --indigo: #6366f1;
    --red: #f87171;

    position: fixed; inset: 0;
    display: flex;
    background: var(--bg);
    color: var(--txt);
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    overflow: hidden;
}

/* --- Theme toggle --- */
.cl-theme {
    position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999;
    width: 2.5rem; height: 2.5rem;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,.12); border-radius: 50%;
    background: rgba(255,255,255,.07); color: rgba(255,255,255,.6);
    backdrop-filter: blur(10px); cursor: pointer;
    transition: background .2s, color .2s; padding: 0;
}
.cl-theme:hover { background: rgba(255,255,255,.14); color: #fff; }

/* --- Layout --- */
.cl-wrap { position: relative; z-index: 1; display: flex; width: 100%; height: 100%; }

/* --- Left --- */
.cl-left {
    display: none; flex-direction: column; width: 55%; position: relative; overflow: hidden;
    background-color: #3d0f0f; background-size: cover; background-position: center;
}
.cl-left::before {
    content: ''; position: absolute; inset: 0; z-index: 0;
    background: linear-gradient(160deg, rgba(25,8,8,.92), rgba(175,30,30,.55) 50%, rgba(25,8,8,.95));
}
@media(min-width:1024px) { .cl-left { display: flex; } }
.cl-left-in { display: flex; flex-direction: column; justify-content: space-between; height: 100%; padding: 2.5rem 2.75rem; position: relative; z-index: 1; }

.cl-brand { display: flex; align-items: center; gap: .625rem; }
.cl-brand-logo {
    width: 2.5rem; height: 2.5rem; border-radius: .625rem; flex-shrink: 0;
    background: linear-gradient(135deg,#ef4444,#dc2626);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; box-shadow: 0 4px 16px rgba(239,68,68,.35);
}
.cl-brand-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
.cl-brand-name { font-size: .9375rem; font-weight: 800; color: #fff; }
.cl-brand-sub { font-size: .625rem; color: rgba(255,255,255,.45); font-weight: 500; }

.cl-hero { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 2rem 0; }
.cl-pill {
    display: inline-flex; align-items: center; gap: .4375rem;
    background: rgba(255,255,255,.1); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.16);
    color: rgba(255,255,255,.85); font-size: .625rem; font-weight: 700;
    padding: .3125rem .75rem; border-radius: 999px; margin-bottom: 1.25rem;
    letter-spacing: .06em; text-transform: uppercase; width: fit-content;
}
.cl-pill-dot { width: 6px; height: 6px; background: #fbbf24; border-radius: 50%; flex-shrink: 0; animation: clPulse 2s ease-in-out infinite; }
@keyframes clPulse { 0%,100%{opacity:1} 50%{opacity:.4} }

.cl-h1 { font-size: clamp(1.75rem, 3.2vw, 3rem); font-weight: 800; color: #fff; line-height: 1.15; letter-spacing: -.02em; margin: 0 0 1rem; }
.cl-h1-g { background: linear-gradient(90deg,#f87171,#fca5a5,#fef08a); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.cl-hero-p { color: rgba(255,255,255,.55); font-size: .875rem; line-height: 1.7; max-width: 28rem; margin: 0 0 2rem; }

.cl-foot { font-size: .625rem; color: rgba(255,255,255,.28); }

/* --- Right --- */
.cl-right {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 2rem 1.25rem; background: var(--panel); overflow-y: auto;
}

.cl-mob { display: flex; flex-direction: column; align-items: center; margin-bottom: 1.75rem; }
@media(min-width:1024px) { .cl-mob { display: none; } }
.cl-mob-logo {
    width: 3rem; height: 3rem; border-radius: .75rem;
    background: linear-gradient(135deg,#ef4444,#dc2626);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; margin-bottom: .5rem; box-shadow: 0 4px 18px rgba(239,68,68,.3);
}
.cl-mob-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
.cl-mob-name { font-size: .9375rem; font-weight: 800; color: var(--txt); text-align: center; }
.cl-mob-sub { font-size: .6875rem; color: var(--muted); text-align: center; }

/* --- Card --- */
.cl-card {
    width: 100%; max-width: 24rem;
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: 1.25rem; padding: 2rem 1.75rem;
    backdrop-filter: blur(20px);
    box-shadow: 0 20px 50px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.04);
    animation: clUp .45s cubic-bezier(.16,1,.3,1) both;
}
@media(min-width:480px) { .cl-card { padding: 2.25rem 2rem; } }
@keyframes clUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

.cl-hdr { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.75rem; }
.cl-hdr-icon {
    width: 2.5rem; height: 2.5rem; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg,#ef4444,#dc2626);
    border-radius: .75rem; color: #fff;
    box-shadow: 0 6px 18px rgba(239,68,68,.3);
}
.cl-hdr-t { font-size: 1.375rem; font-weight: 800; color: var(--txt); letter-spacing: -.02em; margin: 0; line-height: 1.2; }
.cl-hdr-s { font-size: .8125rem; color: var(--muted); margin: .125rem 0 0; }

.cl-form { display: flex; flex-direction: column; gap: 1.125rem; }
.cl-fld { display: flex; flex-direction: column; gap: .375rem; }
.cl-lbl { font-size: .625rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); display: block; }

.cl-iw {
    position: relative; display: flex; align-items: center;
    background: var(--input-bg); border: 1.5px solid var(--input-bd);
    border-radius: .75rem; transition: border-color .2s, background .2s, box-shadow .2s;
}
.cl-iw:hover { border-color: rgba(255,255,255,.16); background: rgba(255,255,255,.08); }
.cl-iw.f { border-color: var(--red) !important; background: rgba(239,68,68,.07) !important; box-shadow: 0 0 0 3px rgba(239,68,68,.15) !important; }
.cl-iw-ic { position: absolute; left: .75rem; display: flex; align-items: center; pointer-events: none; color: rgba(255,255,255,.25); transition: color .2s; }
.cl-iw.f .cl-iw-ic { color: #f87171; }

.cl-inp {
    width: 100%; background: transparent !important; border: none !important; outline: none !important;
    padding: .8125rem .875rem .8125rem 2.5rem;
    font-size: .875rem; font-family: inherit; color: var(--txt); font-weight: 500;
    -webkit-appearance: none; appearance: none;
    box-shadow: none !important; --tw-ring-shadow: none !important;
}
.cl-inp::placeholder { color: rgba(255,255,255,.2); font-weight: 400; }

.cl-eye {
    position: absolute; right: .625rem;
    display: flex; align-items: center; justify-content: center;
    width: 1.75rem; height: 1.75rem;
    border: none; background: transparent; cursor: pointer;
    color: rgba(255,255,255,.25); border-radius: .375rem;
    transition: color .15s, background .15s; padding: 0;
}
.cl-eye:hover { color: rgba(255,255,255,.6); background: rgba(255,255,255,.06); }

.cl-err { display: flex; align-items: center; gap: .3125rem; color: var(--red); font-size: .75rem; font-weight: 500; animation: clShake .3s ease; }
@keyframes clShake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-3px)} 75%{transform:translateX(3px)} }

.cl-btn {
    width: 100%; margin-top: .25rem;
    display: flex; align-items: center; justify-content: center;
    padding: .8125rem 1.25rem; gap: .4375rem;
    background: linear-gradient(135deg,#dc2626,#b91c1c);
    color: #fff; font-family: inherit; font-size: .875rem; font-weight: 700;
    border: none; border-radius: .75rem; cursor: pointer;
    box-shadow: 0 4px 16px rgba(220,38,38,.35);
    transition: opacity .2s, transform .15s, box-shadow .2s;
    letter-spacing: .01em; position: relative; overflow: hidden;
}
.cl-btn::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.12),transparent 55%); }
.cl-btn:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(220,38,38,.45); }
.cl-btn:active { transform: translateY(0) scale(.98); }
@keyframes clSpin { to{transform:rotate(360deg)} }
.cl-spin { animation: clSpin .7s linear infinite; }

.cl-note {
    display: flex; align-items: flex-start; gap: .4375rem;
    margin-top: 1.25rem; padding: .75rem .875rem;
    background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.12);
    border-radius: .625rem; color: rgba(248,113,113,.9);
}
.cl-note p { font-size: .75rem; line-height: 1.55; margin: 0; }
.cl-pw { margin-top: 1.25rem; font-size: .6875rem; color: rgba(255,255,255,.18); text-align: center; }
.cl-pw strong { color: rgba(255,255,255,.3); font-weight: 600; }

/* --- LIGHT MODE --- */
.cl.lt {
    --bg: #fef2f2; --panel: #fef2f2;
    --card-bg: rgba(255,255,255,.9); --card-border: rgba(239,68,68,.2);
    --txt: #450a0a; --muted: #991b1b;
    --input-bg: #fee2e2; --input-bd: #fca5a5;
}
.cl.lt .cl-card { box-shadow: 0 10px 40px rgba(220,38,38,.08), 0 1px 3px rgba(0,0,0,.04); }
.cl.lt .cl-hdr-t { color: #450a0a; }
.cl.lt .cl-iw:hover { background: #fecaca; border-color: #f87171; }
.cl.lt .cl-iw.f { background: #fee2e2 !important; border-color:#dc2626 !important; }
.cl.lt .cl-iw-ic { color: #ef4444; }
.cl.lt .cl-iw.f .cl-iw-ic { color: #dc2626; }
.cl.lt .cl-inp { color: #450a0a; }
.cl.lt .cl-inp::placeholder { color: #f87171; }
.cl.lt .cl-eye { color: #ef4444; }
.cl.lt .cl-eye:hover { color: #b91c1c; background: rgba(0,0,0,.04); }
.cl.lt .cl-note { background: #fef2f2; border-color: rgba(239,68,68,.15); color: #991b1b; }
.cl.lt .cl-pw { color: #fca5a5; }
.cl.lt .cl-pw strong { color: #ef4444; }
.cl.lt .cl-theme { background: rgba(255,255,255,.85); color: #ef4444; border-color: rgba(239,68,68,.3); }
.cl.lt .cl-theme:hover { background: #fff; color: #b91c1c; }

.cl, .cl-right, .cl-card, .cl-iw, .cl-inp, .cl-note, .cl-theme { transition: background .3s, color .3s, border-color .3s, box-shadow .3s; }
</style>
@endpush

<div class="cl" id="clRoot">

    {{-- Theme toggle --}}
    <button class="cl-theme" aria-label="Toggle theme" onclick="clToggle()">
        <svg id="clSun" style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.772 17.303a.75.75 0 00-1.06 1.06l1.59 1.591a.75.75 0 001.061-1.06l-1.59-1.591zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.166 5.106a.75.75 0 011.06 1.06L5.636 7.756a.75.75 0 01-1.061-1.06l1.59-1.59z"/></svg>
        <svg id="clMoon" style="width:1rem;height:1rem;display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd"/></svg>
    </button>

    <div class="cl-wrap">

        {{-- ===== LEFT ===== --}}
        <div class="cl-left">
            <div class="cl-left-in">
                <div class="cl-brand">
                    <div class="cl-brand-logo"><img src="{{ asset('images/lion-logo.png') }}" alt="Cabin Core"></div>
                    <div>
                        <div class="cl-brand-name">Cabin Core</div>
                        <div class="cl-brand-sub">Batam Aero Technic</div>
                    </div>
                </div>

                <div class="cl-hero">
                    <div class="cl-pill"><span class="cl-pill-dot"></span>Keamanan Pertama</div>
                    <h1 class="cl-h1">Wajib<br><span class="cl-h1-g">Ganti Password</span></h1>
                    <p class="cl-hero-p">Demi keamanan data Anda, silakan ubah password default dari administrator sebelum melanjutkan akses ke dalam sistem.</p>
                </div>

                <div class="cl-foot">&copy; {{ date('Y') }} Cabin Core &mdash; All rights reserved.</div>
            </div>
        </div>

        {{-- ===== RIGHT ===== --}}
        <div class="cl-right">

            <div class="cl-mob">
                <div class="cl-mob-logo"><img src="{{ asset('images/lion-logo.png') }}" alt="Cabin Core"></div>
                <div class="cl-mob-name">Cabin Core</div>
                <div class="cl-mob-sub">Keamanan Pertama</div>
            </div>

            <div class="cl-card">
                <div class="cl-hdr">
                    <div class="cl-hdr-icon">
                        <svg style="width:1.125rem;height:1.125rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <div class="cl-hdr-t">Ubah Password</div>
                        <div class="cl-hdr-s">Masukkan password baru Anda</div>
                    </div>
                </div>

                <form wire:submit="updatePassword" class="cl-form" novalidate>

                    {{-- Password Baru --}}
                    <div class="cl-fld">
                        <label for="password" class="cl-lbl">Password Baru</label>
                        <div class="cl-iw" id="clPw">
                            <div class="cl-iw-ic">
                                <svg style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/></svg>
                            </div>
                            <input wire:model="password" type="password" id="password" class="cl-inp"
                                placeholder="Min. 8 karakter"
                                onfocus="this.closest('.cl-iw').classList.add('f')"
                                onblur="this.closest('.cl-iw').classList.remove('f')">
                            <button type="button" class="cl-eye" aria-label="Toggle password"
                                onclick="var i=document.getElementById('password'),a=document.getElementById('clEyeS1'),b=document.getElementById('clEyeH1');if(i.type==='password'){i.type='text';a.style.display='none';b.style.display='block'}else{i.type='password';a.style.display='block';b.style.display='none'}">
                                <svg id="clEyeS1" style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd"/></svg>
                                <svg id="clEyeH1" style="width:1rem;height:1rem;display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z"/><path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.22 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z"/></svg>
                            </button>
                        </div>
                        @error('password')
                        <div class="cl-err">
                            <svg style="width:.875rem;height:.875rem;flex-shrink:0;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div class="cl-fld">
                        <label for="password_confirmation" class="cl-lbl">Konfirmasi Password Baru</label>
                        <div class="cl-iw" id="clPwc">
                            <div class="cl-iw-ic">
                                <svg style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/></svg>
                            </div>
                            <input wire:model="password_confirmation" type="password" id="password_confirmation" class="cl-inp"
                                placeholder="Ulangi password baru"
                                onfocus="this.closest('.cl-iw').classList.add('f')"
                                onblur="this.closest('.cl-iw').classList.remove('f')">
                            <button type="button" class="cl-eye" aria-label="Toggle password"
                                onclick="var i=document.getElementById('password_confirmation'),a=document.getElementById('clEyeS2'),b=document.getElementById('clEyeH2');if(i.type==='password'){i.type='text';a.style.display='none';b.style.display='block'}else{i.type='password';a.style.display='block';b.style.display='none'}">
                                <svg id="clEyeS2" style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd"/></svg>
                                <svg id="clEyeH2" style="width:1rem;height:1rem;display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z"/><path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.22 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="cl-btn">
                        <span wire:loading.remove wire:target="updatePassword" style="display:flex;align-items:center;gap:.4375rem">
                            <svg style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 19.5V18h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5zm0 3a.75.75 0 000 1.5A2.25 2.25 0 0118 8.25a.75.75 0 001.5 0 3.75 3.75 0 00-3.75-3.75z" clip-rule="evenodd"/></svg>
                            Simpan Password
                        </span>
                        <span wire:loading wire:target="updatePassword" style="display:none;align-items:center;gap:.4375rem">
                            <svg class="cl-spin" style="width:1rem;height:1rem;display:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>

                </form>

                <div class="cl-note">
                    <svg style="width:.875rem;height:.875rem;flex-shrink:0;display:block;color:#ef4444;margin-top:.0625rem" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    <p>Pastikan password baru Anda sulit ditebak dan mengandung kombinasi huruf serta angka (minimal 8 karakter).</p>
                </div>
            </div>

            <p class="cl-pw">&copy; {{ date('Y') }} <strong>Batam Aero Technic</strong>. Powered by Lion Air Group.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    var r=document.getElementById('clRoot'),s=document.getElementById('clSun'),m=document.getElementById('clMoon');
    function set(t){if(t==='light'){r.classList.add('lt');s.style.display='none';m.style.display='block'}else{r.classList.remove('lt');s.style.display='block';m.style.display='none'}}
    var sv=localStorage.getItem('cbm-theme');
    if(sv){set(sv)}else if(window.matchMedia&&window.matchMedia('(prefers-color-scheme:light)').matches){set('light')}else{set('dark')}
    window.clToggle=function(){var n=r.classList.contains('lt')?'dark':'light';localStorage.setItem('cbm-theme',n);set(n)};
})();
</script>
@endpush
