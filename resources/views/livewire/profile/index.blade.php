<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title">Profil Saya</div>
            <div class="mod-subtitle">Kelola informasi akun dan preferensi keamanan Anda.</div>
        </div>
    </div>

    <style>
        .profile-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .profile-tab {
            padding: 0.6rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--cbm-text-muted);
            border: 2px solid transparent;
            background: transparent;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: var(--cbm-transition);
        }
        .profile-tab:hover {
            color: var(--cbm-text);
        }
        .profile-tab.active {
            color: var(--cbm-blue);
            border-color: var(--cbm-text);
        }
        
        .cbm-input-group {
            margin-bottom: 1.25rem;
        }
        .cbm-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--cbm-text-sub);
            margin-bottom: 0.5rem;
        }
        .cbm-input {
            width: 100%;
            background: var(--cbm-input-bg);
            border: 1px solid var(--cbm-input-border);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--cbm-text);
            outline: none;
            transition: var(--cbm-transition);
        }
        .cbm-input:focus {
            border-color: var(--cbm-blue);
            background: var(--cbm-nav-hover);
        }
        .cbm-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .cbm-btn {
            background: var(--cbm-blue);
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        .cbm-btn:hover {
            background: var(--cbm-indigo);
        }
        .cbm-btn:active {
            transform: scale(0.98);
        }
        .error-message {
            color: var(--cbm-red);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        /* FAQ Accordion */
        .faq-item {
            border: 1px solid var(--cbm-divider);
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }
        .faq-question {
            padding: 1rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--cbm-nav-hover);
            transition: background 0.2s;
        }
        .faq-question:hover { background: var(--cbm-nav-active); }
        .faq-answer {
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
            color: var(--cbm-text-muted);
            line-height: 1.6;
            border-top: 1px solid var(--cbm-divider);
        }
    </style>

    <div class="mod-card" style="padding: 1.5rem 2rem;">
        
        {{-- Navigation Tabs --}}
        <div class="profile-nav">
            <button type="button" wire:click="setTab('info')" class="profile-tab {{ $activeTab === 'info' ? 'active' : '' }}">Informasi Akun</button>
            <button type="button" wire:click="setTab('security')" class="profile-tab {{ $activeTab === 'security' ? 'active' : '' }}">Keamanan</button>
            <button type="button" wire:click="setTab('audit')" class="profile-tab {{ $activeTab === 'audit' ? 'active' : '' }}">Aktivitas Saya</button>
            <button type="button" wire:click="setTab('faq')" class="profile-tab {{ $activeTab === 'faq' ? 'active' : '' }}">FAQ</button>
        </div>

        {{-- Tab: Informasi Akun --}}
        @if($activeTab === 'info')
        <div style="max-width: 500px;">
            <div class="cbm-input-group">
                <label class="cbm-label">Nama Lengkap</label>
                <input type="text" class="cbm-input" value="{{ $user->name }}" disabled>
            </div>
            <div class="cbm-input-group">
                <label class="cbm-label">ID Karyawan (NIK)</label>
                <input type="text" class="cbm-input" value="{{ $user->nik }}" disabled>
            </div>
            <div class="cbm-input-group">
                <label class="cbm-label">Role</label>
                <input type="text" class="cbm-input" value="{{ $user->roles->first()->name ?? 'No Role' }}" disabled>
            </div>
            <p style="font-size: 0.8125rem; color: var(--cbm-text-sub); margin-top: 1.5rem;">
                * Informasi akun dikelola secara terpusat oleh Super Admin. Jika terdapat kesalahan data, harap hubungi administrator.
            </p>
        </div>
        @endif

        {{-- Tab: Keamanan (Ganti Password) --}}
        @if($activeTab === 'security')
        <div style="max-width: 500px;">
            @if (session()->has('success'))
                <div style="background: rgba(74,222,128,0.15); color: #4ade80; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; font-weight: 600;">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit="changePassword">
                <div class="cbm-input-group">
                    <label class="cbm-label">Password Saat Ini</label>
                    <input type="password" wire:model="current_password" class="cbm-input" placeholder="Masukkan password Anda saat ini">
                    @error('current_password') <span class="error-message">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-input-group">
                    <label class="cbm-label">Password Baru</label>
                    <input type="password" wire:model="password" class="cbm-input" placeholder="Minimal 8 karakter">
                    @error('password') <span class="error-message">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-input-group">
                    <label class="cbm-label">Konfirmasi Password Baru</label>
                    <input type="password" wire:model="password_confirmation" class="cbm-input" placeholder="Ulangi password baru">
                </div>
                
                <button type="submit" class="cbm-btn">Simpan Password</button>
            </form>
        </div>
        @endif

        {{-- Tab: Aktivitas Saya (Audit Trail) --}}
        @if($activeTab === 'audit')
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">Riwayat Aktivitas Terakhir</h3>
            <div class="cbm-table-wrap">
                <table class="cbm-table">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; font-size: 0.75rem; color: var(--cbm-text-sub); font-weight: 600;">WAKTU</th>
                            <th style="padding: 1rem; text-align: left; font-size: 0.75rem; color: var(--cbm-text-sub); font-weight: 600;">AKTIVITAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditLogs as $log)
                        <tr style="border-bottom: 1px solid var(--cbm-divider);">
                            <td style="padding: 1rem; font-size: 0.875rem; color: var(--cbm-text-muted);">
                                {{ \Carbon\Carbon::parse($log['created_at'])->format('d M Y, H:i') }}
                            </td>
                            <td style="padding: 1rem; font-size: 0.875rem; color: var(--cbm-text);">
                                {{ $log['action'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Tab: FAQ --}}
        @if($activeTab === 'faq')
        <div style="max-width: 700px;" x-data="{ selected: 1 }">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.5rem;">Pertanyaan yang Sering Diajukan (FAQ)</h3>
            
            <div class="faq-item">
                <div class="faq-question" @click="selected !== 1 ? selected = 1 : selected = null">
                    <span>Bagaimana cara mengimpor laporan dari Excel?</span>
                    <svg x-show="selected !== 1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    <svg x-show="selected === 1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none; width: 1.25rem; height: 1.25rem; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </div>
                <div class="faq-answer" x-show="selected === 1" x-collapse>
                    Admin CGK dapat menuju modul <strong>Import Data Center</strong>, lalu mengunggah berkas Excel sesuai format yang telah disediakan. Pastikan baris data tidak kosong dan sesuai template dari Document Center.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" @click="selected !== 2 ? selected = 2 : selected = null">
                    <span>Mengapa saya tidak bisa mengakses menu Laporan Eksekutif?</span>
                    <svg x-show="selected !== 2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    <svg x-show="selected === 2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none; width: 1.25rem; height: 1.25rem; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </div>
                <div class="faq-answer" x-show="selected === 2" x-collapse style="display:none;">
                    Akses menu didasarkan pada peranan <i>(role-based)</i>. Menu Laporan Eksekutif hanya dapat diakses secara eksklusif oleh peran Manager dan Super Admin. PIC hanya melihat modul operasional sesuai divisinya.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" @click="selected !== 3 ? selected = 3 : selected = null">
                    <span>Apakah saya bisa mengganti NIK atau nama saya sendiri?</span>
                    <svg x-show="selected !== 3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    <svg x-show="selected === 3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none; width: 1.25rem; height: 1.25rem; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </div>
                <div class="faq-answer" x-show="selected === 3" x-collapse style="display:none;">
                    Tidak bisa. Informasi profil personal seperti NIK dan Nama Lengkap dikunci demi integritas data dan riwayat audit. Anda hanya diizinkan untuk mengubah password Anda.
                </div>
            </div>
            
        </div>
        @endif

    </div>
</div>
