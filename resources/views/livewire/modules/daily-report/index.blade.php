<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Rekap Data & Archive</div>
            <div class="mod-title">Daily Report</div>
            <div class="mod-subtitle">Pusat penampungan laporan harian, bulanan, dan tahunan untuk semua modul.</div>
        </div>
        <div class="mod-actions">
            <div x-data="{
                    busy: false,
                    async upload(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        this.busy = true;
                        const toast = (icon, message) => window.Swal && Swal.fire({ toast: true, position: 'top-end', icon, title: message, showConfirmButton: false, timer: 5000, timerProgressBar: true });
                        try {
                            const res = await fetch('{{ route('daily-report.import-raw') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'X-File-Name': encodeURIComponent(file.name),
                                    'Content-Type': 'application/octet-stream',
                                    'Accept': 'application/json',
                                },
                                body: file,
                            });
                            let json = {};
                            try { json = await res.json(); } catch (err) { json = { message: 'Gagal membaca response server (HTTP ' + res.status + ')' }; }
                            if (res.ok) { toast('success', json.message ?? 'Import berhasil.'); $wire.$refresh(); }
                            else { toast('error', json.message || ('Error HTTP ' + res.status)); }
                        } catch (err) {
                            toast('error', 'Gagal menghubungi server: ' + err.message);
                        } finally {
                            this.busy = false;
                            e.target.value = '';
                        }
                    }
                 }">
                <input type="file" id="rawImport" accept=".xlsx,.xls" style="display:none;" @change="upload($event)" :disabled="busy">
                <label for="rawImport" class="mod-btn-outline" :style="busy ? 'opacity:.6;pointer-events:none;' : 'cursor:pointer;'" style="margin:0;">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg x-show="!busy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        <span x-show="busy" class="cbm-spinner" x-cloak></span>
                        <span x-text="busy ? 'Memproses, mohon tunggu...' : 'Pilih File & Import'"></span>
                    </span>
                </label>
            </div>
            <button wire:click="exportExcel" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="exportExcel" style="display:inline-flex;align-items:center;justify-content:center;gap:.4rem;">
                <span wire:loading.remove wire:target="exportExcel">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem;height:1.25rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Export Excel
                    </span>
                </span>
                <span wire:loading wire:target="exportExcel">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg style="width:1.25rem;height:1.25rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                        Mengexport...
                    </span>
                </span>
            </button>
            <button wire:click="generateCodReport" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="generateCodReport" style="display:inline-flex;align-items:center;justify-content:center;gap:.4rem;">
                <span wire:loading.remove wire:target="generateCodReport">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.2rem;height:1.2rem;"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                        Format COD
                    </span>
                </span>
                <span wire:loading wire:target="generateCodReport">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg style="width:1.2rem;height:1.2rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                        Memuat...
                    </span>
                </span>
            </button>
        </div>
    </div>
    <x-flash />
    @if ($errors->any())
        <div class="cbm-flash cbm-flash-error" role="alert" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    {{-- Main Card --}}
    <div class="mod-card mod-card-accent-blue">
        
        {{-- Tabs --}}
        <div class="cbm-tabs">
            <button type="button" wire:click="setTab('dja-wo')" class="cbm-tab {{ $activeTab === 'dja-wo' ? 'active' : '' }}">DJA WO</button>
            <button type="button" wire:click="setTab('unplanned-wo')" class="cbm-tab {{ $activeTab === 'unplanned-wo' ? 'active' : '' }}">Unplanned WO</button>
            <button type="button" wire:click="setTab('dja-dmi')" class="cbm-tab {{ $activeTab === 'dja-dmi' ? 'active' : '' }}">DJA DMI</button>
            <button type="button" wire:click="setTab('unplanned-dmi')" class="cbm-tab {{ $activeTab === 'unplanned-dmi' ? 'active' : '' }}">Unplanned DMI</button>
            <button type="button" wire:click="setTab('dja-nsrdi')" class="cbm-tab {{ $activeTab === 'dja-nsrdi' ? 'active' : '' }}">DJA NSRDI</button>
            <button type="button" wire:click="setTab('unplanned-nsrdi')" class="cbm-tab {{ $activeTab === 'unplanned-nsrdi' ? 'active' : '' }}">Unplanned NSRDI</button>
            <button type="button" wire:click="setTab('cml')" class="cbm-tab {{ $activeTab === 'cml' ? 'active' : '' }}">CML</button>
            <button type="button" wire:click="setTab('summary')" class="cbm-tab {{ $activeTab === 'summary' ? 'active' : '' }}">Summary Pivot</button>
        </div>

        <div class="mod-hint" style="margin:1rem 1.25rem 0;background:var(--cbm-nav-hover);color:var(--cbm-text-muted);border-color:var(--cbm-card-border);">
            Arsip menampilkan data <strong>sebelum {{ \Carbon\Carbon::parse($cutoffDate)->locale('id')->isoFormat('D MMMM YYYY') }}</strong>; data hari aktif ada di modul masing-masing.
        </div>

        @if(array_sum($held) > 0)
            <div class="mod-hint mod-hint-warn" style="margin:1rem 1.25rem 0;">
                <strong>{{ array_sum($held) }} log belum masuk arsip</strong>
                ({{ collect($held)->filter()->map(fn ($n, $k) => $n.' '.strtoupper($k))->implode(', ') }}):
                sudah lewat cutoff tetapi masih Open tanpa code reason + remarks. Lengkapi di modul WO / DMI / NSRDI, lalu otomatis masuk ke sini.
            </div>
        @endif

        @if($activeTab === 'summary')
            <x-log-toolbar mode="date" :active="(bool) $dateFilter" />
        @else
            <x-log-toolbar :logs="$logs" mode="date" :active="(bool) ($search || $dateFilter)" placeholder="Cari data..." />
        @endif

        {{-- Dynamic Tables Content --}}
        <div class="mod-table-wrap">
            <table class="mod-table" style="white-space: nowrap;">
                
                {{-- 1. WO Format (DJA & Unplanned) --}}
                @if(in_array($activeTab, ['dja-wo', 'unplanned-wo']))
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
                                <td><x-text-popup :text="$log->description ?? ''" title="Action Taken / Reason" /></td>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16">
                                    <div class="mod-empty">
                                        <div class="mod-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                        </div>
                                        <div class="mod-empty-title">Belum ada data Work Order</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                
                {{-- 2. DMI Format (DJA & Unplanned) --}}
                @elseif(in_array($activeTab, ['dja-dmi', 'unplanned-dmi']))
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>REG</th>
                            <th>DMI DESCRIPTION</th>
                            <th>PN REQUIRED</th>
                            <th>DMI NO</th>
                            <th>DMI CAT</th>
                            <th>PLAN STA</th>
                            <th>CAT</th>
                            <th>STATUS</th>
                            <th>REMARK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->dailyJobAssignment ? \Carbon\Carbon::parse($log->dailyJobAssignment->date)->format('d M Y') : ($log->date ? \Carbon\Carbon::parse($log->date)->format('d M Y') : '') }}</td>
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
                                <td><x-text-popup :text="$log->description ?? ''" title="Action Taken / Reason" /></td>
                                <td>{{ $log->pn_required ?? '' }}</td>
                                <td>{{ $log->dmi_number ?? '' }}</td>
                                <td>{{ $log->dmi_category ?? '' }}</td>
                                <td>{{ $log->plan_station ?? '' }}</td>
                                <td>{{ $log->category ?? '' }}</td>
                                <td>
                                    @if(($log->status ?? '') === 'Closed')
                                        <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                    @else
                                        <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#f87171;"></span>Open</span>
                                    @endif
                                </td>
                                <td><x-text-popup :text="$log->hold_remarks ?? $log->remarks ?? ''" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="mod-empty">
                                        <div class="mod-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                                        </div>
                                        <div class="mod-empty-title">Belum ada data DMI</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                {{-- 3. NSRDI Format (DJA & Unplanned) --}}
                @elseif(in_array($activeTab, ['dja-nsrdi', 'unplanned-nsrdi']))
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>WG</th>
                            <th>AC REG</th>
                            <th>NSRDI</th>
                            <th>FINDING DESCRIPTION</th>
                            <th>CATEGORY</th>
                            <th>REPORT DATE</th>
                            <th>DUE DATE</th>
                            <th>PART NUMBER</th>
                            <th>PART DESCRIPTION</th>
                            <th>DEFER</th>
                            <th>AOC</th>
                            <th>TYPE</th>
                            <th>PLAN STA</th>
                            <th>PLAN DATE</th>
                            <th>REMARKS</th>
                            <th>STATUS</th>
                            <th>CLOSE DATE</th>
                            <th>ACT STA</th>
                            <th>CODE REASON</th>
                            <th>REASON OPEN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->dailyJobAssignment ? \Carbon\Carbon::parse($log->dailyJobAssignment->date)->format('d M Y') : ($log->plan_date ? \Carbon\Carbon::parse($log->plan_date)->format('d M Y') : '') }}</td>
                                <td>{{ $log->work_group ?? '' }}</td>
                                <td>
                                    <div class="mod-aircraft-name">{{ $log->aircraft_registration ?? '' }}</div>
                                    <div class="mod-aircraft-sub">
                                        @if($log->dja_id || $log->import_source === 'dja')
                                            <span class="mod-type-planned">Planned</span>
                                        @else
                                            <span class="mod-type-unplanned">Unplanned</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $log->nsrdi_number ?? '' }}</td>
                                <td><x-text-popup :text="$log->description ?? ''" title="Action Taken / Reason" /></td>
                                <td>{{ $log->category ?? '' }}</td>
                                <td>{{ $log->report_date ? \Carbon\Carbon::parse($log->report_date)->format('d M Y') : '' }}</td>
                                <td>{{ $log->due_date ? \Carbon\Carbon::parse($log->due_date)->format('d M Y') : '' }}</td>
                                <td>{{ $log->part_number ?? '' }}</td>
                                <td><x-text-popup :text="$log->part_description ?? ''" /></td>
                                <td>{{ $log->defer ?? '' }}</td>
                                <td>{{ $log->aoc ?? '' }}</td>
                                <td>{{ $log->type ?? '' }}</td>
                                <td>{{ $log->plan_station ?? '' }}</td>
                                <td>{{ $log->plan_date ? \Carbon\Carbon::parse($log->plan_date)->format('d M Y') : '' }}</td>
                                <td><x-text-popup :text="$log->remarks ?? ''" /></td>
                                <td>
                                    @if(($log->status ?? '') === 'Closed')
                                        <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                    @else
                                        <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#f87171;"></span>Open</span>
                                    @endif
                                </td>
                                <td>{{ $log->close_date ? \Carbon\Carbon::parse($log->close_date)->format('d M Y') : '' }}</td>
                                <td>{{ $log->act_station ?? '' }}</td>
                                <td>{{ $log->hold_reason_category ?? '' }}</td>
                                <td>{{ $log->hold_remarks ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="21">
                                    <div class="mod-empty">
                                        <div class="mod-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                        </div>
                                        <div class="mod-empty-title">Belum ada data NSRDI</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                {{-- 4. CML Format --}}
                @elseif($activeTab === 'cml')
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="mod-empty">
                                        <div class="mod-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                        </div>
                                        <div class="mod-empty-title">Belum ada data CML</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                {{-- 5. Summary Pivot --}}
                @elseif($activeTab === 'summary')
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>STATION</th>
                            <th style="text-align: center;">TOTAL WO</th>
                            <th style="text-align: center;">TOTAL DMI</th>
                            <th style="text-align: center;">TOTAL NSRDI</th>
                            <th style="text-align: center;">TOTAL CML</th>
                            <th style="text-align: center; background: rgba(255, 255, 255, 0.05);">GRAND TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $sumWO = 0; $sumDMI = 0; $sumNSRDI = 0; $sumCML = 0; $sumTotal = 0;
                            $idx = 1;
                        @endphp
                        @forelse($logs as $sta => $data)
                            @php
                                $sumWO += $data['WO'];
                                $sumDMI += $data['DMI'];
                                $sumNSRDI += $data['NSRDI'];
                                $sumCML += $data['CML'];
                                $sumTotal += $data['TOTAL'];
                            @endphp
                            <tr>
                                <td>{{ $idx++ }}</td>
                                <td style="font-weight: bold;">{{ $sta === 'LAINNYA' ? 'Lainnya (di luar master)' : $sta }}</td>
                                <td style="text-align: center;">{{ $data['WO'] ?: '-' }}</td>
                                <td style="text-align: center;">{{ $data['DMI'] ?: '-' }}</td>
                                <td style="text-align: center;">{{ $data['NSRDI'] ?: '-' }}</td>
                                <td style="text-align: center;">{{ $data['CML'] ?: '-' }}</td>
                                <td style="text-align: center; font-weight: bold; background: rgba(255, 255, 255, 0.02);">{{ $data['TOTAL'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="mod-empty">
                                        <div class="mod-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                        </div>
                                        <div class="mod-empty-title">Belum ada data Summary</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($logs) > 0)
                        <tfoot>
                            <tr style="background: rgba(255, 255, 255, 0.05); font-weight: bold;">
                                <td colspan="2" style="text-align: right;">TOTAL OVERALL:</td>
                                <td style="text-align: center;">{{ $sumWO }}</td>
                                <td style="text-align: center;">{{ $sumDMI }}</td>
                                <td style="text-align: center;">{{ $sumNSRDI }}</td>
                                <td style="text-align: center;">{{ $sumCML }}</td>
                                <td style="text-align: center; font-size: 1.1em;">{{ $sumTotal }}</td>
                            </tr>
                        </tfoot>
                    @endif
                @endif
            </table>
        </div>

        @if($activeTab !== 'summary' && method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="mod-pagination">{{ $logs->links('pagination::tailwind') }}</div>
        @endif

    </div>

    {{-- ═══════ Format COD Modal ═══════ --}}
    @if($showCodModal)
    <div class="cbm-modal-overlay"
         x-data="{
             copied: false,
             copyText() {
                 const el = document.getElementById('codReportTextarea');
                 if (!el) return;
                 navigator.clipboard.writeText(el.value).then(() => {
                     this.copied = true;
                     setTimeout(() => this.copied = false, 2500);
                 });
             }
         }"
         x-on:keydown.escape.window="$wire.set('showCodModal', false)"
         wire:click.self="$set('showCodModal', false)"
         style="display:flex; z-index:9999;"
    >
        <div class="cbm-modal-panel" @click.stop style="max-width:54rem; width:100%; max-height:92vh; display:flex; flex-direction:column; overflow:hidden;">
            <div class="cbm-modal-header" style="flex-shrink:0;">
                <div>
                    <div class="cbm-modal-title" style="display:flex;align-items:center;gap:.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:1.35rem;height:1.35rem;color:var(--cbm-primary);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span>Laporan Sementara Cabin On-Duty (COD)</span>
                    </div>
                    <div class="cbm-modal-subtitle">Format pesan produksi harian dari DJA. Pilih grup WhatsApp atau Telegram tujuan lalu kirim langsung dari UI.</div>
                </div>
                <button class="cbm-modal-close" wire:click="$set('showCodModal', false)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cbm-modal-body" style="padding-top:.75rem; padding-bottom:.5rem; overflow-y:auto; flex:1;">
                {{-- Date Selector Bar --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;gap:.75rem;flex-wrap:wrap;background:rgba(255,255,255,0.02);padding:.5rem .75rem;border-radius:.5rem;border:1px solid var(--cbm-card-border);">
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                        <span style="font-size:.85rem;color:var(--cbm-text-muted);font-weight:600;">Tanggal:</span>
                        <input type="date" wire:model.live="codReportDate" wire:change="generateCodReport" class="mod-input" style="padding:.25rem .5rem;font-size:.85rem;border-radius:4px;">
                        <span style="font-size:.75rem;color:var(--cbm-text-muted);">(Cutoff 18:00 WIB, otomatis D-1 pada shift pagi)</span>
                    </div>
                    <div style="display:flex;gap:.5rem;">
                        <button type="button" @click="copyText()" class="mod-btn-outline" style="padding:.25rem .65rem;font-size:.8rem;display:inline-flex;align-items:center;gap:.3rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:1rem;height:1rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.625V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                            </svg>
                            <span x-text="copied ? 'Tersalin!' : 'Salin Teks'"></span>
                        </button>
                        <button type="button" wire:click="generateCodReport" class="mod-btn-outline" style="padding:.25rem .65rem;font-size:.8rem;">
                            Refresh Data
                        </button>
                    </div>
                </div>

                {{-- Textarea Container --}}
                <div style="position:relative; margin-bottom:1rem;">
                    <textarea id="codReportTextarea" readonly
                              rows="12"
                              style="width:100%;font-family:'Courier New',Courier,monospace;font-size:.85rem;line-height:1.45;background:#0d1117;color:#c9d1d9;border:1px solid #30363d;border-radius:6px;padding:.75rem;resize:vertical;white-space:pre;"
                    >{{ $codReportText }}</textarea>
                </div>

                {{-- Delivery Destination Section --}}
                <div style="display:grid; grid-template-columns: 1.3fr 1fr; gap:1rem; align-items:start;">
                    
                    {{-- 1. WhatsApp Delivery Box --}}
                    <div style="background:rgba(37,211,102,0.03); border:1px solid rgba(37,211,102,0.2); border-radius:8px; padding:.85rem;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.6rem;">
                            <div style="display:flex; align-items:center; gap:.4rem; font-weight:700; color:#25D366; font-size:.9rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.2rem;height:1.2rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span>Pilih Grup WhatsApp</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:.4rem;">
                                <button type="button" wire:click="toggleSelectAllWaGroups" class="mod-btn-outline" style="padding:.2rem .45rem; font-size:.72rem;">
                                    {{ count($selectedWaGroups) === count($waGroups) && count($waGroups) > 0 ? 'Batal Semua' : 'Pilih Semua' }}
                                </button>
                                <button type="button" wire:click="loadWhatsAppGroups" wire:loading.attr="disabled" class="mod-btn-outline" style="padding:.2rem .45rem; font-size:.72rem;" title="Tarik daftar grup langsung dari akun WhatsApp">
                                    <span wire:loading.remove wire:target="loadWhatsAppGroups">🔄 Sync Grup</span>
                                    <span wire:loading wire:target="loadWhatsAppGroups">Memuat...</span>
                                </button>
                            </div>
                        </div>

                        {{-- Groups Checklist --}}
                        <div style="max-height:140px; overflow-y:auto; border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:.4rem; background:rgba(0,0,0,0.2); margin-bottom:.5rem;">
                            @forelse($waGroups as $g)
                                <label style="display:flex; align-items:center; gap:.5rem; padding:.35rem .5rem; border-radius:4px; cursor:pointer; font-size:.82rem; border-bottom:1px solid rgba(255,255,255,0.03);">
                                    <input type="checkbox" value="{{ $g['id'] }}" wire:model.live="selectedWaGroups" style="accent-color:#25D366; cursor:pointer;">
                                    <span style="font-weight:600; color:var(--cbm-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $g['name'] }}">{{ $g['name'] }}</span>
                                    <span style="font-size:.7rem; color:var(--cbm-text-muted); font-family:monospace; margin-left:auto; opacity:.7;">{{ Str::limit($g['id'], 18) }}</span>
                                </label>
                            @empty
                                <div style="text-align:center; padding:.8rem; color:var(--cbm-text-muted); font-size:.8rem;">
                                    Belum ada grup termuat. Klik <strong>Sync Grup</strong> atau ketik nomor di bawah.
                                </div>
                            @endforelse
                        </div>

                        {{-- Add Manual Target --}}
                        <div style="display:flex; gap:.4rem; margin-bottom:.6rem;">
                            <input type="text" wire:model="manualWaTarget" placeholder="Nomor (0812...) atau Group ID" class="mod-input" style="flex:1; padding:.25rem .5rem; font-size:.8rem;" wire:keydown.enter.prevent="addManualWaTarget">
                            <button type="button" wire:click="addManualWaTarget" class="mod-btn-outline" style="padding:.25rem .6rem; font-size:.75rem;">
                                + Tambah
                            </button>
                        </div>

                        {{-- Send Button --}}
                        <button type="button" wire:click="sendCodReportToSelectedGroups" wire:loading.attr="disabled" wire:target="sendCodReportToSelectedGroups" class="mod-btn-primary" style="width:100%; justify-content:center; background:#25D366; border-color:#25D366; color:#000; font-weight:700; display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .85rem;">
                            <span wire:loading.remove wire:target="sendCodReportToSelectedGroups" style="display:inline-flex; align-items:center; gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.15rem;height:1.15rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span>Kirim ke {{ count($selectedWaGroups) }} Grup Terpilih</span>
                            </span>
                            <span wire:loading wire:target="sendCodReportToSelectedGroups">
                                Mengirim ke WhatsApp...
                            </span>
                        </button>
                    </div>

                    {{-- 2. Telegram Delivery & Bot Box --}}
                    <div style="background:rgba(34,158,217,0.03); border:1px solid rgba(34,158,217,0.2); border-radius:8px; padding:.85rem; display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; align-items:center; gap:.4rem; font-weight:700; color:#229ED9; font-size:.9rem; margin-bottom:.6rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.2rem;height:1.2rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.197 1.006.128.828.942z"/></svg>
                                <span>Kirim / Setting Telegram</span>
                            </div>

                            <div style="margin-bottom:.6rem;">
                                <label style="font-size:.78rem; color:var(--cbm-text-muted); display:block; margin-bottom:.25rem;">Target Chat ID (Grup / Channel):</label>
                                <input type="text" wire:model="telegramChatId" placeholder="-100xxxxxxxxxx" class="mod-input" style="width:100%; padding:.3rem .55rem; font-size:.82rem;">
                            </div>

                            <button type="button" wire:click="sendCodReportViaTelegram" wire:loading.attr="disabled" wire:target="sendCodReportViaTelegram" class="mod-btn-primary" style="width:100%; justify-content:center; background:#229ED9; border-color:#229ED9; color:#fff; font-weight:700; display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .85rem; margin-bottom:.75rem;">
                                <span wire:loading.remove wire:target="sendCodReportViaTelegram" style="display:inline-flex; align-items:center; gap:.4rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:1.15rem;height:1.15rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.197 1.006.128.828.942z"/></svg>
                                    <span>Kirim ke Telegram</span>
                                </span>
                                <span wire:loading wire:target="sendCodReportViaTelegram">
                                    Mengirim ke Telegram...
                                </span>
                            </button>
                        </div>

                        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:6px; padding:.5rem .65rem; font-size:.75rem; color:var(--cbm-text-muted); line-height:1.4;">
                            💡 <strong>Mode Bot Telegram:</strong> Anggota tim juga bisa langsung ketik <code>/cod</code> atau <code>/status</code> di Telegram untuk meminta laporan ini secara otomatis kapan saja.
                        </div>
                    </div>

                </div>
            </div>

            <div class="cbm-modal-footer" style="flex-shrink:0; display:flex; justify-content:flex-end;">
                <button type="button" wire:click="$set('showCodModal', false)" class="mod-btn-outline" style="padding:.4rem 1.2rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
