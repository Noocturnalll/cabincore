<div class="cbm-login-root" id="cbm-root">

    {{-- ===== Theme Toggle Button ===== --}}
    <button id="cbm-theme-toggle" class="cbm-theme-btn" aria-label="Toggle theme" onclick="cbmToggleTheme()">
        {{-- Sun icon (shown in dark mode) --}}
        <svg id="cbm-icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.772 17.303a.75.75 0 00-1.06 1.06l1.59 1.591a.75.75 0 001.061-1.06l-1.59-1.591zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.166 5.106a.75.75 0 011.06 1.06L5.636 7.756a.75.75 0 01-1.061-1.06l1.59-1.59z" />
        </svg>
        {{-- Moon icon (shown in light mode) --}}
        <svg id="cbm-icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- ===== Animated Background Blobs ===== --}}
    <div class="cbm-bg-blob cbm-blob-1"></div>
    <div class="cbm-bg-blob cbm-blob-2"></div>
    <div class="cbm-bg-blob cbm-blob-3"></div>

    <div class="cbm-login-wrapper">

        {{-- ===== Left Panel ===== --}}
        <div class="cbm-left-panel"
             style="background-image: linear-gradient(160deg, rgba(15,23,42,0.82) 0%, rgba(30,64,175,0.72) 50%, rgba(15,23,42,0.88) 100%), url('{{ asset('build/assets/leftside.jpg') }}');">

            <div class="cbm-noise"></div>

            {{-- Logo --}}
            <div class="cbm-logo-badge">
                <div class="cbm-logo-icon">
                    <img src="{{ asset('images/lion-logo.png') }}" alt="Logo" style="width: 32px; height: 32px; object-fit: contain;">
                </div>
                <div class="cbm-logo-text-wrap">
                    <span class="cbm-logo-text">Cabin Core</span>
                    <span class="cbm-logo-sub">Batam Aero Technic</span>
                </div>
            </div>

            {{-- Hero content --}}
            <div class="cbm-hero">
                <div class="cbm-badge-pill">
                    <span class="cbm-badge-dot"></span>
                    Cabin Maintenance System
                </div>

                <h1 class="cbm-hero-title">
                    Monitor Semua<br>
                    <span class="cbm-hero-gradient">Station</span> Sekarang
                </h1>

                <p class="cbm-hero-desc">
                    Kelola operasional cabin maintenance seluruh stasiun dalam satu platform yang terintegrasi dan efisien.
                </p>

                <div class="cbm-stats-row">
                    <div class="cbm-stat-card">
                        <div class="cbm-stat-num">24/7</div>
                        <div class="cbm-stat-label">Monitoring</div>
                    </div>
                    <div class="cbm-stat-divider"></div>
                    <div class="cbm-stat-card">
                        <div class="cbm-stat-num">Real-time</div>
                        <div class="cbm-stat-label">Data Update</div>
                    </div>
                    <div class="cbm-stat-divider"></div>
                    <div class="cbm-stat-card">
                        <div class="cbm-stat-num">All</div>
                        <div class="cbm-stat-label">Stations</div>
                    </div>
                </div>
            </div>

            <div class="cbm-left-footer">
                <span>&copy; {{ date('Y') }} Cabin Core. All rights reserved.</span>
            </div>
        </div>

        {{-- ===== Right Panel ===== --}}
        <div class="cbm-right-panel">

            <div class="cbm-form-card">

                <div class="cbm-form-header">
                    <div class="cbm-form-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.375rem;height:1.375rem;">
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="cbm-form-title">Selamat Datang</h2>
                        <p class="cbm-form-subtitle">Masuk ke akun karyawan Anda</p>
                    </div>
                </div>

                <form wire:submit="login" class="cbm-form" novalidate>

                    {{-- NIK Field --}}
                    <div class="cbm-field-group">
                        <label for="nik" class="cbm-label">ID Karyawan</label>
                        <div class="cbm-input-wrap" id="nik-wrap">
                            <div class="cbm-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.125rem;height:1.125rem;">
                                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                wire:model="nik"
                                type="text"
                                id="nik"
                                class="cbm-input"
                                placeholder="Masukkan ID karyawan Anda"
                                autocomplete="username"
                                onfocus="document.getElementById('nik-wrap').classList.add('cbm-input-wrap--focused')"
                                onblur="document.getElementById('nik-wrap').classList.remove('cbm-input-wrap--focused')"
                            >
                        </div>
                        @error('nik')
                            <div class="cbm-error-msg">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    {{-- Password Field --}}
                    <div class="cbm-field-group">
                        <div class="cbm-label-row">
                            <label for="password" class="cbm-label">Password</label>
                            <a href="#" class="cbm-forgot-link" tabindex="-1">Lupa password?</a>
                        </div>
                        <div class="cbm-input-wrap" id="password-wrap">
                            <div class="cbm-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.125rem;height:1.125rem;">
                                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                wire:model="password"
                                type="password"
                                id="password"
                                class="cbm-input"
                                placeholder="Masukkan password Anda"
                                autocomplete="current-password"
                                onfocus="document.getElementById('password-wrap').classList.add('cbm-input-wrap--focused')"
                                onblur="document.getElementById('password-wrap').classList.remove('cbm-input-wrap--focused')"
                            >
                            <button
                                type="button"
                                class="cbm-toggle-pw"
                                aria-label="Toggle password visibility"
                                onclick="(function(){var inp=document.getElementById('password'),eyeOn=document.getElementById('cbm-eye-on'),eyeOff=document.getElementById('cbm-eye-off');if(inp.type==='password'){inp.type='text';eyeOn.style.display='none';eyeOff.style.display='block';}else{inp.type='password';eyeOn.style.display='block';eyeOff.style.display='none';}})()"
                            >
                                <svg id="cbm-eye-on" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.125rem;height:1.125rem;">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" />
                                </svg>
                                <svg id="cbm-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.125rem;height:1.125rem;display:none;">
                                    <path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z" />
                                    <path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.22 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243zM12 20.25c-1.72 0-3.35-.43-4.787-1.19l-1.58-1.58C6.998 18.41 8.436 19.1 10 19.41v.09h.001c.657.099 1.33.15 2.001.15.923 0 1.82-.1 2.682-.289l-1.617-1.617A8.259 8.259 0 0112 18a8.26 8.26 0 01-1.066-.067l-.001.001z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="cbm-error-msg">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="cbm-submit-btn">
                        <span wire:loading.remove wire:target="login" class="cbm-btn-content">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.125rem;height:1.125rem;">
                                <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 19.5V18h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5zm0 3a.75.75 0 000 1.5A2.25 2.25 0 0118 8.25a.75.75 0 001.5 0 3.75 3.75 0 00-3.75-3.75z" clip-rule="evenodd" />
                            </svg>
                            Masuk ke Sistem
                        </span>
                        <span wire:loading wire:target="login" class="cbm-btn-loading">
                            <svg class="cbm-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width:1.125rem;height:1.125rem;">
                                <circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>

                </form>

                <div class="cbm-info-note">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;flex-shrink:0;color:#60a5fa;margin-top:0.05rem;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                    <p>Login pertama kali? Anda akan diminta untuk mengganti password default sebelum mengakses sistem.</p>
                </div>

            </div>

            <p class="cbm-powered-by">Powered by <strong>Lion Air Group</strong></p>

        </div>
    </div>
</div>

<style>
/* ─── Transition layer (smooth theme switch) ─────────────── */
.cbm-login-root,
.cbm-right-panel,
.cbm-form-card,
.cbm-input-wrap,
.cbm-input,
.cbm-bg-blob,
.cbm-info-note,
.cbm-theme-btn {
    transition: background .35s ease, color .35s ease, border-color .35s ease, box-shadow .35s ease;
}

/* ─── Theme Toggle Button ────────────────────────────────── */
.cbm-theme-btn {
    position: fixed;
    top: 1.25rem;
    right: 1.25rem;
    z-index: 100;
    width: 2.75rem;
    height: 2.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    background: rgba(255,255,255,.10);
    color: rgba(255,255,255,.70);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.15);
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
}
.cbm-theme-btn svg { width: 1.125rem; height: 1.125rem; }
.cbm-theme-btn:hover { background: rgba(255,255,255,.18); color: white; }

/* ─── DARK MODE (default) ────────────────────────────────── */
.cbm-login-root{position:relative;width:100%;min-height:100vh;background:#0b0f1e;overflow:hidden;display:flex;align-items:stretch;}
.cbm-login-wrapper{display:flex;width:100%;min-height:100vh;position:relative;z-index:1;}
.cbm-bg-blob{position:fixed;border-radius:50%;filter:blur(80px);opacity:.15;animation:cbm-float 8s ease-in-out infinite;pointer-events:none;}
.cbm-blob-1{width:500px;height:500px;background:radial-gradient(circle,#3b82f6,#1d4ed8);top:-100px;left:-100px;animation-delay:0s;}
.cbm-blob-2{width:400px;height:400px;background:radial-gradient(circle,#8b5cf6,#6d28d9);bottom:-80px;right:25%;animation-delay:-3s;}
.cbm-blob-3{width:300px;height:300px;background:radial-gradient(circle,#06b6d4,#0891b2);top:40%;right:-60px;animation-delay:-5s;}
@keyframes cbm-float{0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-30px) scale(1.05);}}
.cbm-left-panel{display:none;position:relative;flex-direction:column;justify-content:space-between;padding:2.5rem;background-size:cover;background-position:center;overflow:hidden;}
@media(min-width:1024px){.cbm-left-panel{display:flex;width:55%;}}
.cbm-noise{position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;opacity:.5;}
.cbm-logo-badge{display:inline-flex;align-items:center;gap:.625rem;background:rgba(255,255,255,.12);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.2);padding:.5rem 1rem .5rem .625rem;border-radius:999px;width:fit-content;}
.cbm-logo-icon{width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:50%;color:white;}
.cbm-logo-text{color:white;font-weight:700;font-size:.9375rem;letter-spacing:.01em;}
.cbm-hero{margin-top:auto;margin-bottom:3rem;}
.cbm-badge-pill{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.9);font-size:.8125rem;font-weight:600;padding:.375rem .875rem;border-radius:999px;margin-bottom:1.5rem;letter-spacing:.02em;}
.cbm-badge-dot{width:7px;height:7px;background:#4ade80;border-radius:50%;animation:cbm-pulse 2s ease-in-out infinite;}
@keyframes cbm-pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.6;transform:scale(1.2);}}
.cbm-hero-title{font-size:clamp(2rem,4vw,3.5rem);font-weight:800;color:white;line-height:1.15;margin:0 0 1rem 0;letter-spacing:-.02em;}
.cbm-hero-gradient{background:linear-gradient(90deg,#60a5fa,#a78bfa,#38bdf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.cbm-hero-desc{color:rgba(255,255,255,.65);font-size:1rem;line-height:1.7;max-width:28rem;margin:0 0 2.5rem 0;}
.cbm-stats-row{display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;}
.cbm-stat-num{font-size:1.25rem;font-weight:800;color:white;line-height:1;}
.cbm-stat-label{font-size:.75rem;color:rgba(255,255,255,.55);margin-top:.25rem;font-weight:500;}
.cbm-stat-divider{width:1px;height:2.5rem;background:rgba(255,255,255,.2);}
.cbm-left-footer{font-size:.75rem;color:rgba(255,255,255,.4);}
.cbm-right-panel{width:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1.25rem;background:#0d1117;}
@media(min-width:1024px){.cbm-right-panel{width:45%;padding:3rem 2rem;}}
.cbm-form-card{width:100%;max-width:26rem;background:rgba(255,255,255,.04);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.10);border-radius:1.5rem;padding:2.5rem 1.75rem;animation:cbm-slide-up .5s cubic-bezier(.16,1,.3,1) both;}
@media(min-width:640px){.cbm-form-card{padding:2.5rem;}}
@keyframes cbm-slide-up{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
.cbm-form-header{display:flex;align-items:center;gap:1rem;margin-bottom:2rem;}
.cbm-form-icon-wrap{width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:.875rem;color:white;flex-shrink:0;box-shadow:0 8px 20px rgba(59,130,246,.35);}
.cbm-form-title{font-size:1.625rem;font-weight:800;color:#f1f5f9;margin:0;letter-spacing:-.02em;line-height:1.2;}
.cbm-form-subtitle{font-size:.875rem;color:rgba(255,255,255,.45);margin:.2rem 0 0 0;}
.cbm-form{display:flex;flex-direction:column;gap:1.25rem;}
.cbm-field-group{display:flex;flex-direction:column;gap:.5rem;}
.cbm-label{font-size:.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:rgba(255,255,255,.50);}
.cbm-label-row{display:flex;align-items:center;justify-content:space-between;}
.cbm-forgot-link{font-size:.8125rem;font-weight:600;color:#60a5fa;text-decoration:none;transition:color .15s;}
.cbm-forgot-link:hover{color:#93c5fd;}
.cbm-input-wrap{position:relative;display:flex;align-items:center;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.10);border-radius:.875rem;transition:border-color .2s,background .2s,box-shadow .2s;}
.cbm-input-wrap:hover{border-color:rgba(255,255,255,.18);background:rgba(255,255,255,.08);}
.cbm-input-wrap--focused{border-color:#3b82f6!important;background:rgba(59,130,246,.08)!important;box-shadow:0 0 0 3px rgba(59,130,246,.18)!important;}
.cbm-input-icon{position:absolute;left:.875rem;display:flex;align-items:center;pointer-events:none;color:rgba(255,255,255,.30);transition:color .2s;}
.cbm-input-wrap--focused .cbm-input-icon{color:#60a5fa;}
.cbm-input{width:100%;background:transparent;border:none;outline:none;padding:.875rem 1rem .875rem 2.75rem;font-size:.9375rem;font-family:inherit;color:#f1f5f9;font-weight:500;}
.cbm-input:focus{outline:none!important;box-shadow:none!important;border-color:transparent!important;--tw-ring-shadow:0 0 #0000!important;}
.cbm-input::placeholder{color:rgba(255,255,255,.25);font-weight:400;}
.cbm-toggle-pw{position:absolute;right:.75rem;display:flex;align-items:center;justify-content:center;width:2rem;height:2rem;border:none;background:transparent;cursor:pointer;color:rgba(255,255,255,.30);border-radius:.5rem;transition:color .15s,background .15s;}
.cbm-toggle-pw:hover{color:rgba(255,255,255,.70);background:rgba(255,255,255,.08);}
.cbm-error-msg{display:flex;align-items:center;gap:.375rem;color:#f87171;font-size:.8125rem;font-weight:500;animation:cbm-shake .3s ease;}
@keyframes cbm-shake{0%,100%{transform:translateX(0);}25%{transform:translateX(-4px);}75%{transform:translateX(4px);}}
.cbm-submit-btn{width:100%;margin-top:.5rem;display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.9375rem 1.5rem;background:linear-gradient(135deg,#2563eb,#4f46e5);color:white;font-family:inherit;font-size:.9375rem;font-weight:700;border:none;border-radius:.875rem;cursor:pointer;transition:opacity .2s,transform .15s,box-shadow .2s;box-shadow:0 6px 20px rgba(37,99,235,.40),0 2px 6px rgba(0,0,0,.20);letter-spacing:.01em;position:relative;overflow:hidden;}
.cbm-submit-btn::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.15),rgba(255,255,255,0) 60%);border-radius:inherit;}
.cbm-submit-btn:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 10px 28px rgba(37,99,235,.50),0 4px 10px rgba(0,0,0,.25);}
.cbm-submit-btn:active{transform:translateY(0) scale(.98);opacity:1;}
.cbm-btn-content{display:flex;align-items:center;gap:.5rem;}
.cbm-btn-loading{display:flex;align-items:center;gap:.625rem;}
@keyframes cbm-spin{to{transform:rotate(360deg);}}
.cbm-spinner{animation:cbm-spin .8s linear infinite;}
.cbm-info-note{display:flex;align-items:flex-start;gap:.5rem;margin-top:1.5rem;padding:.875rem 1rem;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.18);border-radius:.75rem;color:rgba(148,163,184,.80);}
.cbm-info-note p{font-size:.8125rem;line-height:1.6;margin:0;}
.cbm-powered-by{margin-top:1.5rem;font-size:.75rem;color:rgba(255,255,255,.22);text-align:center;}
.cbm-powered-by strong{color:rgba(255,255,255,.35);font-weight:600;}

/* ─── LIGHT MODE overrides (.cbm-light) ──────────────────── */
.cbm-light.cbm-login-root { background: #f0f4ff; }
.cbm-light .cbm-bg-blob { opacity: .10; }
.cbm-light .cbm-blob-1 { background: radial-gradient(circle,#93c5fd,#60a5fa); }
.cbm-light .cbm-blob-2 { background: radial-gradient(circle,#c4b5fd,#a78bfa); }
.cbm-light .cbm-blob-3 { background: radial-gradient(circle,#67e8f9,#22d3ee); }

/* Right panel */
.cbm-light .cbm-right-panel { background: #f0f4ff; }

/* Form card */
.cbm-light .cbm-form-card {
    background: rgba(255,255,255,.85);
    border-color: rgba(148,163,184,.25);
    box-shadow: 0 8px 40px rgba(37,99,235,.10), 0 2px 8px rgba(0,0,0,.06);
}

/* Header text */
.cbm-light .cbm-form-title { color: #0f172a; }
.cbm-light .cbm-form-subtitle { color: #64748b; }

/* Labels */
.cbm-light .cbm-label { color: #64748b; }

/* Forgot link — stays blue, adjust shade */
.cbm-light .cbm-forgot-link { color: #2563eb; }
.cbm-light .cbm-forgot-link:hover { color: #1d4ed8; }

/* Inputs */
.cbm-light .cbm-input-wrap {
    background: #f1f5f9;
    border-color: #e2e8f0;
}
.cbm-light .cbm-input-wrap:hover {
    background: #e8eef8;
    border-color: #cbd5e1;
}
.cbm-light .cbm-input-wrap--focused {
    border-color: #3b82f6 !important;
    background: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(59,130,246,.15) !important;
}
.cbm-light .cbm-input-icon { color: #94a3b8; }
.cbm-light .cbm-input-wrap--focused .cbm-input-icon { color: #2563eb; }
.cbm-light .cbm-input { color: #0f172a; }
.cbm-light .cbm-input::placeholder { color: #94a3b8; }

/* Toggle password */
.cbm-light .cbm-toggle-pw { color: #94a3b8; }
.cbm-light .cbm-toggle-pw:hover { color: #475569; background: rgba(0,0,0,.06); }

/* Info note */
.cbm-light .cbm-info-note {
    background: #eff6ff;
    border-color: rgba(59,130,246,.25);
    color: #475569;
}

/* Powered by */
.cbm-light .cbm-powered-by { color: #94a3b8; }
.cbm-light .cbm-powered-by strong { color: #64748b; }

/* Theme toggle button in light mode */
.cbm-light .cbm-theme-btn {
    background: rgba(255,255,255,.85);
    color: #475569;
    border-color: rgba(148,163,184,.35);
    box-shadow: 0 4px 12px rgba(0,0,0,.10);
}
.cbm-light .cbm-theme-btn:hover {
    background: white;
    color: #1e40af;
}
</style>

<script>
(function () {
    var root = document.getElementById('cbm-root');
    var sun  = document.getElementById('cbm-icon-sun');
    var moon = document.getElementById('cbm-icon-moon');

    function applyTheme(theme) {
        if (theme === 'light') {
            root.classList.add('cbm-light');
            sun.style.display  = 'none';
            moon.style.display = 'block';
        } else {
            root.classList.remove('cbm-light');
            sun.style.display  = 'block';
            moon.style.display = 'none';
        }
    }

    // On load: respect saved preference, then system preference
    var saved = localStorage.getItem('cbm-theme');
    if (saved) {
        applyTheme(saved);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
        applyTheme('light');
    } else {
        applyTheme('dark');
    }

    window.cbmToggleTheme = function () {
        var isLight = root.classList.contains('cbm-light');
        var next = isLight ? 'dark' : 'light';
        localStorage.setItem('cbm-theme', next);
        applyTheme(next);
    };
})();
</script>
