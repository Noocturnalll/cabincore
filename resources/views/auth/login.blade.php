<x-guest-layout>
    {{-- Session Status --}}
    @if (session('status'))
        <div class="g-status">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ session('status') }}
        </div>
    @endif

    {{-- Any auth error --}}
    @if ($errors->any())
        <div class="g-err-alert">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            Periksa kembali kredensial Anda.
        </div>
    @endif

    <div class="g-card">
        <div style="text-align:center; margin-bottom:1.75rem;">
            <h1 class="g-form-title" style="text-align:center; margin-bottom:.375rem;">Selamat Datang</h1>
            <p class="g-form-sub" style="text-align:center; margin-bottom:0;">Masuk ke akun Cabin Core Anda</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div class="g-input-group">
                <label class="g-label" for="email">Alamat Email</label>
                <div class="g-input-wrap">
                    <span class="g-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                    </span>
                    <input id="email" class="g-input {{ $errors->has('email') ? 'g-input-error' : '' }}"
                           type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username"
                           placeholder="nama@batam-aero.com">
                </div>
                @error('email')
                    <span class="g-error-msg">{{ $message }}</span>
                @enderror
            </div>

            {{-- Password --}}
            <div class="g-input-group" style="margin-bottom:1.25rem;">
                <label class="g-label" for="password">Password</label>
                <div class="g-input-wrap">
                    <span class="g-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    </span>
                    <input id="password" class="g-input {{ $errors->has('password') ? 'g-input-error' : '' }}"
                           type="password" name="password"
                           required autocomplete="current-password"
                           placeholder="••••••••">
                </div>
                @error('password')
                    <span class="g-error-msg">{{ $message }}</span>
                @enderror
            </div>

            {{-- Remember Me + Forgot --}}
            <div class="g-row">
                <label class="g-check-label">
                    <input id="remember_me" type="checkbox" name="remember">
                    <span>Ingat saya</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="g-forgot" href="{{ route('password.request') }}">Lupa password?</a>
                @endif
            </div>

            {{-- Submit --}}
            <button type="submit" class="g-submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                Masuk ke Dashboard
            </button>
        </form>
    </div>
</x-guest-layout>