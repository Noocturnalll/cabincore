<div>
    <x-master.page-header title="Bandara / Station" subtitle="Kelola data bandara dan station operasional." accent="blue" create-label="Tambah Bandara" />

    <x-flash />

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari kode, nama, atau kota..." aria-label="Cari bandara">
                </div>
                <label class="mod-field mod-field-labelled"><span>Status</span>
                    <select wire:model.live="statusFilter" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                </label>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ $totals['active'] }} aktif dari {{ $totals['all'] }} bandara</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>KODE IATA</th>
                        <th>NAMA BANDARA</th>
                        <th>KOTA</th>
                        <th>STATUS</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($airports as $airport)
                        <tr wire:key="airport-{{ $airport->id }}">
                            <td><div class="mod-aircraft-name">{{ $airport->kode }}</div></td>
                            <td>{{ $airport->nama }}</td>
                            <td>{{ $airport->kota }}</td>
                            <td>
                                @if($airport->status === 'Aktif')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Tidak Aktif</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    <button type="button" wire:click="edit({{ $airport->id }})" class="mod-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $airport->id }})" wire:confirm="Hapus bandara {{ $airport->kode }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ ($search || $statusFilter) ? 'Tidak ada data yang cocok' : 'Belum ada bandara' }}</div>
                                <div class="mod-empty-sub">{{ ($search || $statusFilter) ? 'Ubah kata kunci atau filter status.' : 'Klik "Tambah Bandara" untuk mulai.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($airports->hasPages())
            <div class="mod-pagination">{{ $airports->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="$isEditMode ? 'Edit Bandara' : 'Tambah Bandara Baru'" subtitle="Kode IATA dipakai di seluruh modul sebagai kode station." :submit="$isEditMode ? 'update' : 'store'" max-width="28rem">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ap-kode">Kode IATA *</label>
            <input id="ap-kode" type="text" wire:model="kode" class="cbm-form-input" maxlength="10" placeholder="CGK" style="text-transform:uppercase;" autocomplete="off">
            @error('kode') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ap-nama">Nama bandara *</label>
            <input id="ap-nama" type="text" wire:model="nama" class="cbm-form-input" placeholder="Soekarno-Hatta International">
            @error('nama') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ap-kota">Kota *</label>
            <input id="ap-kota" type="text" wire:model="kota" class="cbm-form-input" placeholder="Tangerang">
            @error('kota') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="ap-status">Status *</label>
            <div class="cbm-select-wrap">
                <select id="ap-status" wire:model="status" class="cbm-form-select">
                    <option value="Aktif">Aktif</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </select>
            </div>
            @error('status') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
