<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Rekap Data & Archive</div>
            <div class="mod-title">Daily Report</div>
            <div class="mod-subtitle">Pusat penampungan laporan harian, bulanan, dan tahunan untuk semua modul.</div>
        </div>
        <div class="mod-actions" style="display: flex; gap: 0.5rem; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="file" wire:model="importFile" id="importFile" class="hidden" style="display: none;">
                <label for="importFile" class="mod-btn-outline" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    Pilih File Excel
                </label>
                @if($importFile)
                    <button wire:click="importExcel" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="importExcel">
                        <span wire:loading.remove wire:target="importExcel">Import Data</span>
                        <span wire:loading wire:target="importExcel">Importing...</span>
                    </button>
                @endif
            </div>

            <button class="mod-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Export Semua Data
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success" style="padding: 1rem; margin-bottom: 1rem; background: #d4edda; color: #155724; border-radius: 4px;">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger" style="padding: 1rem; margin-bottom: 1rem; background: #f8d7da; color: #721c24; border-radius: 4px;">
            {{ session('error') }}
        </div>
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
        </div>

        {{-- Toolbar --}}
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="display: flex; gap: 10px; align-items: center;">
                <div style="position: relative; display: flex; align-items: center;">
                    <svg style="position: absolute; left: 10px; width: 18px; height: 18px; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live="search" class="mod-search-input" type="text" placeholder="Cari data..." style="padding-left: 35px;">
                </div>
                <input wire:model.live="dateFilter" type="date" class="mod-search-input">
            </div>
            <span class="mod-record-count">{{ collect($logs)->count() }} records</span>
        </div>

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
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">{{ $log->hold_remarks ?? $log->remarks ?? '' }}</td>
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
                                        @if($log->dja_id)
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
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">{{ $log->part_description ?? '' }}</td>
                                <td>{{ $log->defer ?? '' }}</td>
                                <td>{{ $log->aoc ?? '' }}</td>
                                <td>{{ $log->type ?? '' }}</td>
                                <td>{{ $log->plan_station ?? '' }}</td>
                                <td>{{ $log->plan_date ? \Carbon\Carbon::parse($log->plan_date)->format('d M Y') : '' }}</td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">{{ $log->remarks ?? '' }}</td>
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
                @endif
            </table>
        </div>

    </div>

</div>
