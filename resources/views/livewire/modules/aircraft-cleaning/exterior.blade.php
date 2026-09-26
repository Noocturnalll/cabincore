<div>
    <div class="cbm-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, #a855f7, #d946ef); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(168,85,247,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.428-1.428L13.5 18.75l1.178-.394a2.25 2.25 0 001.428-1.428l.394-1.183.394 1.183a2.25 2.25 0 001.428 1.428l1.178.394-1.178.394a2.25 2.25 0 00-1.428 1.428z" /></svg>
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.025em;">Exterior Cleaning (DCE)</h1>
                <p class="cbm-greeting-sub" style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.25rem;">Log dan laporan Deep Cleaning Exterior pesawat</p>
            </div>
        </div>
    </div>

    <div class="cbm-card">
        <div class="cbm-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 0;">
            <div style="position: relative; width: 300px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.25rem; height: 1.25rem; color: var(--cbm-text-muted);">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari Exterior Cleaning (DCE)..." class="cbm-input" style="padding-left: 2.75rem; width: 100%; height: 2.75rem; border-radius: .875rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" wire:model.live.debounce.300ms="search">
            </div>
            <button wire:click="create()" class="cbm-btn cbm-btn-primary" style="background: linear-gradient(135deg, #a855f7, #d946ef); box-shadow: 0 4px 12px rgba(168,85,247,0.3); border: none; color: white; padding: 0 1.5rem; height: 2.75rem; border-radius: .875rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
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
                            <th>Registrasi</th>
                            <th>Tanggal Masuk</th>
                            <th>Area Cuci / Shift</th>
                            <th>Status</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cleanings as $cln)
                        <tr>
                            <td class="fw-bold">{{ $cln->aircraft_registration }}</td>
                            <td>{{ \Carbon\Carbon::parse($cln->date)->format('d M Y') }}</td>
                            <td>{{ $cln->shift }}</td>
                            <td>
                                @if($cln->status === 'Aktif' || $cln->status === 'Open')
                                    <span style="background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34,197,94,0.2);">{{ $cln->status }}</span>
                                @else
                                    <span style="background: rgba(14,165,233,0.1); color: #0ea5e9; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(14,165,233,0.2);">{{ $cln->status }}</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button wire:click="edit({{ $cln->id }})" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer; padding: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path d="M2.695 14.763l-1.262 3.152a.5.5 0 00.65.65l3.152-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                                <button wire:click="deleteConfirm({{ $cln->id }})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; margin-left: 0.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="cbm-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    <div>
                                        <h4>Belum Ada Data</h4>
                                        <p>Tidak ada data exterior cleaning yang ditemukan.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="padding: 1.25rem; border-top: 1px solid var(--cbm-card-border);">
            {{ $cleanings->links('pagination::tailwind') }}
        </div>
    </div>

    <!-- Modal Form -->
    @if($isModalOpen)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; backdrop-filter: blur(4px);">
        <div style="background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: 1.25rem; width: 100%; max-width: 32rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-card-border); display: flex; justify-content: space-between; align-items: center; background: var(--cbm-nav-hover);">
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--cbm-text); margin: 0;">{{ $cleaningId ? 'Edit' : 'Tambah' }} Exterior (DCE)</h3>
                <button wire:click="closeModal()" style="background: transparent; border: none; color: var(--cbm-text-muted); cursor: pointer; transition: color 0.2s;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Registrasi Pesawat <span style="color: #ef4444;">*</span></label>
                        <input type="text" wire:model="aircraft_registration" class="cbm-input" placeholder="PK-..." style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                        @error('aircraft_registration') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Station <span style="color: #ef4444;">*</span></label>
                        <select wire:model="station" class="cbm-input" style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                            <option value="">-- Pilih --</option>
                            <option value="CGK">CGK</option>
                            <option value="HLP">HLP</option>
                            <option value="SUB">SUB</option>
                            <option value="KNO">KNO</option>
                            <option value="UPG">UPG</option>
                            <option value="DPS">DPS</option>
                        </select>
                        @error('station') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Tanggal Masuk <span style="color: #ef4444;">*</span></label>
                        <input type="date" wire:model="date" class="cbm-input" style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                        @error('date') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Shift <span style="color: #ef4444;">*</span></label>
                        <select wire:model="shift" class="cbm-input" style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                            <option value="">-- Pilih --</option>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Night">Night</option>
                        </select>
                        @error('shift') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Operator / Tim</label>
                        <input type="text" wire:model="operator" class="cbm-input" placeholder="Nama / Tim" style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                        @error('operator') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Status <span style="color: #ef4444;">*</span></label>
                        <select wire:model="status" class="cbm-input" style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                            <option value="Aktif">Aktif</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                        @error('status') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Remarks</label>
                    <textarea wire:model="remarks" class="cbm-input" rows="3" placeholder="Catatan (Opsional)" style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text); resize: vertical;"></textarea>
                    @error('remarks') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                    <button type="button" wire:click="closeModal()" class="cbm-btn" style="padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; background: transparent; border: 1px solid var(--cbm-input-border); color: var(--cbm-text); cursor: pointer;">Batal</button>
                    <button type="submit" class="cbm-btn cbm-btn-primary" style="padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 700; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; color: white; cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,0.3); display: flex; align-items: center; gap: 0.5rem;">
                        <svg wire:loading wire:target="save" style="animation: spin 1s linear infinite; width: 1.25rem; height: 1.25rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>