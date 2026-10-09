<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Findings</div>
            <div class="mod-title">ICT Findings (PI)</div>
            <div class="mod-subtitle">Daftar temuan ICT, monitoring, dan tindak lanjutnya.</div>
        </div>
        <div class="mod-actions">
            <button wire:click="export" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="export">
                <span wire:loading.remove wire:target="export">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Export Excel
                    </span>
                </span>
                <span wire:loading wire:target="export"><span style="display:inline-flex;align-items:center;gap:.4rem;"><span class="cbm-spinner"></span>Mengexport...</span></span>
            </button>
            <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Import Excel
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-purple">
        <div class="cbm-tabs">
            @foreach(['' => 'Semua', 'Open' => 'Open', 'Closed' => 'Closed'] as $value => $label)
                <button type="button" wire:click="$set('statusFilter', '{{ $value }}')" class="cbm-tab {{ $statusFilter === $value ? 'active' : '' }}">{{ $label }}</button>
            @endforeach
        </div>

        <x-log-toolbar :logs="$findings" mode="date" :active="(bool) ($search || $dateFilter || $statusFilter)" placeholder="Cari finding, a/c reg, operator..." />

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width:3rem;">NO</th>
                        <th>DATE</th>
                        <th>NO FINDING</th>
                        <th>OPERATOR</th>
                        <th>REG A/C</th>
                        <th>DEFECT DESCRIPTION</th>
                        <th>REMARKS</th>
                        <th>STATUS</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($findings as $index => $finding)
                        <tr wire:key="ict-{{ $finding->id }}">
                            <td>{{ $findings->firstItem() + $index }}</td>
                            <td style="white-space:nowrap;">{{ $finding->date ? \Carbon\Carbon::parse($finding->date)->format('d M Y') : '-' }}</td>
                            <td>{{ $finding->no_finding }}</td>
                            <td>{{ $finding->operator ?? '-' }}</td>
                            <td><div class="mod-aircraft-name">{{ $finding->aircraft_registration }}</div></td>
                            <td><x-text-popup :text="$finding->defect_description ?? '-'" title="Defect Description" /></td>
                            <td><x-text-popup :text="$finding->remarks ?? '-'" title="Remarks" /></td>
                            <td>
                                @if(($finding->status ?? 'Open') === 'Closed')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                @else
                                    <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#f87171;"></span>Open</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <button wire:click="editFinding({{ $finding->id }})" class="mod-btn-outline" style="padding:.25rem .625rem;font-size:.75rem;">Update</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="mod-empty">
                                    <div class="mod-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                                    </div>
                                    <div class="mod-empty-title">{{ ($search || $dateFilter || $statusFilter) ? 'Tidak ada data yang cocok' : 'Belum ada temuan ICT' }}</div>
                                    <div class="mod-empty-sub">{{ ($search || $dateFilter || $statusFilter) ? 'Ubah kata kunci, tanggal, atau reset filter.' : 'Klik "Import Excel" untuk mengunggah data.' }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($findings->hasPages())
            <div class="mod-pagination">{{ $findings->links('pagination::tailwind') }}</div>
        @endif
    </div>

    {{-- ═══════ Update Modal ═══════ --}}
    @if($showEditModal)
    <div class="cbm-modal-overlay" x-data x-on:keydown.escape.window="$wire.closeEditModal()" wire:click.self="closeEditModal" style="display:flex;">
        <div class="cbm-modal-panel" style="max-width:27.5rem;" @click.stop>
            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">Update Finding</div>
                    <div class="cbm-modal-subtitle">Perbarui status dan tindak lanjut temuan ini</div>
                </div>
                <button class="cbm-modal-close" wire:click="closeEditModal" aria-label="Tutup">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="cbm-modal-body">
                <div class="cbm-form-group">
                    <label class="cbm-form-label">Status</label>
                    <div class="cbm-select-wrap">
                        <select wire:model.live="editStatus" class="cbm-form-select {{ $editStatus === 'Open' ? 'cbm-status-open' : 'cbm-status-closed' }}">
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    @error('editStatus') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group" style="margin-bottom:0;">
                    <label class="cbm-form-label">Remarks (alasan / tindak lanjut)</label>
                    <textarea wire:model="editRemarks" class="cbm-form-textarea" placeholder="Tuliskan alasan atau tindak lanjut..."></textarea>
                    @error('editRemarks') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="cbm-modal-footer">
                <button wire:click="closeEditModal" class="mod-btn-outline">Batal</button>
                <button wire:click="saveFinding" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="saveFinding">
                    <span wire:loading.remove wire:target="saveFinding">Simpan Perubahan</span>
                    <span wire:loading wire:target="saveFinding"><span class="cbm-spinner"></span> Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════ Import Modal ═══════ --}}
    @if($isImportModalOpen)
    <div class="cbm-modal-overlay" x-data x-on:keydown.escape.window="$wire.set('isImportModalOpen', false)" wire:click.self="$set('isImportModalOpen', false)" style="display:flex;">
        <div class="cbm-modal-panel" @click.stop>
            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">Import ICT Findings</div>
                    <div class="cbm-modal-subtitle">Kolom: date, no_finding, operator, aircraft_registration, defect_description, remarks, status</div>
                </div>
                <button class="cbm-modal-close" wire:click="$set('isImportModalOpen', false)" aria-label="Tutup">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit="import">
                <div class="cbm-modal-body">
                    <div class="cbm-upload-zone">
                        <input type="file" wire:model="file" accept=".xlsx,.xls,.csv">
                        <div class="cbm-upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                        <div class="cbm-upload-title">{{ $file ? $file->getClientOriginalName() : 'Klik untuk pilih file' }}</div>
                        <div class="cbm-upload-sub">atau drag &amp; drop ke sini</div>
                        <div class="cbm-upload-badge"><span>.xlsx</span><span>.xls</span><span>.csv</span></div>
                        <div wire:loading wire:target="file" style="margin-top:.75rem;font-size:.8125rem;color:var(--cbm-text-muted);">Mengunggah file...</div>
                    </div>
                    @error('file') <span style="color:#f87171;font-size:.75rem;display:block;margin-top:.5rem;font-weight:600;">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-modal-footer">
                    <button type="button" wire:click="$set('isImportModalOpen', false)" class="mod-btn-outline">Batal</button>
                    <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="file, import">
                        <span wire:loading.remove wire:target="import">Upload &amp; Import</span>
                        <span wire:loading wire:target="import"><span class="cbm-spinner"></span> Mengimport...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
