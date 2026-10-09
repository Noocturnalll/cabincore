<div>
    <x-master.page-header title="Master Pesawat" subtitle="Daftar registrasi pesawat beserta tipe dan maskapai." accent="purple" create-label="Tambah Pesawat" />

    <x-flash />

    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari registrasi, tipe, atau maskapai..." aria-label="Cari pesawat">
                </div>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($aircrafts->total()) }} pesawat</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr><th>REGISTRASI</th><th>TIPE PESAWAT</th><th>MASKAPAI</th><th>WG</th><th>STATUS</th><th style="text-align:right;">AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse($aircrafts as $aircraft)
                        <tr wire:key="aircraft-{{ $aircraft->id }}">
                            <td><div class="mod-aircraft-name">{{ $aircraft->registration }}</div></td>
                            <td>{{ $aircraft->tipe }}</td>
                            <td>{{ $aircraft->maskapai }}</td>
                            <td>{{ $aircraft->wg ?? '-' }}</td>
                            <td>
                                @if($aircraft->status === 'Aktif')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Tidak Aktif</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    <button type="button" wire:click="edit({{ $aircraft->id }})" class="mod-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $aircraft->id }})" wire:confirm="Hapus pesawat {{ $aircraft->registration }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ $search ? 'Tidak ada data yang cocok' : 'Belum ada data pesawat' }}</div>
                                <div class="mod-empty-sub">{{ $search ? 'Ubah kata kunci pencarian.' : 'Klik "Tambah Pesawat" untuk mulai.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($aircrafts->hasPages())
            <div class="mod-pagination">{{ $aircrafts->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="$isEditMode ? 'Edit Pesawat' : 'Tambah Pesawat Baru'" :submit="$isEditMode ? 'update' : 'store'" max-width="28rem">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ac-reg">Registrasi *</label>
            <input id="ac-reg" type="text" wire:model="registration" class="cbm-form-input" maxlength="20" placeholder="PK-ABC" style="text-transform:uppercase;" autocomplete="off">
            @error('registration') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ac-tipe">Tipe pesawat *</label>
            <input id="ac-tipe" type="text" wire:model="tipe" class="cbm-form-input" placeholder="Boeing 737-800">
            @error('tipe') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ac-airline">Maskapai *</label>
            <div class="cbm-select-wrap">
                <select id="ac-airline" wire:model="maskapai" class="cbm-form-select">
                    <option value="">-- Pilih maskapai --</option>
                    @foreach($this->airlineOptions() as $airline)<option value="{{ $airline }}">{{ $airline }}</option>@endforeach
                </select>
            </div>
            @error('maskapai') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ac-wg">Working Group (WG)</label>
            <input id="ac-wg" type="text" wire:model="wg" class="cbm-form-input" maxlength="10" placeholder="contoh: WG 05" autocomplete="off">
            @error('wg') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="ac-status">Status *</label>
            <div class="cbm-select-wrap">
                <select id="ac-status" wire:model="status" class="cbm-form-select">
                    <option value="Aktif">Aktif</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </select>
            </div>
            @error('status') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
