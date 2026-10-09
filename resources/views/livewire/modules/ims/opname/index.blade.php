<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-orange">IMS Tracker</span>
            <h1 class="mod-title">Stock Opname</h1>
            <p class="mod-subtitle">Sesuaikan stok sistem dengan hasil hitung fisik.</p>
        </div>
    </div>

    <x-flash />

    <div class="mod-card mod-card-accent-orange" style="max-width:44rem;">
        <form wire:submit="submit" class="cbm-modal-body">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="opn-search">Cari barang</label>
                <input id="opn-search" type="search" wire:model.live.debounce.300ms="searchItem" placeholder="Ketik nama atau part number..." class="cbm-form-input" autocomplete="off">
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));gap:1rem;">
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="opn-item">Barang *</label>
                    <div class="cbm-select-wrap">
                        <select id="opn-item" wire:model.live="itemId" class="cbm-form-select">
                            <option value="">-- Pilih barang --</option>
                            @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->part_number }} - {{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                    @error('itemId') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="opn-loc">Lokasi / Gudang *</label>
                    <div class="cbm-select-wrap">
                        <select id="opn-loc" wire:model.live="locationId" class="cbm-form-select">
                            <option value="">-- Pilih lokasi --</option>
                            @foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                    </div>
                    @error('locationId') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));gap:1rem;">
                <div class="cbm-form-group">
                    <label class="cbm-form-label">Stok saat ini (sistem)</label>
                    <div class="mod-readonly">{{ $currentQty }}</div>
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="opn-new">Stok fisik (baru) *</label>
                    <input id="opn-new" type="number" wire:model.live="newQty" min="0" placeholder="Jumlah aktual" class="cbm-form-input">
                    @error('newQty') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            @if($itemId && $locationId && $newQty !== null && $newQty !== '' && (int) $newQty !== (int) $currentQty)
                @php $diff = (int) $newQty - (int) $currentQty; @endphp
                <div class="mod-hint {{ $diff > 0 ? 'mod-hint-ok' : 'mod-hint-warn' }}">
                    Selisih {{ $diff > 0 ? '+' : '' }}{{ $diff }} akan dicatat sebagai penyesuaian {{ $diff > 0 ? 'masuk' : 'keluar' }}.
                </div>
            @endif

            <div class="cbm-form-group" style="margin-bottom:0;">
                <label class="cbm-form-label" for="opn-reason">Alasan penyesuaian *</label>
                <textarea id="opn-reason" wire:model="reason" class="cbm-form-textarea" placeholder="Mis. selisih perhitungan, barang rusak, barang hilang"></textarea>
                @error('reason') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:1.25rem;">
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="submit" wire:confirm="Sesuaikan stok sistem dengan jumlah fisik ini?">
                    <span wire:loading.remove wire:target="submit">Sesuaikan Stok</span>
                    <span wire:loading wire:target="submit"><span class="cbm-spinner"></span> Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
