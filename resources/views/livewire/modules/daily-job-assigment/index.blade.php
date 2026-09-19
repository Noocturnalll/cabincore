<div>
    <style>
        .cbm-file-input::-webkit-file-upload-button {
            background: var(--cbm-bg);
            border: 1px solid var(--cbm-border);
            color: var(--cbm-text);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .cbm-file-input::-webkit-file-upload-button:hover {
            background: var(--cbm-nav-hover);
        }
        .cbm-file-input::file-selector-button {
            background: var(--cbm-bg);
            border: 1px solid var(--cbm-border);
            color: var(--cbm-text);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .cbm-file-input::file-selector-button:hover {
            background: var(--cbm-nav-hover);
        }
    </style>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title">DJA Sync</div>
            <div class="mod-subtitle">Sinkronisasi tugas terencana dari Google Sheets Planner ke seluruh modul CBM.</div>
        </div>
    </div>

    {{-- Sync Card --}}
    <div style="display: flex; justify-content: center; padding: 2rem 1.5rem;">
        <div class="mod-card" style="padding: 2.5rem; width: 100%; max-width: 560px; text-align: center;">

            <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(59,130,246,.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <svg style="width: 28px; height: 28px; color: var(--cbm-blue);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </div>

            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--cbm-text); margin-bottom: 0.375rem;">Google Sheets Sync</h3>
            <p style="font-size: 0.8125rem; color: var(--cbm-text-muted); margin-bottom: 1.75rem; line-height: 1.6;">
                Selama masa testing, gunakan fitur Import Excel untuk mengupload data DJA.
                Pastikan format header kolom sesuai (contoh: date, aircraft_registration, task_id, description, station).
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.625rem; margin-bottom: 2rem;">
                <input type="file" wire:model="file" class="mod-search-input cbm-file-input" style="width: 100%; padding: 0.5rem 0.75rem; text-align: left; font-size: 0.8125rem;">
                @error('file') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                
                <button wire:click="import" class="mod-btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem; font-size: 0.875rem;" wire:loading.attr="disabled" wire:target="file, import">
                    <span wire:loading.remove wire:target="import">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Import dari Excel
                    </span>
                    <span wire:loading wire:target="import">Mengimport Data...</span>
                </button>
            </div>

            <div style="display: flex; align-items: center; margin: 2rem 0;">
                <div style="flex: 1; border-top: 1px dashed var(--cbm-border);"></div>
                <span style="padding: 0 1rem; font-size: 0.75rem; color: var(--cbm-text-muted); font-weight: 600;">ATAU</span>
                <div style="flex: 1; border-top: 1px dashed var(--cbm-border);"></div>
            </div>

            <p style="font-size: 0.8125rem; color: var(--cbm-text-muted); margin-bottom: 1rem; line-height: 1.6;">
                Sinkronisasi melalui Google Sheets URL (Khusus Production)
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.625rem;">
                <input
                    type="text"
                    wire:model.defer="sheetUrl"
                    placeholder="https://docs.google.com/spreadsheets/d/..."
                    class="mod-search-input"
                    style="width: 100%; padding: 0.75rem 1rem; text-align: left; font-size: 0.8125rem;"
                >
                <button
                    wire:click="syncNow"
                    class="mod-btn-primary"
                    style="width: 100%; justify-content: center; padding: 0.75rem; font-size: 0.875rem;"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="syncNow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Mulai Sinkronisasi
                    </span>
                    <span wire:loading wire:target="syncNow">Menyinkronkan Data...</span>
                </button>
            </div>

            @if (session()->has('success'))
                <div style="margin-top: 1.25rem; padding: 0.875rem 1rem; background: #ECFDF5; color: #065F46; border-radius: 0.5rem; font-size: 0.8125rem; border: 1px solid #A7F3D0; text-align: left; display: flex; gap: 0.5rem; align-items: flex-start;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><strong>Sukses!</strong> {{ session('success') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div style="margin-top: 1.25rem; padding: 0.875rem 1rem; background: #FEF2F2; color: #991B1B; border-radius: 0.5rem; font-size: 0.8125rem; border: 1px solid #FECACA; text-align: left; display: flex; gap: 0.5rem; align-items: flex-start;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <span><strong>Gagal!</strong> {{ session('error') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>