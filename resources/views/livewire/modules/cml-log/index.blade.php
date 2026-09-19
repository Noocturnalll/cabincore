<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Cabin Maintenance Log</div>
            <div class="mod-title">CML</div>
            <div class="mod-subtitle">Catatan harian perawatan kabin pesawat — semua log tercatat di sini.</div>
        </div>
        <div class="mod-actions">
            <button class="mod-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Export Excel
            </button>
            <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Import CML
            </button>
        </div>
    </div>

    {{-- Table card --}}
    <div class="mod-card mod-card-accent-blue">
        {{-- Toolbar --}}
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="display: flex; gap: 10px; align-items: center;">
                <div style="position: relative; display: flex; align-items: center;">
                    <svg style="position: absolute; left: 10px; width: 18px; height: 18px; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live="search" class="mod-search-input" type="text" placeholder="Cari registrasi, status..." style="padding-left: 35px;">
                </div>
                <input wire:model.live="dateFilter" type="date" class="mod-search-input">
            </div>
            <span class="mod-record-count">{{ $logs->count() }} records</span>
        </div>

        {{-- Table --}}
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>OPERATOR</th>
                        <th>REG A/C</th>
                        <th>A/C STATUS</th>
                        <th>STA</th>
                        <th>DOC TYPE</th>
                        <th>NO. DOC</th>
                        <th>ACTION TAKEN / REASON</th>
                        <th>STATUS</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->date ? \Carbon\Carbon::parse($log->date)->format('d M Y') : '' }}</td>
                            <td>{{ $log->operator ?? '' }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $log->aircraft_registration ?? '' }}</div>
                            </td>
                            <td>{{ $log->ac_status ?? '' }}</td>
                            <td>{{ $log->station ?? '' }}</td>
                            <td>{{ $log->doc_type ?? '' }}</td>
                            <td>{{ $log->no_doc ?? '' }}</td>
                            <td><x-text-popup :text="$log->description ?? ''" title="Action Taken / Reason" /></td>
                            <td>
                                @if(($log->status ?? '') === 'Closed')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                @else
                                    <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#f87171;"></span>Open</span>
                                @endif
                            </td>

                            <td style="text-align:right;">
                                <button class="mod-action-btn" title="Detail">
                                    Detail
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="mod-empty">
                                    <div class="mod-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                    </div>
                                    <div class="mod-empty-title">Belum ada data CML</div>
                                    <div class="mod-empty-sub">Klik "Import CML" untuk mengunggah log pertama.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- ═══════ Import Modal ═══════ --}}
    @if($isImportModalOpen)
    <div class="cbm-modal-overlay"
         x-data x-init
         x-on:keydown.escape.window="$wire.set('isImportModalOpen', false)"
         x-show="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display:flex;"
    >
        <div class="cbm-modal-panel" @click.stop>

            {{-- Header --}}
            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">Import Data CML</div>
                    <div class="cbm-modal-subtitle">Unggah file Excel untuk memuat data log</div>
                </div>
                <button class="cbm-modal-close" wire:click="$set('isImportModalOpen', false)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Flash messages --}}
            <div class="cbm-modal-body" style="padding-bottom: 0;">
                @if(session()->has('message'))
                <div class="cbm-flash cbm-flash-success" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('message') }}
                </div>
                @endif
                @if(session()->has('error'))
                <div class="cbm-flash cbm-flash-error" x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    {{ session('error') }}
                </div>
                @endif
            </div>

            {{-- Form --}}
            <form wire:submit="importCml">
                <div class="cbm-modal-body">
                    {{-- Upload Zone --}}
                    <div class="cbm-upload-zone">
                        <input type="file" wire:model="file" accept=".xlsx,.xls,.csv">
                        <div class="cbm-upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                        <div class="cbm-upload-title">Klik untuk pilih file</div>
                        <div class="cbm-upload-sub">atau drag &amp; drop ke sini</div>
                        <div class="cbm-upload-badge">
                            <span>.xlsx</span><span>.xls</span><span>.csv</span>
                        </div>
                        <div wire:loading wire:target="file" style="margin-top:.75rem; font-size:.8125rem; color:var(--cbm-text-muted);">Mengunggah file...</div>
                    </div>
                    @error('file') <span style="color:#f87171; font-size:.75rem; display:block; margin-top:.5rem; font-weight:600;">{{ $message }}</span> @enderror
                </div>

                <div class="cbm-modal-footer">
                    <button type="button" wire:click="$set('isImportModalOpen', false)" class="mod-btn-outline">Batal</button>
                    <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="importCml">
                            <span style="display:inline-flex;align-items:center;gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:.875rem;height:.875rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Upload &amp; Import
                            </span>
                        </span>
                        <span wire:loading wire:target="importCml">
                            <span style="display:inline-flex;align-items:center;gap:.4rem;">
                                <span class="cbm-spinner"></span> Mengimport...
                            </span>
                        </span>
                    </button>
                </div>
            </form>

        </div>
    </div>
    @endif

</div>
