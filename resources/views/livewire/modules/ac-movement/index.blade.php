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
        .nav-tabs .nav-link {
            color: var(--cbm-text-muted);
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .nav-tabs .nav-link.active {
            color: var(--cbm-blue);
            background: transparent;
            border-color: var(--cbm-blue);
        }
        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--cbm-text);
            border-color: var(--cbm-border);
        }
        .table {
            color: var(--cbm-text);
            border-color: var(--cbm-border);
        }
        .table th {
            background: var(--cbm-bg);
            color: var(--cbm-text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom-width: 1px;
        }
        .table td {
            background: var(--cbm-card);
            border-color: var(--cbm-border);
            vertical-align: middle;
        }
        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: rgba(255, 255, 255, 0.02);
            color: var(--cbm-text);
        }
    </style>

    {{-- Header --}}
    <div class="mod-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="mod-title-block">
            <div class="mod-title">AC Movement & RON</div>
            <div class="mod-subtitle">Pantau pergerakan pesawat di Terminal dan daftar RON secara realtime.</div>
        </div>
        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" wire:click="$refresh" style="border-color: var(--cbm-border); color: var(--cbm-text);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Refresh Data
        </button>
    </div>

    {{-- Layout Grid --}}
    <div style="display: flex; gap: 1.5rem; flex-direction: column;">
        
        {{-- Sync Card --}}
        <div class="mod-card" style="padding: 1.5rem; max-width: 600px; margin: 0 auto; width: 100%; text-align: center;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <svg style="width: 24px; height: 24px; color: var(--cbm-blue);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </div>
            
            <h3 style="font-size: 1rem; font-weight: 600; color: var(--cbm-text); margin-bottom: 0.25rem;">Google Sheets Sync</h3>
            <p style="font-size: 0.8125rem; color: var(--cbm-text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                Tarik data pergerakan pesawat dari Sheet Terminal 1, Terminal 2, AC RON, dan AC STBY secara instan.
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <input
                    type="text"
                    wire:model.defer="sheetUrl"
                    placeholder="https://docs.google.com/spreadsheets/d/..."
                    class="mod-search-input"
                    style="width: 100%; padding: 0.625rem 0.875rem; text-align: left; font-size: 0.8125rem;"
                >
                <button
                    wire:click="syncNow"
                    class="mod-btn-primary"
                    style="width: 100%; justify-content: center; padding: 0.625rem; font-size: 0.875rem;"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="syncNow">
                        Mulai Sinkronisasi
                    </span>
                    <span wire:loading wire:target="syncNow">
                        <svg class="animate-spin" style="width:16px;height:16px;display:inline;margin-right:6px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Menyinkronkan...
                    </span>
                </button>
            </div>

            @include('livewire.modules.partials.sync-status', [
                'syncSetting' => $syncSetting,
                'hint' => 'Link sheet tersimpan permanen dan auto-sync tiap 5 menit. Ganti link di atas jika sheet berubah.',
            ])
        </div>

    </div>
</div>
