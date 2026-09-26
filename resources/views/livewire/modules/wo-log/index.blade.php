<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-green">Work Order</div>
            <div class="mod-title">WO</div>
            <div class="mod-subtitle">Work Order — dokumen pekerjaan teknis kabin pesawat.</div>
        </div>
        <div class="mod-actions">
                        <button wire:click="exportExcel" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="exportExcel">
                <span wire:loading.remove wire:target="exportExcel" style="display:inline-flex;align-items:center;gap:.4rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem;height:1.25rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Export Excel
                </span>
                <span wire:loading wire:target="exportExcel" style="display:inline-flex;align-items:center;gap:.4rem;">
                    <svg style="width:1.25rem;height:1.25rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                    Mengexport...
                </span>
            </button>
            <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Import WO
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-green">
        <div class="cbm-tabs">
            <button type="button" wire:click="setTab('planned')" class="cbm-tab {{ $activeTab === 'planned' ? 'active' : '' }}">DJA (Planned)</button>
            <button type="button" wire:click="setTab('unplanned')" class="cbm-tab {{ $activeTab === 'unplanned' ? 'active' : '' }}">Unplanned</button>
        </div>
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
        <div class="mod-table-wrap">
            <table class="mod-table" style="white-space: nowrap;">
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>WG</th>
                        <th>AC REG</th>
                        <th>WO</th>
                        <th>WO CAT</th>
                        <th>WO DESCRIPTION</th>
                        <th>PN PICKLIST</th>
                        <th>MAN HOUR</th>
                        <th>OPERATOR</th>
                        <th>TYPE</th>
                        <th>PLAN STA</th>
                        <th>REMARKS PPC TO LM</th>
                        <th>ACT STA</th>
                        <th>STATUS</th>
                        <th>CODE REASON</th>
                        <th>REASON OPEN</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->dailyJobAssignment ? \Carbon\Carbon::parse($log->dailyJobAssignment->date)->format('d M Y') : ($log->date ? \Carbon\Carbon::parse($log->date)->format('d M Y') : '') }}</td>
                            <td>{{ $log->work_group ?? '' }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $log->aircraft_registration ?? '' }}</div>
                                <div class="mod-aircraft-sub">
                                    @if($log->dja_id)
                                        <span class="mod-type-planned">Planned</span>
                                    @else
                                        <span class="mod-type-unplanned">Unplanned</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $log->wo_number ?? '' }}</td>
                            <td>{{ $log->wo_category ?? '' }}</td>
                            <td><x-text-popup :text="$log->description ?? ''" title="WO Description" /></td>
                            <td>{{ $log->pn_picklist ?? '' }}</td>
                            <td>{{ $log->man_hour ?? '' }}</td>
                            <td>{{ $log->operator ?? '' }}</td>
                            <td>{{ $log->type ?? '' }}</td>
                            <td>{{ $log->plan_station ?? '' }}</td>
                            <td><x-text-popup :text="$log->remarks_ppc_to_lm ?? ''" title="Remarks PPC to LM" /></td>
                            <td>{{ $log->act_station ?? '' }}</td>
                            <td>
                                @if(($log->status ?? '') === 'Closed')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                @else
                                    <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#f87171;"></span>Open</span>
                                @endif
                            </td>
                            <td>{{ $log->hold_reason_category ?? '' }}</td>
                            <td>{{ $log->hold_remarks ?? '' }}</td>
                            <td style="text-align:right;">
                                <button wire:click="openStatusModal({{ $log->id }})" class="mod-btn-outline" style="padding: 0.25rem 0.625rem; font-size: 0.75rem; active:scale-95;">
                                    Update Status
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17">
                                <div class="mod-empty">
                                    <div class="mod-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                    </div>
                                    <div class="mod-empty-title">Belum ada data WO</div>
                                    <div class="mod-empty-sub">Klik "Import WO" untuk mengunggah data.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════ Update Status Modal ═══════ --}}
    @if($isModalOpen)
    <div class="cbm-modal-overlay"
         x-data x-init
         x-on:keydown.escape.window="$wire.set('isModalOpen', false)"
         style="display:flex;"
         x-show="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
    >
        <div class="cbm-modal-panel"
             style="max-width:440px;"
             x-show="true"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             @click.stop>

            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">Update Status WO</div>
                    <div class="cbm-modal-subtitle">Perbarui status penyelesaian pekerjaan ini</div>
                </div>
                <button class="cbm-modal-close" wire:click="$set('isModalOpen', false)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cbm-modal-body">
                {{-- Status Select --}}
                <div class="cbm-form-group">
                    <label class="cbm-form-label">Status Pekerjaan</label>
                    <div class="cbm-select-wrap">
                        <select wire:model.live="status" class="cbm-form-select {{ $status === 'Open' ? 'cbm-status-open' : 'cbm-status-closed' }}">
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>

                {{-- Hold Reason (animated, only shown when Open) --}}
                @if($status === 'Open')
                <div class="cbm-hold-section"
                     x-data x-show="true"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="cbm-hold-label">Alasan Open / Hold Reason</div>
                    <div class="cbm-form-group">
                        <label class="cbm-form-label">Code Reason</label>
                        <div class="cbm-select-wrap">
                            <select wire:model="hold_reason_category" class="cbm-form-select">
                                <option value="">Pilih Code...</option>
                                <option value="AUTHOR">AUTHOR</option>
                                <option value="DEFFECT">DEFFECT</option>
                                <option value="GSE">GSE</option>
                                <option value="IRR">IRR</option>
                                <option value="LT">LT</option>
                                <option value="MP">MP</option>
                                <option value="NS">NS</option>
                                <option value="NT">NT</option>
                                <option value="OCT">OCT</option>
                                <option value="TC">TC</option>
                                <option value="WT">WT</option>
                            </select>
                        </div>
                    </div>
                    <div class="cbm-form-group" style="margin-bottom:0;">
                        <label class="cbm-form-label">Reason Open (Remarks)</label>
                        <textarea wire:model="hold_remarks" class="cbm-form-textarea" placeholder="Ketikan penjelasan secara manual..."></textarea>
                    </div>
                </div>
                @endif
            </div>

            <div class="cbm-modal-footer">
                <button wire:click="$set('isModalOpen', false)" class="mod-btn-outline">Batal</button>
                <button wire:click="updateStatus" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="updateStatus">
                    <span wire:loading.remove wire:target="updateStatus">
                        <span style="display:inline-flex;align-items:center;gap:.4rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:.875rem;height:.875rem;"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                            Simpan Perubahan
                        </span>
                    </span>
                    <span wire:loading wire:target="updateStatus">
                        <span style="display:inline-flex;align-items:center;gap:.4rem;">
                            <span class="cbm-spinner"></span> Menyimpan...
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════ Import Modal ═══════ --}}
    @if($isImportModalOpen)
    <div class="cbm-modal-overlay"
         x-data x-init
         x-on:keydown.escape.window="$wire.set('isImportModalOpen', false)"
         style="display:flex;"
         x-show="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
    >
        <div class="cbm-modal-panel" @click.stop>
            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">Import Data WO</div>
                    <div class="cbm-modal-subtitle">Unggah file Excel untuk memuat data Work Order</div>
                </div>
                <button class="cbm-modal-close" wire:click="$set('isImportModalOpen', false)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cbm-modal-body" style="padding-bottom:0;">
                @if(session()->has('success'))
                <div class="cbm-flash cbm-flash-success" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
                @endif
                @if(session()->has('error'))
                <div class="cbm-flash cbm-flash-error" x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    {{ session('error') }}
                </div>
                @endif
            </div>

            <form wire:submit="importWo">
                <div class="cbm-modal-body">
                    <div class="cbm-upload-zone">
                        <input type="file" wire:model="file" accept=".xlsx,.xls,.csv">
                        <div class="cbm-upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                        <div class="cbm-upload-title">Klik untuk pilih file</div>
                        <div class="cbm-upload-sub">atau drag & drop ke sini</div>
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
                        <span wire:loading.remove wire:target="importWo">
                            <span style="display:inline-flex;align-items:center;gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:.875rem;height:.875rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Upload & Import
                            </span>
                        </span>
                        <span wire:loading wire:target="importWo">
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
