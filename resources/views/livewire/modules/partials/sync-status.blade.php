@php
    $isSuccess = $syncSetting->last_status === 'success';
@endphp

<div style="margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--cbm-border); text-align: left; font-size: 0.75rem; line-height: 1.6; color: var(--cbm-text-muted);">
    <div>
        <strong style="color: var(--cbm-text);">Sheet aktif:</strong>
        @if ($syncSetting->spreadsheet_id)
            <a href="{{ $syncSetting->sheetUrl() }}" target="_blank" rel="noopener" style="color: var(--cbm-blue); word-break: break-all;">{{ $syncSetting->spreadsheet_id }}</a>
        @else
            <span>belum diatur</span>
        @endif
    </div>

    @if ($syncSetting->last_synced_at)
        <div>
            <strong style="color: var(--cbm-text);">Sync terakhir:</strong>
            {{ $syncSetting->last_synced_at->format('d M Y H:i') }}
            <span style="font-weight: 600; color: {{ $isSuccess ? '#059669' : '#DC2626' }};">({{ $isSuccess ? 'berhasil' : 'gagal' }})</span>
        </div>
        @if ($syncSetting->last_message)
            <div style="color: {{ $isSuccess ? 'var(--cbm-text-muted)' : '#DC2626' }};">{{ $syncSetting->last_message }}</div>
        @endif
    @endif

    <div style="margin-top: 0.25rem; font-style: italic;">{{ $hint }}</div>
</div>
