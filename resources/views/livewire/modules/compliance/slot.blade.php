{{-- One evidence slot: the file when it exists (with remove), otherwise an upload button for those who may upload. --}}
<span class="cp-slot" wire:key="slot-{{ $key }}">
    @if($url)
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="mod-action-btn" style="color:#34d399;border-color:rgba(52,211,153,.4);">✓ {{ $label }}</a>
        @if($canUpload && $entryId)
            <button type="button" class="cp-x" title="Hapus {{ $label }}" wire:click="removeDoc({{ $entryId }}, '{{ $doc }}')" wire:confirm="Hapus bukti {{ $label }}?">×</button>
        @endif
    @elseif($canUpload)
        <label class="mod-action-btn" style="border-style:dashed;" title="Unggah {{ $label }}">
            <span wire:loading.remove wire:target="files.{{ $key }}">＋ {{ $label }}</span>
            <span wire:loading wire:target="files.{{ $key }}">Mengunggah…</span>
            <input class="cp-file" type="file" accept="image/*,application/pdf" wire:model="files.{{ $key }}" aria-label="Unggah {{ $label }}">
        </label>
    @else
        <span class="cp-miss">{{ $label }} ✕</span>
    @endif
</span>
