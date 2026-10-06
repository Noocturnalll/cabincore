<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">Master Data</span>
            <h1 class="mod-title">Data Barang (Items)</h1>
            <p class="mod-subtitle">Kelola database spare part, tools, dan barang lainnya untuk sistem IMS.</p>
        </div>
        
        <div class="mod-actions">
            <button wire:click="create" class="mod-btn-primary">
                + Tambah Barang
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #10b981;">
            {{ session('success') }}
        </div>
    @endif

    <div class="mod-card">
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="max-width: 18.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari PN atau Nama..." class="mod-search-input" wire:model.live.debounce.300ms="search">
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Part Number</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td><strong>{{ $record->part_number }}</strong></td>
                            <td>{{ $record->name }}</td>
                            <td>{{ $record->category->name ?? '-' }}</td>
                            <td>{{ $record->unit->code ?? '-' }}</td>
                            <td>
                                @if($record->is_active)
                                    <span class="mod-badge-closed">Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Nonaktif</span>
                                @endif
                            </td>
                            <td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button wire:click="edit({{ $record->id }})" class="mod-action-btn">Edit</button>
                                <button wire:click="delete({{ $record->id }})" class="mod-action-btn" style="color: #ef4444; border-color: #ef4444;" onclick="confirm('Yakin ingin menghapus barang ini?') || event.stopImmediatePropagation()">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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
        <div style="background: var(--cbm-card-bg); width: 100%; max-width: 37.5rem; max-height: 90vh; border-radius: 1rem; box-shadow: var(--cbm-card-shadow); overflow-y: auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: var(--cbm-card-bg); z-index: 10;">
                <h3 style="font-size: 1.25rem; font-weight: 600;">{{ $isEdit ? 'Edit Barang' : 'Tambah Barang' }}</h3>
                <button wire:click="$set('isOpen', false)" style="background: none; border: none; cursor: pointer; color: var(--cbm-text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save">
                <div style="padding: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Part Number <span style="color: red">*</span></label>
                        <input type="text" wire:model="form.part_number" class="mod-search-input" style="width: 100%;">
                        @error('form.part_number') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Nama Barang <span style="color: red">*</span></label>
                        <input type="text" wire:model="form.name" class="mod-search-input" style="width: 100%;">
                        @error('form.name') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Deskripsi</label>
                        <textarea wire:model="form.description" class="mod-search-input" style="width: 100%; height: 3.75rem;"></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Kategori <span style="color: red">*</span></label>
                        <select wire:model="form.category_id" class="mod-search-input" style="width: 100%;">
                            <option value="">-- Pilih --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('form.category_id') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Satuan <span style="color: red">*</span></label>
                        <select wire:model="form.unit_id" class="mod-search-input" style="width: 100%;">
                            <option value="">-- Pilih --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                            @endforeach
                        </select>
                        @error('form.unit_id') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Lokasi Default</label>
                        <select wire:model="form.default_location_id" class="mod-search-input" style="width: 100%;">
                            <option value="">-- Pilih --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('form.default_location_id') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Minimal Stok</label>
                        <input type="number" wire:model="form.min_stock" class="mod-search-input" style="width: 100%;">
                        @error('form.min_stock') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Tracking Type</label>
                        <select wire:model="form.tracking_type" class="mod-search-input" style="width: 100%;">
                            <option value="quantity">Quantity (Tanpa Serial)</option>
                            <option value="serial">Serial Number (S/N)</option>
                        </select>
                        @error('form.tracking_type') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="display: flex; align-items: flex-end; padding-bottom: 0.5rem;">
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
