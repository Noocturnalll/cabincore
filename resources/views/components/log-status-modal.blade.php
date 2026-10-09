@props(['show' => false, 'codes' => []])

{{-- Set a WO / DMI / NSRDI item to Open. Code reason and remarks are both required. --}}
@if($show)
<div class="cbm-modal-overlay" x-data x-on:keydown.escape.window="$wire.closeStatusModal()" wire:click.self="closeStatusModal" style="display:flex;">
    <div class="cbm-modal-panel" style="max-width:27.5rem;" @click.stop role="dialog" aria-modal="true" aria-label="Set status Open">
        <div class="cbm-modal-header">
            <div>
                <div class="cbm-modal-title">Set status Open</div>
                <div class="cbm-modal-subtitle">Code reason dan remarks wajib diisi</div>
            </div>
            <button type="button" class="cbm-modal-close" wire:click="closeStatusModal" aria-label="Tutup">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form wire:submit="updateStatus">
            <div class="cbm-modal-body">
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="st-code">Code reason *</label>
                    <div class="cbm-select-wrap">
                        <select id="st-code" wire:model="hold_reason_category" class="cbm-form-select">
                            <option value="">-- Pilih code --</option>
                            @foreach($codes as $code)<option value="{{ $code }}">{{ $code }}</option>@endforeach
                        </select>
                    </div>
                    @error('hold_reason_category') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group" style="margin-bottom:0;">
                    <label class="cbm-form-label" for="st-remarks">Remarks (alasan open) *</label>
                    <textarea id="st-remarks" wire:model="hold_remarks" class="cbm-form-textarea" maxlength="1000" placeholder="Jelaskan kenapa pekerjaan ini masih Open..."></textarea>
                    @error('hold_remarks') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="cbm-modal-footer">
                <button type="button" wire:click="closeStatusModal" class="mod-btn-outline">Batal</button>
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="updateStatus">
                    <span wire:loading.remove wire:target="updateStatus">Simpan sebagai Open</span>
                    <span wire:loading wire:target="updateStatus"><span class="cbm-spinner"></span> Menyimpan &amp; sinkron sheet...</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif
