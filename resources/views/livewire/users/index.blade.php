<div>
    <div class="cbm-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(59,130,246,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.025em;">Manajemen Pengguna</h1>
                <p class="cbm-greeting-sub" style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.25rem;">Kelola akun pengguna, peran, dan akses sistem</p>
            </div>
        </div>
    </div>

    <div class="cbm-card">
        <div class="cbm-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 0;">
            <div style="position: relative; width: 300px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.25rem; height: 1.25rem; color: var(--cbm-text-muted);">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari Pengguna (Nama/NIK/Email)..." class="cbm-input" style="padding-left: 2.75rem; width: 100%; height: 2.75rem; border-radius: .875rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" wire:model.live.debounce.300ms="search">
            </div>
            <button type="button" wire:click.prevent="create" class="cbm-btn cbm-btn-primary" style="background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple)); box-shadow: 0 4px 12px rgba(59,130,246,0.3); border: none; color: white; padding: 0 1.5rem; height: 2.75rem; border-radius: .875rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Tambah Pengguna
            </button>
        </div>
        
        <div class="cbm-card-body" style="padding: 0;">
            <div class="cbm-table-wrapper">
                <table class="cbm-table">
                    <thead>
                        <tr>
                            <th>Profil Pengguna</th>
                            <th>Jabatan & Divisi</th>
                            <th>Station</th>
                            <th>Role / Akses</th>
                            <th>Status</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: var(--cbm-bg); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--cbm-blue); border: 1px solid var(--cbm-card-border);">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--cbm-text);">{{ $user->name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--cbm-text-muted);">{{ $user->nik }} &bull; {{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color: var(--cbm-text);">{{ optional($user->position)->name ?? '-' }}</div>
                                <div style="font-size: 0.75rem; color: var(--cbm-text-muted);">{{ optional($user->division)->name ?? '-' }}</div>
                            </td>
                            <td style="font-weight: 600;">{{ $user->station ?? 'Semua' }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span style="background: rgba(168,85,247,0.1); color: #a855f7; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; border: 1px solid rgba(168,85,247,0.2);">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($user->status == 'active')
                                    <span style="background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34,197,94,0.2);">Active</span>
                                @else
                                    <span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(239,68,68,0.2);">Inactive</span>
                                @endif
                            </td>
                            <td style="text-align: right; min-width: 120px;">
                                <button type="button" title="Reset Password (ke default)" onclick="confirm('Yakin mereset password user ini ke default?') || event.stopImmediatePropagation()" wire:click.prevent="resetPassword({{ $user->id }})" style="background: none; border: none; color: var(--cbm-blue); cursor: pointer; padding: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg></button>
                                <button type="button" title="Edit" wire:click.prevent="edit({{ $user->id }})" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer; padding: 0.25rem; margin-left: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path d="M2.695 14.763l-1.262 3.152a.5.5 0 00.65.65l3.152-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                                @if($user->id !== auth()->id())
                                <button type="button" title="Hapus" onclick="confirm('Yakin ingin menghapus pengguna ini?') || event.stopImmediatePropagation()" wire:click.prevent="delete({{ $user->id }})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; margin-left: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="cbm-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    <div>
                                        <h4>Belum Ada Data</h4>
                                        <p>Tidak ada data pengguna yang ditemukan.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    @if($isOpen)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;">
        <div style="background: var(--cbm-card-bg); width: 100%; max-width: 700px; border-radius: 1.25rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-card-border); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text);">{{ $isEditMode ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h3>
                <button wire:click="close" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            
            <div style="padding: 1.5rem; overflow-y: auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">NIK</label>
                        <input type="text" wire:model="nik" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                        @error('nik') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Nama Lengkap</label>
                        <input type="text" wire:model="name" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                        @error('name') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Email</label>
                        <input type="email" wire:model="email" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                        @error('email') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Jabatan</label>
                        <select wire:model="position_id" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                            <option value="">Pilih Jabatan...</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('position_id') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Divisi</label>
                        <select wire:model="division_id" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                            <option value="">Pilih Divisi...</option>
                            @foreach($divisions as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('division_id') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Station / Bandara</label>
                        <select wire:model="station" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                            <option value="">Semua Station</option>
                            @foreach($stations as $s)
                                <option value="{{ $s->kode }}">{{ $s->kode }} - {{ $s->nama }}</option>
                            @endforeach
                        </select>
                        @error('station') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Role / Hak Akses</label>
                        <select wire:model="role" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                            <option value="">Pilih Role...</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                        @error('role') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Status Akun</label>
                        <select wire:model="status" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                    
                    @if(!$isEditMode)
                    <div style="grid-column: span 2; padding: 1rem; background: rgba(59,130,246,0.1); border-radius: .75rem; margin-top: 0.5rem;">
                        <div style="font-size: 0.8125rem; color: var(--cbm-text-muted);">
                            <strong style="color: var(--cbm-blue);">Info:</strong> Password default untuk pengguna baru adalah <code>batam123</code>. Pengguna akan diminta untuk mengganti password ini saat login pertama kali.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--cbm-card-border); background: var(--cbm-bg); display: flex; justify-content: flex-end; gap: .75rem; flex-shrink: 0;">
                <button type="button" wire:click.prevent="close" style="padding: .75rem 1.5rem; border-radius: .75rem; background: transparent; border: 1px solid var(--cbm-card-border); color: var(--cbm-text); font-weight: 600; cursor: pointer;">Batal</button>
                <button type="button" wire:click.prevent="{{ $isEditMode ? 'update' : 'store' }}" style="padding: .75rem 1.5rem; border-radius: .75rem; background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple)); border: none; color: white; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">{{ $isEditMode ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
            </div>
        </div>
    </div>
    @endif
</div>
