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
                <span wire:loading.remove wire:target="exportExcel">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Export Excel
                    </span>
                </span>
                <span wire:loading wire:target="exportExcel">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <span class="cbm-spinner"></span>
                        Mengexport...
                    </span>
                </span>
            </button>
            <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Import WO
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-green">
        <div class="cbm-tabs">
            <button type="button" wire:click="setTab('planned')" class="cbm-tab {{ $activeTab === 'planned' ? 'active' : '' }}">DJA (Planned)</button>
            <button type="button" wire:click="setTab('unplanned')" class="cbm-tab {{ $activeTab === 'unplanned' ? 'active' : '' }}">Unplanned</button>
        </div>
        <x-log-toolbar :logs="$logs" mode="date" :active="(bool) ($search || $dateFilter)" />
        @if($carryOver > 0)
            <div class="mod-hint mod-hint-warn" style="margin:1rem 1.25rem 0;">
                {{ $carryOver }} log dari hari sebelumnya masih tampil di sini karena belum Closed dan belum punya code reason + remarks. Setelah dilengkapi, otomatis masuk Daily Report.
            </div>
        @endif

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
                                <div style="margin-top: 4px;">
                                    @if($log->is_submitted)
                                        <span style="font-size: 0.7rem; color: #10b981; font-weight: 600; display: inline-flex; align-items: center; gap: 2px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:0.75rem;height:0.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            Sent to Daily Report
                                        </span>
                                    @else
                                        <span style="font-size: 0.7rem; color: #f59e0b; font-weight: 600; display: inline-flex; align-items: center; gap: 2px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:0.75rem;height:0.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Pending Reason
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $log->hold_reason_category ?? '' }}</td>
                            <td>{{ $log->hold_remarks ?? '' }}</td>
                            <td style="text-align:right;">
                                <x-log-status-actions :log="$log" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17">
                                <div class="mod-empty">
                                    <div class="mod-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                    </div>
                                    <div class="mod-empty-title">{{ ($search || $dateFilter) ? 'Tidak ada data yang cocok' : 'Belum ada data WO' }}</div>
                                    <div class="mod-empty-sub">{{ ($search || $dateFilter) ? 'Ubah kata kunci atau tanggal, atau reset filter.' : 'Klik "Import WO" untuk mengunggah data.' }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="mod-pagination">{{ $logs->links('pagination::tailwind') }}</div>
        @endif
    </div>

    {{-- ═══════ Status Open modal ═══════ --}}
    <x-log-status-modal :show="$isModalOpen" :codes="$this::REASON_CODES" />

    {{-- ═══════ Import Modal ═══════ --}}
    @if($isImportModalOpen)
    <div class="cbm-modal-overlay"
         x-data x-init
         x-on:keydown.escape.window="$wire.set('isImportModalOpen', false)"
         wire:click.self="$set('isImportModalOpen', false)"
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
                        <div class="cbm-upload-title">{{ $file ? $file->getClientOriginalName() : 'Klik untuk pilih file' }}</div>
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
                    <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="file, importWo">
                        <span wire:loading.remove wire:target="importWo">
                            <span style="display:inline-flex;align-items:center;gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
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
