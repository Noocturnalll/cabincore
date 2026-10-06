<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">NSRDI Overdue Management</div>
            <div class="mod-title">NSRDI Overdue</div>
            <div class="mod-subtitle">Daftar item NSRDI yang sudah melewati batas waktu.</div>
        </div>
        <div class="mod-actions">
            <button wire:click="syncData" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="syncData" style="color: #3b82f6; border-color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; min-width: 8.125rem;">
                <span wire:loading.remove wire:target="syncData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem;height:1.25rem;"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" /></svg>
                        Sync Status
                    </span>
                </span>
                <span wire:loading wire:target="syncData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg style="width:1.25rem;height:1.25rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                        Syncing...
                    </span>
                </span>
            </button>
            <button wire:click="exportData" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="exportData" style="display: inline-flex; align-items: center; justify-content: center; min-width: 8.75rem;">
                <span wire:loading.remove wire:target="exportData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem;height:1.25rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Export Excel
                    </span>
                </span>
                <span wire:loading wire:target="exportData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg style="width:1.25rem;height:1.25rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                        Mengexport...
                    </span>
                </span>
            </button>
            <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Import Data
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="display: flex; gap: 0.625rem; align-items: center;">
                <div style="position: relative; display: flex; align-items: center;">
                    <svg style="position: absolute; left: 0.625rem; width: 1.125rem; height: 1.125rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live="search" class="mod-search-input" type="text" placeholder="Cari registrasi, nsrdi..." style="padding-left: 2.1875rem;">
                </div>
            </div>
            <span class="mod-record-count">{{ $logs->total() }} records</span>
        </div>
        
        <div class="mod-table-wrap">
            <table class="mod-table" style="white-space: nowrap;">
                <thead>
                    <tr>
                        <th>OPERATOR</th>
                        <th>A/C</th>
                        <th>STATUS A/C</th>
                        <th>DEFECT</th>
                        <th>MDDR</th>
                        <th>STATUS</th>
                        <th>AREA</th>
                        <th>REPORT DATE</th>
                        <th>MONTH DUE</th>
                        <th>DEFECT DESCRIPTION</th>
                        <th>STATUS FINAL</th>
                        <th>REMARKS</th>
                        <th>CLOSED AT</th>
                        <th style="text-align: right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->operator ?? '-' }}</td>
                            <td>
                                <div class="mod-ac-badge">{{ $log->aircraft_registration ?? '-' }}</div>
                            </td>
                            <td>{{ $log->ac_status ?? '-' }}</td>
                            <td style="font-weight: 500;">{{ $log->nsrdi_number ?? '-' }}</td>
                            <td>{{ $log->mddr ?? '-' }}</td>
                            <td>
                                <span class="mod-status-badge {{ strtolower($log->status) === 'closed' ? 'mod-status-success' : 'mod-status-warning' }}">
                                    {{ $log->status ?? 'Open' }}
                                </span>
                            </td>
                            <td>{{ $log->area ?? '-' }}</td>
                            <td>{{ $log->report_date ? \Carbon\Carbon::parse($log->report_date)->format('d M Y') : '-' }}</td>
                            <td>{{ $log->month_due ?? '-' }}</td>
                            <td>
                                <x-text-popup :text="$log->description ?? '-'" />
                            </td>
                            <td>{{ $log->status_final ?? '-' }}</td>
                            <td>
                                <x-text-popup :text="$log->remarks ?? '-'" />
                            </td>
                            <td>{{ $log->closed_at ? \Carbon\Carbon::parse($log->closed_at)->format('d M Y H:i') : '-' }}</td>
                            <td style="text-align: right;">
                                <button wire:click="openEditModal({{ $log->id }})" class="mod-btn-outline" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    Update Status
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14">
                                <div class="mod-empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center;">
                                    <svg style="width: 4rem; height: 4rem; margin-bottom: 1rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    <p style="color: #6b7280; font-size: 0.95rem;">Tidak ada data NSRDI Overdue</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div style="padding: 1rem; border-top: 1px solid var(--cbm-border);">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Edit Status --}}
    @if($isEditModalOpen)
    <div class="cbm-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
        <div class="cbm-modal-content" style="background:var(--cbm-card-bg); width:100%; max-width:30rem; border-radius:0.75rem; overflow:hidden; box-shadow:0 0.625rem 0.9375rem -3px rgba(0,0,0,0.1);">
            <form wire:submit.prevent="updateOverdue">
                <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--cbm-border); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-weight:600; font-size:1.1rem; color:var(--cbm-text-main);">Update Status NSRDI Overdue</h3>
                    <button type="button" wire:click="$set('isEditModalOpen', false)" style="background:transparent; border:none; color:var(--cbm-text-sub); cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem; height:1.25rem;"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
                <div style="padding:1.5rem; display:flex; flex-direction:column; gap:1rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.4rem;">Status</label>
                        <select wire:model="editStatus" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main);">
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.4rem;">Status Final</label>
                        <input type="text" wire:model="editStatusFinal" placeholder="Contoh: Closed by DJA, Replaced, dll" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main);">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.4rem;">Remarks</label>
                        <textarea wire:model="editRemarks" rows="3" placeholder="Catatan tambahan..." style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main);"></textarea>
                    </div>
                </div>
                <div style="padding:1rem 1.5rem; border-top:1px solid var(--cbm-border); background:var(--cbm-bg); display:flex; justify-content:flex-end; gap:0.75rem;">
                    <button type="button" wire:click="$set('isEditModalOpen', false)" class="mod-btn-outline" style="border-radius:6px;">Batal</button>
                    <button type="submit" class="mod-btn-primary" style="border-radius:6px;" wire:loading.attr="disabled" wire:target="updateOverdue">
                        <span wire:loading.remove wire:target="updateOverdue">Simpan Perubahan</span>
                        <span wire:loading wire:target="updateOverdue">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Import --}}
    @if($isImportModalOpen)
    <div class="cbm-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
        <div class="cbm-modal-content" style="background:var(--cbm-card-bg); width:100%; max-width:25rem; border-radius:0.75rem; overflow:hidden; box-shadow:0 0.625rem 0.9375rem -3px rgba(0,0,0,0.1);">
            <form wire:submit.prevent="importData">
                <div style="padding:1.5rem; border-bottom:1px solid var(--cbm-border); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-weight:600; font-size:1.1rem; color:var(--cbm-text-main);">Import NSRDI Overdue</h3>
                    <button type="button" wire:click="$set('isImportModalOpen', false)" style="background:transparent; border:none; color:var(--cbm-text-sub); cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem; height:1.25rem;"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
                <div style="padding:1.5rem;">
                    @if(session()->has('error'))
                    <div style="margin-bottom:1rem; padding:0.75rem; background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); border-radius:6px; color:#ef4444; font-size:0.875rem;">
                        {{ session('error') }}
                    </div>
                    @endif
                    <div style="margin-bottom:1rem;">
                        <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.5rem;">Pilih File (Excel/CSV)</label>
                        <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main); cursor:pointer;">
                        @error('file') <span style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:block;">{{ $message }}</span> @enderror
                        
                        <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--cbm-text-sub);">
                            * Format kolom akan disesuaikan setelah instruksi lebih lanjut
                        </div>
                    </div>
                </div>
                <div style="padding:1rem 1.5rem; border-top:1px solid var(--cbm-border); background:var(--cbm-bg); display:flex; justify-content:flex-end; gap:0.75rem;">
                    <button type="button" wire:click="$set('isImportModalOpen', false)" class="mod-btn-outline" style="border-radius:6px;">Batal</button>
                    <button type="submit" class="mod-btn-primary" style="border-radius:6px;" wire:loading.attr="disabled" wire:target="file, importData">
                        <span wire:loading.remove wire:target="importData">Upload & Import</span>
                        <span wire:loading wire:target="importData">Loading...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
