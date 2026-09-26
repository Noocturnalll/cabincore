<div>
    <div class="cbm-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, #0ea5e9, #3b82f6); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(14,165,233,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.025em;">Bandara / Station</h1>
                <p class="cbm-greeting-sub" style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.25rem;">Kelola data bandara dan station operasional</p>
            </div>
        </div>
    </div>

    <div class="cbm-card">
        <div class="cbm-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 0;">
            <div style="position: relative; width: 300px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.25rem; height: 1.25rem; color: var(--cbm-text-muted);">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari Bandara / Station..." class="cbm-input" style="padding-left: 2.75rem; width: 100%; height: 2.75rem; border-radius: .875rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" wire:model.live.debounce.300ms="search">
            </div>
            <button type="button" wire:click.prevent="create" class="cbm-btn cbm-btn-primary" style="background: linear-gradient(135deg, #0ea5e9, #3b82f6); box-shadow: 0 4px 12px rgba(14,165,233,0.3); border: none; color: white; padding: 0 1.5rem; height: 2.75rem; border-radius: .875rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Tambah Data
            </button>
        </div>
        
        <div class="cbm-card-body" style="padding: 0;">
            <div class="cbm-table-wrapper">
                <table class="cbm-table">
                    <thead>
                        <tr>
                            <th>Kode IATA</th>
                            <th>Nama Bandara</th>
                            <th>Kota</th>
                            <th>Status</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($airports as $airport)
                        <tr>
                            <td class="fw-bold">{{ $airport->kode }}</td>
                            <td>{{ $airport->nama }}</td>
                            <td>{{ $airport->kota }}</td>
                            <td>
                                @if($airport->status == 'Aktif')
                                    <span style="background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34,197,94,0.2);">Aktif</span>
                                @else
                                    <span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(239,68,68,0.2);">Tidak Aktif</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button type="button" wire:click.prevent="edit({{ $airport->id }})" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer; padding: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path d="M2.695 14.763l-1.262 3.152a.5.5 0 00.65.65l3.152-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                                <button type="button" onclick="confirm('Yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click.prevent="delete({{ $airport->id }})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; margin-left: 0.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="cbm-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    <div>
                                        <h4>Belum Ada Data</h4>
                                        <p>Tidak ada data bandara yang ditemukan.</p>
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
        <div style="background: var(--cbm-card-bg); width: 100%; max-width: 500px; border-radius: 1.25rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-card-border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text);">{{ $isEditMode ? 'Edit Bandara' : 'Tambah Bandara Baru' }}</h3>
                <button wire:click="close" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Kode IATA</label>
                    <input type="text" wire:model="kode" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" placeholder="Contoh: CGK">
                    @error('kode') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Nama Bandara</label>
                    <input type="text" wire:model="nama" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" placeholder="Contoh: Soekarno-Hatta">
                    @error('nama') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Kota</label>
                    <input type="text" wire:model="kota" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" placeholder="Contoh: Tangerang">
                    @error('kota') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Status</label>
                    <select wire:model="status" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                    @error('status') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
            </div>
            <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--cbm-card-border); background: var(--cbm-bg); display: flex; justify-content: flex-end; gap: .75rem;">
                <button type="button" wire:click.prevent="close" style="padding: .75rem 1.5rem; border-radius: .75rem; background: transparent; border: 1px solid var(--cbm-card-border); color: var(--cbm-text); font-weight: 600; cursor: pointer;">Batal</button>
                <button type="button" wire:click.prevent="{{ $isEditMode ? 'update' : 'store' }}" style="padding: .75rem 1.5rem; border-radius: .75rem; background: linear-gradient(135deg, #0ea5e9, #3b82f6); border: none; color: white; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(14,165,233,0.3);">{{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Data' }}</button>
            </div>
        </div>
    </div>
    @endif
</div>
