<div>
    <x-master.page-header title="Master Kategori Pekerjaan" subtitle="Kategori pekerjaan per divisi untuk pengelompokan laporan." accent="orange" create-label="Tambah Kategori" />

    <x-flash />

    <div class="mod-card mod-card-accent-orange">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari kode, nama, atau divisi..." aria-label="Cari kategori">
                </div>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($categories->total()) }} kategori</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr><th>KODE</th><th>NAMA KATEGORI</th><th>DIVISI</th><th>STATUS</th><th style="text-align:right;">AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr wire:key="category-{{ $category->id }}">
                            <td><div class="mod-aircraft-name">{{ $category->kode }}</div></td>
                            <td>{{ $category->nama }}</td>
                            <td>{{ $category->divisi }}</td>
                            <td>
                                @if($category->aktif)
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Tidak Aktif</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    <button type="button" wire:click="edit({{ $category->id }})" class="mod-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="Hapus kategori {{ $category->kode }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ $search ? 'Tidak ada data yang cocok' : 'Belum ada kategori' }}</div>
                                <div class="mod-empty-sub">{{ $search ? 'Ubah kata kunci pencarian.' : 'Klik "Tambah Kategori" untuk mulai.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($categories->hasPages())
            <div class="mod-pagination">{{ $categories->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="$isEditMode ? 'Edit Kategori' : 'Tambah Kategori Baru'" :submit="$isEditMode ? 'update' : 'store'" max-width="28rem">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="cat-kode">Kode kategori *</label>
            <input id="cat-kode" type="text" wire:model="kode" class="cbm-form-input" maxlength="50" style="text-transform:uppercase;" autocomplete="off">
            @error('kode') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="cat-nama">Nama kategori *</label>
            <input id="cat-nama" type="text" wire:model="nama" class="cbm-form-input">
            @error('nama') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="cat-divisi">Divisi *</label>
            <div class="cbm-select-wrap">
                <select id="cat-divisi" wire:model="divisi" class="cbm-form-select">
                    <option value="">-- Pilih divisi --</option>
                    @foreach($this->divisionOptions() as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                </select>
            </div>
            @error('divisi') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="cat-aktif">Status *</label>
            <div class="cbm-select-wrap">
                <select id="cat-aktif" wire:model="aktif" class="cbm-form-select">
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
            </div>
            @error('aktif') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
