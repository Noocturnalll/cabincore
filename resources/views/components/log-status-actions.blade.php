@props(['log'])

{{-- Closed / Open actions for one WO / DMI / NSRDI row (see App\Livewire\Traits\ManagesLogStatus) --}}
<div style="display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap;">
    @if($log->status === 'Closed')
        <button type="button" wire:click="openStatusModal({{ $log->id }})" class="mod-action-btn" title="Buka kembali (wajib isi code reason dan remarks)">
            &#8634; Open
        </button>
    @else
        <button type="button" wire:click="markClosed({{ $log->id }})" wire:confirm="Tandai {{ $log->aircraft_registration }} sebagai Closed?" wire:loading.attr="disabled"
                class="mod-action-btn" style="color:#10b981;border-color:rgba(16,185,129,.4);">
            &#10003; Closed
        </button>
        @php $hasReason = filled($log->hold_reason_category) && filled($log->hold_remarks); @endphp
        <button type="button" wire:click="openStatusModal({{ $log->id }})" class="mod-action-btn"
                @unless($hasReason) style="color:#d97706;border-color:rgba(217,119,6,.45);" @endunless>
            {{ $hasReason ? 'Ubah alasan' : 'Isi alasan' }}
        </button>
    @endif
</div>
