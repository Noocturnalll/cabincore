<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-green">IMS Tracker</span>
            <h1 class="mod-title">Penerimaan Barang</h1>
            <p class="mod-subtitle">Tambahkan stok baru ke gudang (Goods In).</p>
        </div>
    </div>

    <x-flash />

    <div class="mod-card mod-card-accent-green" style="max-width:44rem;">
        <form wire:submit="submit" class="cbm-modal-body">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rcv-search">Cari barang</label>
                <input id="rcv-search" type="search" wire:model.live.debounce.300ms="searchItem" placeholder="Ketik nama atau part number..." class="cbm-form-input" autocomplete="off">
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));gap:1rem;">
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="rcv-item">Barang *</label>
                    <div class="cbm-select-wrap">
                        <select id="rcv-item" wire:model="itemId" class="cbm-form-select">
                            <option value="">-- Pilih barang --</option>
                            @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->part_number }} - {{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                    @error('itemId') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="rcv-loc">Lokasi / Gudang *</label>
                    <div class="cbm-select-wrap">
                        <select id="rcv-loc" wire:model="locationId" class="cbm-form-select">
                            <option value="">-- Pilih lokasi --</option>
                            @foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                    </div>
                    @error('locationId') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rcv-qty">Jumlah *</label>
                <input id="rcv-qty" type="number" wire:model="qty" min="1" placeholder="Jumlah barang masuk" class="cbm-form-input">
                @error('qty') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="cbm-form-group" style="margin-bottom:0;">
                <label class="cbm-form-label" for="rcv-notes">Catatan</label>
                <textarea id="rcv-notes" wire:model="notes" class="cbm-form-textarea" placeholder="Opsional, mis. nomor PO"></textarea>
                @error('notes') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:1.25rem;">
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Simpan Stok</span>
                    <span wire:loading wire:target="submit"><span class="cbm-spinner"></span> Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
