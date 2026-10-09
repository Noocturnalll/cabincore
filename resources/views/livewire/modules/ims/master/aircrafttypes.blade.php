<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">Master Data</span>
            <h1 class="mod-title">Tipe Pesawat</h1>
            <p class="mod-subtitle">Kelola data Tipe Pesawat untuk sistem IMS.</p>
        </div>
        
        <div class="mod-actions">
            <button wire:click="create" class="mod-btn-primary">
                + Tambah Data
            </button>
        </div>
    </div>
    <x-flash />

    <div class="mod-card">
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="max-width: 18.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari..." class="mod-search-input" wire:model.live.debounce.300ms="search">
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pabrikan</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->code }}</td>
                            <td>{{ $record->manufacturer }}</td>
                            <td>{{ $record->model }}</td>
                            <td>
                                @if($record->is_active)
                                    <span class="mod-badge-closed">Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Nonaktif</span>
                                @endif
                            </td>
                            <td style="text-align:right;"><div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                <button wire:click="edit({{ $record->id }})" class="mod-action-btn">Edit</button>
                                <button wire:click="delete({{ $record->id }})" class="mod-action-btn" style="color: #ef4444; border-color: #ef4444;" wire:confirm="Yakin ingin menghapus data ini?">Hapus</button>
                            </div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Data kosong.</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mod-pagination">
            {{ $records->links('pagination::tailwind') }}
        </div>
    </div>

    <!-- Modal Form -->
    @if($isOpen)
    <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
        <div style="background: var(--cbm-card-bg); width: 100%; max-width: 31.25rem; border-radius: 1rem; box-shadow: var(--cbm-card-shadow); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600;">{{ $isEdit ? 'Edit Data' : 'Tambah Data' }}</h3>
                <button wire:click="$set('isOpen', false)" style="background: none; border: none; cursor: pointer; color: var(--cbm-text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save">
                <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Kode</label>
                    <input type="text" wire:model="form.code" class="mod-search-input" style="width: 100%;">
                    @error('form.code') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Pabrikan</label>
                    <input type="text" wire:model="form.manufacturer" class="mod-search-input" style="width: 100%;">
                    @error('form.manufacturer') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Model</label>
                    <input type="text" wire:model="form.model" class="mod-search-input" style="width: 100%;">
                    @error('form.model') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                        <input type="checkbox" wire:model="form.is_active" style="width: 1rem; height: 1rem;">
                        Status Aktif
                    </label>
                </div>
                </div>
                
                <div style="padding: 1rem 1.5rem; background: var(--cbm-sidebar-bg); border-top: 1px solid var(--cbm-border); display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" wire:click="$set('isOpen', false)" class="mod-action-btn">Batal</button>
                    <button type="submit" class="mod-btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
