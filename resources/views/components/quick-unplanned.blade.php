@props(['open' => false, 'columns' => []])
<div>
    <button type="button" wire:click="$set('isQuickOpen', true)" class="mod-btn-primary"
            style="position:fixed; right:1.25rem; bottom:1.25rem; z-index:40; box-shadow:0 6px 20px rgba(0,0,0,.35);">
        + Unplanned Cepat
    </button>

    @if($open)
        <div class="cbm-modal-overlay" style="display:flex;" x-data
             x-on:keydown.escape.window="$wire.set('isQuickOpen', false)"
             wire:click.self="$set('isQuickOpen', false)">
            <div class="cbm-modal-panel" @click.stop>
                <div class="cbm-modal-header">
                    <div>
                        <div class="cbm-modal-title">Input Unplanned Cepat</div>
                        <div class="cbm-modal-subtitle">Tempel banyak baris sekaligus (satu baris = satu dokumen). Status otomatis Closed.</div>
                    </div>
                    <button class="cbm-modal-close" wire:click="$set('isQuickOpen', false)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveQuick">
                    <div class="cbm-modal-body">
                        <div class="mod-hint" style="margin-bottom:.75rem;">
                            Urutan kolom (pisahkan dengan Tab dari Excel / GSS, atau titik koma):<br>
                            <strong>{{ implode('  |  ', $columns) }}</strong><br>
                            WG, operator, dan tipe pesawat diisi otomatis dari master pesawat. Dokumen yang sudah ada dilewati.
                        </div>
                        <textarea wire:model="quickText" rows="10" class="mod-input" style="width:100%; font-family:monospace;"
                                  placeholder="PK-LQA;DOC-12345;Replace seat cover;CGK"></textarea>
                    </div>
                    <div class="cbm-modal-footer">
                        <button type="button" wire:click="$set('isQuickOpen', false)" class="mod-btn-outline">Batal</button>
                        <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="saveQuick">
                            <span wire:loading.remove wire:target="saveQuick">Simpan</span>
                            <span wire:loading wire:target="saveQuick">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
