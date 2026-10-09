<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">IMS Tracker</span>
            <h1 class="mod-title">Transfer Stok</h1>
            <p class="mod-subtitle">Pindahkan stok barang antar lokasi / gudang.</p>
        </div>
    </div>

    <x-flash />

    <div class="mod-card mod-card-accent-blue" style="max-width:44rem;">
        <form wire:submit="submit" class="cbm-modal-body">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="trf-search">Cari barang</label>
                <input id="trf-search" type="search" wire:model.live.debounce.300ms="searchItem" placeholder="Ketik nama atau part number..." class="cbm-form-input" autocomplete="off">
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="trf-item">Barang *</label>
                <div class="cbm-select-wrap">
                    <select id="trf-item" wire:model.live="itemId" class="cbm-form-select">
                        <option value="">-- Pilih barang --</option>
                        @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->part_number }} - {{ $item->name }}</option>@endforeach
                    </select>
                </div>
                @error('itemId') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));gap:1rem;">
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="trf-from">Dari lokasi *</label>
                    <div class="cbm-select-wrap">
                        <select id="trf-from" wire:model.live="fromLocationId" class="cbm-form-select">
                            <option value="">-- Pilih asal --</option>
                            @foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                    </div>
                    @error('fromLocationId') <span class="mod-field-error">{{ $message }}</span> @enderror
                    @if($itemId && $fromLocationId)
                        <span class="mod-hint-inline">Tersedia: <strong>{{ $availableQty }}</strong></span>
                    @endif
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="trf-to">Ke lokasi *</label>
                    <div class="cbm-select-wrap">
                        <select id="trf-to" wire:model="toLocationId" class="cbm-form-select">
                            <option value="">-- Pilih tujuan --</option>
                            @foreach($locations as $loc)
                                @if((string) $loc->id !== (string) $fromLocationId)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endif
                            @endforeach
                        </select>
                    </div>
                    @error('toLocationId') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="trf-qty">Jumlah *</label>
                <input id="trf-qty" type="number" wire:model="qty" min="1" @if($availableQty > 0) max="{{ $availableQty }}" @endif placeholder="Jumlah yang dipindahkan" class="cbm-form-input">
                @error('qty') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="cbm-form-group" style="margin-bottom:0;">
                <label class="cbm-form-label" for="trf-notes">Catatan</label>
                <textarea id="trf-notes" wire:model="notes" class="cbm-form-textarea" placeholder="Opsional"></textarea>
                @error('notes') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:1.25rem;">
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Transfer Stok</span>
                    <span wire:loading wire:target="submit"><span class="cbm-spinner"></span> Memproses...</span>
                </button>
            </div>
        </form>
    </div>
</div>
