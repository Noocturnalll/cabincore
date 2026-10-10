<div>
    <x-master.page-header title="Master Divisi" subtitle="Divisi organisasi yang dipakai pada data pengguna." accent="blue" create-label="Tambah Divisi" />

    <x-flash />

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari divisi..." aria-label="Cari divisi">
                </div>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($divisions->total()) }} divisi</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr><th>NAMA DIVISI</th><th style="text-align:center;">PENGGUNA</th><th>STATUS</th><th style="text-align:right;">AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse($divisions as $division)
                        <tr wire:key="division-{{ $division->id }}">
                            <td><div class="mod-aircraft-name">{{ $division->name }}</div></td>
                            <td style="text-align:center;">{{ $division->users_count }}</td>
                            <td>
                                @if($division->status === 'Aktif')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Nonaktif</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    <button type="button" wire:click="edit({{ $division->id }})" class="mod-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $division->id }})" wire:confirm="Hapus divisi {{ $division->name }}?{{ $division->users_count > 0 ? ' (' . $division->users_count . ' pengguna terkait akan dilepas dari divisi ini)' : '' }}" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);" title="Hapus divisi">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ $search ? 'Tidak ada data yang cocok' : 'Belum ada divisi' }}</div>
                                <div class="mod-empty-sub">{{ $search ? 'Ubah kata kunci pencarian.' : 'Klik "Tambah Divisi" untuk mulai.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($divisions->hasPages())
            <div class="mod-pagination">{{ $divisions->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="$isEditMode ? 'Edit Divisi' : 'Tambah Divisi Baru'" :submit="$isEditMode ? 'update' : 'store'" max-width="26rem">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="dv-name">Nama divisi *</label>
            <input id="dv-name" type="text" wire:model="name" class="cbm-form-input" maxlength="100" autocomplete="off">
            @error('name') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="dv-status">Status *</label>
            <div class="cbm-select-wrap">
                <select id="dv-status" wire:model="status" class="cbm-form-select">
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
            @error('status') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
