<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title">Cleaning Sync</div>
            <div class="mod-subtitle">Sinkronisasi data operasional Aircraft Cleaning dari Google Sheets.</div>
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
                Masukkan link URL Google Sheets yang berisi data operasional Aircraft Cleaning. Pastikan file tersebut sudah memiliki izin akses (Share) agar sistem dapat membacanya.
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.625rem;">
                <input
                    type="text"
                    wire:model.defer="sheetUrl"
                    placeholder="https://docs.google.com/spreadsheets/d/..."
                    class="mod-search-input"
                    style="width: 100%; padding: 0.75rem 1rem; text-align: left; font-size: 0.8125rem;"
                >
                @error('sheetUrl') <span style="color: red; font-size: 0.75rem; text-align: left;">{{ $message }}</span> @enderror
                
                <button
                    wire:click="syncNow"
                    class="mod-btn-primary"
                    style="width: 100%; justify-content: center; padding: 0.75rem; font-size: 0.875rem; margin-top: 0.5rem;"
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

            <div style="margin-top: 2rem;">
                @include('livewire.modules.partials.sync-status', [
                    'syncSetting' => $syncSetting,
                    'hint' => 'Tempel link google sheet untuk Aircraft Cleaning lalu klik sinkronisasi.',
                ])
            </div>
            
            @if (session()->has('success'))
                <div style="margin-top: 1.25rem; padding: 0.875rem 1rem; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 0.5rem; font-size: 0.8125rem; border: 1px solid rgba(16, 185, 129, 0.2); text-align: left; display: flex; gap: 0.5rem; align-items: flex-start;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><strong>Sukses!</strong> {{ session('success') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div style="margin-top: 1.25rem; padding: 0.875rem 1rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 0.5rem; font-size: 0.8125rem; border: 1px solid rgba(239, 68, 68, 0.2); text-align: left; display: flex; gap: 0.5rem; align-items: flex-start;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <span><strong>Gagal!</strong> {{ session('error') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
