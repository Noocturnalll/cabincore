<div>
    <div class="mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">Aircraft Cleaning</span>
            <h1 class="mod-title">Daily Report</h1>
            <p class="mod-subtitle">Pantau log harian aktivitas Aircraft Cleaning</p>
        </div>
        <div class="mod-actions">
            <button type="button" wire:click="$set('isImportModalOpen', true)" class="mod-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Import Data
            </button>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="mod-btn-primary">
                <span wire:loading.remove wire:target="exportExcel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export Data
                </span>
                <span wire:loading wire:target="exportExcel">
                    Exporting...
                </span>
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="cbm-tabs">
            <button wire:click="setTab('Transit')" class="cbm-tab {{ $activeTab === 'Transit' ? 'active' : '' }}">Transit Cleaning</button>
            <button wire:click="setTab('General')" class="cbm-tab {{ $activeTab === 'General' ? 'active' : '' }}">General Cleaning</button>
            <button wire:click="setTab('DCI')" class="cbm-tab {{ $activeTab === 'DCI' ? 'active' : '' }}">Deep Cleaning Interior</button>
            <button wire:click="setTab('DCE')" class="cbm-tab {{ $activeTab === 'DCE' ? 'active' : '' }}">Deep Cleaning Exterior</button>
        </div>

        <div class="mod-toolbar">
            <div class="mod-search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" class="mod-search-input" placeholder="Cari registrasi, station, status...">
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input wire:model.live="dateFilter" type="date" class="mod-search-input" style="width: auto; padding-left: 0.875rem;">
                <div class="mod-record-count">Total: {{ count($logs) }} Data</div>
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>A/C Reg</th>
                        <th>Type</th>
                        <th>Shift</th>
                        <th>Station</th>
                        <th>Operator</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->date }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $log->aircraft_registration }}</div>
                            </td>
                            <td>{{ $log->type }}</td>
                            <td>{{ $log->shift ?: '-' }}</td>
                            <td>{{ $log->station ?: '-' }}</td>
                            <td>{{ $log->operator ?: '-' }}</td>
                            <td>
                                @if(in_array($log->type, ['Transit', 'General']))
                                    -
                                @else
                                    @if(strtolower($log->status) === 'closed')
                                        <span class="mod-badge-closed"><span class="mod-badge-dot"></span>Closed</span>
                                    @else
                                        <span class="mod-badge-open"><span class="mod-badge-dot"></span>Open</span>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $log->remarks ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="mod-empty">
                                    <div class="mod-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <div class="mod-empty-title">Tidak ada data</div>
                                    <div class="mod-empty-sub">Coba ubah filter atau import data baru.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Import Modal --}}
    @if($isImportModalOpen)
        <div class="cbm-modal-overlay">
            <div class="cbm-modal-panel">
                <div class="cbm-modal-header">
                    <div>
                        <h3 class="cbm-modal-title">Import Data Aircraft Cleaning</h3>
                        <p class="cbm-modal-subtitle">Unggah file Excel atau CSV</p>
                    </div>
                    <button wire:click="$set('isImportModalOpen', false)" class="cbm-modal-close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="cbm-modal-body">
                    <form wire:submit.prevent="importData">
                        <div class="cbm-form-group">
                            <label class="cbm-form-label">File Excel/CSV</label>
                            <label class="cbm-upload-zone" for="import-file">
                                <div class="cbm-upload-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div class="cbm-upload-title">Klik atau seret file ke sini</div>
                                <div class="cbm-upload-sub">Hanya menerima .xlsx, .xls, .csv (Max: 10MB)</div>
                                <input type="file" id="import-file" wire:model="file" accept=".xlsx,.xls,.csv">
                            </label>
                            @error('file') <span class="cbm-form-label" style="color: #f87171; margin-top: 0.5rem;">{{ $message }}</span> @enderror
                            
                            <div wire:loading wire:target="file" style="margin-top: 0.5rem; color: var(--cbm-text-muted); font-size: 0.8125rem;">
                                Mengunggah file...
                            </div>
                        </div>

                        <div class="cbm-modal-footer" style="padding: 0; border: none; margin-top: 1.5rem;">
                            <button type="button" wire:click="$set('isImportModalOpen', false)" class="mod-btn-outline" style="border: none; background: transparent;">Batal</button>
                            <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="importData, file">
                                <span wire:loading.remove wire:target="importData">Proses Import</span>
                                <span wire:loading wire:target="importData">Memproses...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
