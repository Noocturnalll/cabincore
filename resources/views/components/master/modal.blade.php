@props([
    'show' => false,
    'title' => '',
    'subtitle' => null,
    'close' => 'close',        // Livewire method that closes the modal
    'submit' => 'store',       // Livewire method run on submit
    'maxWidth' => '32rem',
    'submitLabel' => 'Simpan',
])

{{-- Standard master-data form modal: header, scrollable body (slot), Batal / Simpan footer --}}
@if($show)
<div class="cbm-modal-overlay" x-data x-on:keydown.escape.window="$wire.{{ $close }}()" wire:click.self="{{ $close }}" style="display:flex;">
    <div class="cbm-modal-panel" style="max-width:{{ $maxWidth }};" @click.stop role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="cbm-modal-header">
            <div>
                <div class="cbm-modal-title">{{ $title }}</div>
                @if($subtitle)<div class="cbm-modal-subtitle">{{ $subtitle }}</div>@endif
            </div>
            <button type="button" class="cbm-modal-close" wire:click="{{ $close }}" aria-label="Tutup">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form wire:submit="{{ $submit }}">
            <div class="cbm-modal-body">{{ $slot }}</div>
            <div class="cbm-modal-footer">
                <button type="button" wire:click="{{ $close }}" class="mod-btn-outline">Batal</button>
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="{{ $submit }}">
                    <span wire:loading.remove wire:target="{{ $submit }}">{{ $submitLabel }}</span>
                    <span wire:loading wire:target="{{ $submit }}"><span class="cbm-spinner"></span> Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif
