<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title">ICT Findings</div>
            <div class="mod-subtitle">Daftar temuan ICT, monitoring, dan cross-reference pekerjaan.</div>
        </div>
        <div class="mod-actions">
            <!-- Search & Filter -->
            <div style="display: flex; gap: 0.75rem;">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari finding, a/c reg..." class="mod-search-input">
                <input type="date" wire:model.live="dateFilter" class="mod-search-input">
                <button wire:click="$set('dateFilter', '')" class="mod-btn-secondary" title="Clear Date" style="padding: 0.5rem 0.75rem;">
                    &times;
                </button>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin: 0 1.5rem 1rem;">
        <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary">
            Import Excel
        </button>
        <button wire:click="export" class="mod-btn-secondary" wire:loading.attr="disabled" wire:target="export">
            <span wire:loading.remove wire:target="export">Export Excel</span>
            <span wire:loading wire:target="export">Exporting...</span>
        </button>
    </div>
    
    @error('file') <div style="color: red; margin: 0 1.5rem 1rem; font-size: 0.8125rem;">{{ $message }}</div> @enderror
    @if(session()->has('success')) <div style="color: green; margin: 0 1.5rem 1rem; font-size: 0.8125rem;">{{ session('success') }}</div> @endif
    @if(session()->has('error')) <div style="color: red; margin: 0 1.5rem 1rem; font-size: 0.8125rem;">{{ session('error') }}</div> @endif

    {{-- Table --}}
    <div class="mod-content">
        <div class="mod-table-container">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th>DATE</th>
                        <th>NO FINDING</th>
                        <th>OPERATOR</th>
                        <th>REG A/C</th>
                        <th>DEFECT DESCRIPTION</th>
                        <th>REMARKS</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($findings as $index => $finding)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($finding->date)->format('d M Y') }}</td>
                            <td>{{ $finding->no_finding }}</td>
                            <td>{{ $finding->operator ?? '-' }}</td>
                            <td>{{ $finding->aircraft_registration }}</td>
                            <td><x-text-popup :text="$finding->defect_description ?? '-'" title="Defect Description" /></td>
                            <td><x-text-popup :text="$finding->remarks ?? '-'" title="Remarks" /></td>
                            <td>
                                @if(($finding->status ?? 'Open') === 'Closed')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                @else
                                    <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#fbbf24;"></span>Open</span>
                                @endif
                            </td>
                            <td>
                                <button wire:click="editFinding({{ $finding->id }})" class="mod-btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="mod-table-empty">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; color: var(--cbm-text-muted);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 32px; height: 32px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <span>Tidak ada temuan ICT</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div style="position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
            <div style="background: var(--cbm-card-bg); padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                <h3 style="font-weight: 700; font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--cbm-text);">Update Finding</h3>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: var(--cbm-text); margin-bottom: 0.5rem;">Status</label>
                    <select wire:model="editStatus" class="mod-search-input" style="width: 100%; padding: 0.75rem;">
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: var(--cbm-text); margin-bottom: 0.5rem;">Remarks (Alasan / Tindak Lanjut)</label>
                    <textarea wire:model="editRemarks" class="mod-search-input" style="width: 100%; padding: 0.75rem; min-height: 100px; resize: vertical;" placeholder="Contoh: open DMI dengan no doc NIDxxxx, status closed..."></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button wire:click="closeEditModal" class="mod-btn-secondary">Batal</button>
                    <button wire:click="saveFinding" class="mod-btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import Modal --}}
    @if($isImportModalOpen)
        <div style="position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
            <div style="background: var(--cbm-card-bg); padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                <h3 style="font-weight: 700; font-size: 1.25rem; margin-bottom: 0.5rem; color: var(--cbm-text);">Import ICT Findings</h3>
                <p style="font-size: 0.8125rem; color: var(--cbm-text-muted); margin-bottom: 1.5rem;">Format kolom: date, no_finding, operator, aircraft_registration, defect_description, remarks, status.</p>
                
                <div style="margin-bottom: 1.5rem;">
                    <input type="file" wire:model="file" class="mod-search-input cbm-file-input" style="width: 100%; font-size: 0.8125rem;">
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button wire:click="$set('isImportModalOpen', false)" class="mod-btn-secondary">Batal</button>
                    <button wire:click="import" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="file, import">
                        <span wire:loading.remove wire:target="import">Import Data</span>
                        <span wire:loading wire:target="import">Importing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
