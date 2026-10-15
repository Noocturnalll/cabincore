<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">NSRDI Overdue Management</div>
            <div class="mod-title">NSRDI Overdue</div>
            <div class="mod-subtitle">Daftar defect kabin NSRDI overdue, monitoring jatuh tempo (due date), dan sinkronisasi status otomatis dengan Bank Data &amp; Daily Report.</div>
        </div>
        <div class="mod-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button wire:click="syncData" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="syncData" style="color: #3b82f6; border-color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; min-width: 8.5rem;" title="Sinkronkan nomor defect dengan Bank Data / Daily Report">
                <span wire:loading.remove wire:target="syncData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.15rem;height:1.15rem;"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" /></svg>
                        Sync Status
                    </span>
                </span>
                <span wire:loading wire:target="syncData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg style="width:1.15rem;height:1.15rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                        Sinkronisasi...
                    </span>
                </span>
            </button>
            <button wire:click="exportData" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="exportData" style="display: inline-flex; align-items: center; justify-content: center; min-width: 8.75rem;">
                <span wire:loading.remove wire:target="exportData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.15rem;height:1.15rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Export Excel
                    </span>
                </span>
                <span wire:loading wire:target="exportData">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg style="width:1.15rem;height:1.15rem;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg>
                        Mengexport...
                    </span>
                </span>
            </button>
            <button wire:click="$set('isImportModalOpen', true)" class="mod-btn-primary" style="display:inline-flex;align-items:center;gap:.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.15rem;height:1.15rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Import Excel
            </button>
        </div>
    </div>

    {{-- Executive Stat Summary Cards --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
        <div class="mod-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #f59e0b;">
            <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.5rem; background: rgba(245, 158, 11, 0.12); color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.5rem;height:1.5rem;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
            </div>
            <div>
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--cbm-text-muted); letter-spacing: 0.05em;">Open Defect</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--cbm-text-main); line-height: 1.2;">{{ number_format($openCount) }}</div>
                <div style="font-size: 0.72rem; color: #f59e0b; font-weight: 500;">Perlu penanganan kabin</div>
            </div>
        </div>

        <div class="mod-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #ef4444;">
            <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.5rem; background: rgba(239, 68, 68, 0.12); color: #ef4444; display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.5rem;height:1.5rem;"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
            </div>
            <div>
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--cbm-text-muted); letter-spacing: 0.05em;">Lewat Due Date</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #ef4444; line-height: 1.2;">{{ number_format($overduePassCount) }}</div>
                <div style="font-size: 0.72rem; color: #ef4444; font-weight: 500;">Sudah melewati batas waktu</div>
            </div>
        </div>

        <div class="mod-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #10b981;">
            <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.5rem; background: rgba(16, 185, 129, 0.12); color: #10b981; display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.5rem;height:1.5rem;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
            </div>
            <div>
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--cbm-text-muted); letter-spacing: 0.05em;">Closed / Solved</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981; line-height: 1.2;">{{ number_format($closedCount) }}</div>
                <div style="font-size: 0.72rem; color: #10b981; font-weight: 500;">Tersinkron di Bank Data</div>
            </div>
        </div>

        <div class="mod-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #8b5cf6;">
            <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.5rem; background: rgba(139, 92, 246, 0.12); color: #8b5cf6; display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.5rem;height:1.5rem;"><path d="M2 3a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H3a1 1 0 01-1-1V3zm0 6a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H3a1 1 0 01-1-1V9zm1 5a1 1 0 00-1 1v2a1 1 0 001 1h14a1 1 0 001-1v-2a1 1 0 00-1-1H3z" /></svg>
            </div>
            <div>
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--cbm-text-muted); letter-spacing: 0.05em;">Total Database</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--cbm-text-main); line-height: 1.2;">{{ number_format($allCount) }}</div>
                <div style="font-size: 0.72rem; color: var(--cbm-text-muted); font-weight: 500;">Total record overdue</div>
            </div>
        </div>
    </div>

    {{-- Main Tabs Navigation --}}
    <div style="display:flex; gap:.5rem; margin-bottom:1.1rem; border-bottom:1px solid var(--cbm-card-border); padding-bottom:.65rem; align-items:center;">
        <button type="button" wire:click="setTab('open')" class="ch-tab {{ $activeTab === 'open' ? 'on' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;display:inline-block;vertical-align:-2px;margin-right:.3rem;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
            Tab Open / Outstanding <span class="cbm-tab-count" style="background:rgba(245,158,11,0.2);color:#f59e0b;">{{ number_format($openCount) }}</span>
        </button>
        <button type="button" wire:click="setTab('closed')" class="ch-tab {{ $activeTab === 'closed' ? 'on' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;display:inline-block;vertical-align:-2px;margin-right:.3rem;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
            Tab Closed <span class="cbm-tab-count" style="background:rgba(16,185,129,0.2);color:#10b981;">{{ number_format($closedCount) }}</span>
        </button>
        <button type="button" wire:click="setTab('all')" class="ch-tab {{ $activeTab === 'all' ? 'on' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;display:inline-block;vertical-align:-2px;margin-right:.3rem;"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
            Semua Data <span class="cbm-tab-count">{{ number_format($allCount) }}</span>
        </button>
    </div>

    {{-- Main Table Card --}}
    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar" style="display:flex; flex-wrap:wrap; gap:0.75rem; justify-content:space-between; align-items:center;">
            <div class="mod-search-wrap" style="display: flex; gap: 0.625rem; align-items: center; flex: 1; flex-wrap:wrap; min-width: 18rem;">
                <div style="position: relative; display: flex; align-items: center; width: 100%; max-width: 20rem;">
                    <svg style="position: absolute; left: 0.625rem; width: 1.125rem; height: 1.125rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="text" placeholder="Cari registrasi, no defect, mddr, deskripsi, part number..." style="padding-left: 2.1875rem; width: 100%;">
                </div>

                {{-- Operator Filter --}}
                <div style="display:flex; align-items:center; gap:0.35rem;">
                    <span style="font-size:0.78rem; font-weight:600; color:var(--cbm-text-muted);">AOC:</span>
                    <select wire:model.live="filterOperator" style="padding:0.4rem 0.65rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-card-bg); color:var(--cbm-text-main); font-size:0.82rem; cursor:pointer;">
                        <option value="">Semua Operator</option>
                        @foreach($operators as $op)
                            <option value="{{ $op }}">{{ $op }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort Order --}}
                <div style="display:flex; align-items:center; gap:0.35rem;">
                    <span style="font-size:0.78rem; font-weight:600; color:var(--cbm-text-muted);">Urutan:</span>
                    <select wire:model.live="sortBy" style="padding:0.4rem 0.65rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-card-bg); color:var(--cbm-text-main); font-size:0.82rem; cursor:pointer;">
                        <option value="due_date_asc">⚠️ Jatuh Tempo Terdekat (Urgent)</option>
                        <option value="latest">Input Terbaru</option>
                    </select>
                </div>

                @if($search || $filterOperator)
                    <button wire:click="$set('search', ''); $set('filterOperator', '')" class="mod-btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                        Reset Filter
                    </button>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <span class="mod-record-count">{{ number_format($logs->total()) }} records ditampilkan</span>
            </div>
        </div>
        
        <div class="mod-table-wrap">
            <table class="mod-table" style="white-space: nowrap;">
                <thead>
                    <tr>
                        <th>OPERATOR</th>
                        <th>A/C</th>
                        <th>STATUS A/C</th>
                        <th>DEFECT (NSRDI NO)</th>
                        <th>MDDR</th>
                        <th>STATUS</th>
                        <th>AREA</th>
                        <th>REPORT DATE</th>
                        <th>DUE DATE / OVERDUE</th>
                        <th>MONTH DUE</th>
                        <th>DEFECT DESCRIPTION</th>
                        <th>PART NUMBER</th>
                        <th>STATUS FINAL</th>
                        <th>REMARKS</th>
                        <th>CLOSED AT</th>
                        <th style="text-align: right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $isClosed = strtolower((string) $log->status) === 'closed';
                            $dueDateObj = $log->due_date ? \Carbon\Carbon::parse($log->due_date) : null;
                            $isPassedDue = $dueDateObj && ! $isClosed && $dueDateObj->lt(now()->startOfDay());
                        @endphp
                        <tr style="{{ $isPassedDue ? 'background: rgba(239, 68, 68, 0.04);' : '' }}">
                            <td>
                                <span style="font-weight: 600; color: var(--cbm-text-main);">{{ $log->operator ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="mod-ac-badge">{{ $log->aircraft_registration ?? '-' }}</div>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; color: var(--cbm-text-sub);">{{ $log->ac_status ?? '-' }}</span>
                            </td>
                            <td style="font-weight: 600; font-family: monospace; color: var(--cbm-accent-purple);">
                                {{ $log->nsrdi_number ?? '-' }}
                            </td>
                            <td style="font-family: monospace; color: var(--cbm-text-sub);">
                                {{ $log->mddr ?? '-' }}
                            </td>
                            <td>
                                @if($isClosed)
                                    <span class="mod-status-badge mod-status-success">
                                        Closed
                                    </span>
                                @elseif($isPassedDue)
                                    <span class="mod-status-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                        Overdue
                                    </span>
                                @else
                                    <span class="mod-status-badge mod-status-warning">
                                        {{ $log->status ?? 'Open' }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $log->area ?? '-' }}</td>
                            <td>{{ $log->report_date ? \Carbon\Carbon::parse($log->report_date)->format('d M Y') : '-' }}</td>
                            <td>
                                @if($dueDateObj)
                                    @if($isPassedDue)
                                        <div style="display:inline-flex; align-items:center; gap:0.35rem; padding: 0.2rem 0.5rem; background: rgba(239, 68, 68, 0.12); border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-weight: 600;">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:0.875rem;height:0.875rem;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                                            {{ $dueDateObj->format('d M Y') }}
                                        </div>
                                    @else
                                        <span style="font-weight: 500; color: var(--cbm-text-main);">{{ $dueDateObj->format('d M Y') }}</span>
                                    @endif
                                    @if(! $isClosed)
                                        @php $days = (int) now()->startOfDay()->diffInDays($dueDateObj->copy()->startOfDay(), false); @endphp
                                        <div class="mod-subtitle" style="font-size:.72rem; color:{{ $days < 0 ? '#ef4444' : ($days <= 30 ? '#f59e0b' : 'inherit') }};">
                                            {{ $days < 0 ? 'terlambat '.abs($days).' hari' : ($days === 0 ? 'jatuh tempo hari ini' : $days.' hari lagi') }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color: var(--cbm-text-muted);">-</span>
                                @endif
                            </td>
                            <td>{{ $log->month_due ?? '-' }}</td>
                            <td>
                                <x-text-popup :text="$log->description ?? '-'" />
                            </td>
                            <td style="font-family: monospace; font-size: 0.82rem;">
                                @php
                                    $rawPn = trim((string) ($log->part_number ?? ''));
                                    $normPn = strtoupper($rawPn);
                                    $imsItem = ($normPn !== '' && isset($imsItems[$normPn])) ? $imsItems[$normPn] : null;
                                @endphp
                                @if($rawPn !== '')
                                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                        <span style="font-weight:600; color:var(--cbm-text-main);">{{ $rawPn }}</span>
                                        @if($imsItem)
                                            @php
                                                $onHand = $imsItem->stocks->sum('qty_on_hand');
                                                $reserved = $imsItem->stocks->sum('qty_reserved');
                                                $avail = max(0, $onHand - $reserved);
                                            @endphp
                                            @if($avail > 0)
                                                <button type="button" wire:click="showImsDetail('{{ $normPn }}')" class="ims-stock-badge ready" title="Klik untuk cek detail stok & lokasi di IMS">
                                                    📦 IMS: {{ $avail }} Ready
                                                </button>
                                            @else
                                                <button type="button" wire:click="showImsDetail('{{ $normPn }}')" class="ims-stock-badge empty" title="Stok habis di IMS. Klik untuk detail / permintaan">
                                                    📦 IMS: Stok 0
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" wire:click="openQuickRegisterIms('{{ $rawPn }}', {{ $log->id }})" class="ims-stock-badge not-found" title="Part belum terdaftar di IMS. Klik untuk daftarkan">
                                                + Daftarkan ke IMS
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: var(--cbm-text-muted);">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->status_final)
                                    <span style="display:inline-block; padding: 0.15rem 0.45rem; background: var(--cbm-bg); border: 1px solid var(--cbm-border); border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                                        {{ $log->status_final }}
                                    </span>
                                @else
                                    <span style="color: var(--cbm-text-muted);">-</span>
                                @endif
                            </td>
                            <td>
                                <x-text-popup :text="$log->remarks ?? '-'" />
                            </td>
                            <td>
                                @if($log->closed_at)
                                    <span style="font-size: 0.8rem; color: #10b981;">
                                        {{ \Carbon\Carbon::parse($log->closed_at)->format($log->closed_via ? 'd M Y' : 'd M Y H:i') }}
                                    </span>
                                    @if($log->closed_via)
                                        <div class="mod-subtitle" style="font-size:.72rem;" title="Dicocokkan dengan nomor NSRDI di bank data">via {{ ['daily_report' => 'Daily Report', 'bank' => 'Bank Data', 'file' => 'File', 'manual' => 'Manual'][$log->closed_via] ?? $log->closed_via }}@if($log->bank_ref) · {{ $log->bank_ref }}@endif</div>
                                    @endif
                                @else
                                    <span style="color: var(--cbm-text-muted);">-</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button wire:click="openEditModal({{ $log->id }})" class="mod-btn-outline" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    Update Status
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16">
                                <div class="mod-empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center;">
                                    <svg style="width: 4rem; height: 4rem; margin-bottom: 1rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    <p style="color: #6b7280; font-size: 0.95rem;">Tidak ada data NSRDI Overdue pada tab {{ strtoupper($activeTab) }}</p>
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
                            <option value="Overdue">Overdue</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.4rem;">Status Final / Part Action</label>
                        <input type="text" wire:model="editStatusFinal" placeholder="Contoh: AVAILABLE, CHECKING BY CBM, Replaced, dll" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main);">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.4rem;">Remarks / Catatan</label>
                        <textarea wire:model="editRemarks" rows="3" placeholder="Catatan part, no order, atau keterangan tindak lanjut..." style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main);"></textarea>
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
    @php
        $an = $analysis;
        $viaLabel = ['daily_report' => 'Daily Report', 'bank' => 'Bank Data', 'file' => 'File', 'manual' => 'Manual'];
    @endphp
    <div class="cbm-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center; padding:1rem;">
        <div class="cbm-modal-content" style="background:var(--cbm-card-bg); width:100%; max-width:46rem; max-height:92vh; display:flex; flex-direction:column; border-radius:0.75rem; overflow:hidden; box-shadow:0 0.625rem 0.9375rem -3px rgba(0,0,0,0.1);">
            <div style="padding:1.1rem 1.5rem; border-bottom:1px solid var(--cbm-border); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-weight:600; font-size:1.1rem; color:var(--cbm-text-main);">Import NSRDI Overdue (Excel)</h3>
                <button type="button" wire:click="closeImportModal" aria-label="Tutup" style="background:transparent; border:none; color:var(--cbm-text-sub); cursor:pointer; font-size:1.4rem; line-height:1;">×</button>
            </div>

            <div style="padding:1.25rem 1.5rem; overflow-y:auto;">
                @error('file')
                    <div style="margin-bottom:1rem; padding:0.75rem; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:6px; color:#ef4444; font-size:0.875rem;">{{ $message }}</div>
                @enderror

                @if(! $importDone)
                    <label style="display:block; font-size:0.875rem; font-weight:500; color:var(--cbm-text-main); margin-bottom:0.5rem;">Pilih file mingguan NSRDI (Excel)</label>
                    <input type="file" wire:model="file" accept=".xlsx,.xls" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main);">
                    <div wire:loading wire:target="file" class="mod-subtitle" style="margin-top:.4rem;">Mengunggah…</div>
                    <div style="margin-top:.9rem; padding:.7rem .85rem; background:var(--cbm-bg); border:1px solid var(--cbm-border); border-radius:6px; font-size:.78rem; color:var(--cbm-text-sub); line-height:1.55;">
                        Sheet daftar defect (PROVIDER, A/C, DEFECT, MDDR, STATUS, AREA, REPORTED_DATE, DUE-DATE) dan sheet part (DOC NO., PART NUMBER, STATUS FINAL) dibaca otomatis.
                        Nomor <strong>MDDR</strong> atau <strong>DEFECT</strong> yang sudah <strong>Closed</strong> di Bank Data / Daily Report ditutup otomatis pada tanggal close di bank;
                        sisanya tetap <strong>Open</strong> dengan due date-nya.
                    </div>
                @endif

                @if($an)
                    <div style="margin-top:1rem; font-weight:600; color:var(--cbm-text-main);">{{ $importDone ? 'Hasil import' : 'Hasil analisis (belum disimpan)' }}</div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(8.5rem,1fr)); gap:.55rem; margin-top:.55rem;">
                        @foreach([
                            ['Dokumen unik', $an['unique'], '#60a5fa'],
                            ['Baru', $an['created'], '#34d399'],
                            ['Diperbarui', $an['updated'], '#94a3b8'],
                            ['Ditutup otomatis', count($an['closed_by_bank']) + $an['closed_by_file'], '#34d399'],
                            ['Masih open', $an['still_open'], '#f59e0b'],
                            ['Lewat due date', $an['past_due'], '#ef4444'],
                            ['Due ≤ 30 hari', $an['due_30'], '#f59e0b'],
                            ['Tidak ada di file', $an['missing_from_file'], '#94a3b8'],
                        ] as [$label, $value, $color])
                            <div style="padding:.6rem .7rem; border:1px solid var(--cbm-border); border-radius:8px; border-left:3px solid {{ $color }};">
                                <div style="font-size:.7rem; color:var(--cbm-text-muted);">{{ $label }}</div>
                                <div style="font-size:1.3rem; font-weight:700; color:var(--cbm-text-main);">{{ number_format($value) }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if($an['duplicates'])
                        <div class="mod-subtitle" style="margin-top:.5rem;">{{ $an['duplicates'] }} baris ganda (nomor defect sama) dilewati.</div>
                    @endif
                    @if($an['missing_from_file'])
                        <div class="mod-subtitle" style="margin-top:.35rem;">"Tidak ada di file": dokumen open di sistem yang tidak lagi tercantum di file ini (kemungkinan sudah ditutup planner). Status tidak diubah.</div>
                    @endif

                    @if(count($an['closed_by_bank']))
                        <div style="margin-top:1rem; font-weight:600; color:var(--cbm-text-main);">Ditutup karena nomornya Closed di bank data ({{ count($an['closed_by_bank']) }})</div>
                        <div style="margin-top:.4rem; border:1px solid var(--cbm-border); border-radius:8px; max-height:14rem; overflow:auto;">
                            <table class="mod-table" style="font-size:.8rem;">
                                <thead><tr><th>DEFECT</th><th>MDDR</th><th>A/C</th><th>COCOK DENGAN</th><th>CLOSE</th><th>SUMBER</th></tr></thead>
                                <tbody>
                                    @foreach(array_slice($an['closed_by_bank'], 0, 60) as $c)
                                        <tr>
                                            <td style="font-family:monospace;">{{ $c['doc'] }}</td>
                                            <td style="font-family:monospace;">{{ $c['mddr'] ?: '-' }}</td>
                                            <td>{{ $c['aircraft'] ?: '-' }}</td>
                                            <td style="font-family:monospace;">{{ $c['bank_ref'] }}</td>
                                            <td style="white-space:nowrap;">{{ $c['date'] ? \Carbon\Carbon::parse($c['date'])->format('d M Y') : '-' }}</td>
                                            <td>{{ $viaLabel[$c['source']] ?? $c['source'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($an['closed_by_bank']) > 60)<div class="mod-subtitle" style="margin-top:.3rem;">…dan {{ count($an['closed_by_bank']) - 60 }} lainnya.</div>@endif
                    @endif

                    @if(! empty($an['by_operator']))
                        <div style="margin-top:1rem; font-weight:600; color:var(--cbm-text-main);">Masih open per AOC</div>
                        <div style="margin-top:.4rem; display:flex; gap:.4rem; flex-wrap:wrap;">
                            @foreach($an['by_operator'] as $op => $n)
                                <span style="padding:.2rem .6rem; border-radius:999px; background:rgba(96,165,250,.14); font-size:.8rem;"><strong>{{ $op }}</strong> {{ number_format($n) }}</span>
                            @endforeach
                            @foreach($an['by_month'] as $mo => $n)
                                <span style="padding:.2rem .6rem; border-radius:999px; background:rgba(245,158,11,.14); font-size:.8rem;">due {{ $mo }}: <strong>{{ number_format($n) }}</strong></span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <div style="padding:1rem 1.5rem; border-top:1px solid var(--cbm-border); background:var(--cbm-bg); display:flex; justify-content:flex-end; gap:0.75rem; flex-wrap:wrap;">
                <button type="button" wire:click="closeImportModal" class="mod-btn-outline" style="border-radius:6px;">{{ $importDone ? 'Selesai' : 'Batal' }}</button>
                @if(! $importDone)
                    <button type="button" wire:click="analyzeFile" class="mod-btn-outline" style="border-radius:6px;" wire:loading.attr="disabled" wire:target="file, analyzeFile, importData" @disabled(! $file)>
                        <span wire:loading.remove wire:target="analyzeFile">Analisis dulu</span>
                        <span wire:loading wire:target="analyzeFile">Menganalisis…</span>
                    </button>
                    <button type="button" wire:click="importData" class="mod-btn-primary" style="border-radius:6px;" wire:loading.attr="disabled" wire:target="file, analyzeFile, importData" @disabled(! $file)>
                        <span wire:loading.remove wire:target="importData">{{ $an ? 'Terapkan import' : 'Import langsung' }}</span>
                        <span wire:loading wire:target="importData">Memproses…</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endif
    {{-- Modal Detail Stok IMS --}}
    @if($isImsModalOpen && $selectedImsItem)
    <div class="cbm-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; display:flex; align-items:center; justify-content:center; padding:1rem;" wire:keydown.escape="closeImsModal">
        <div class="cbm-modal-content" style="background:var(--cbm-card-bg); width:100%; max-width:36rem; border-radius:0.875rem; overflow:hidden; box-shadow:0 1.25rem 1.875rem -0.5rem rgba(0,0,0,0.3); border:1px solid var(--cbm-card-border);">
            <div style="padding:1.15rem 1.5rem; border-bottom:1px solid var(--cbm-card-border); display:flex; justify-content:space-between; align-items:center; background:rgba(16,185,129,0.06);">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="font-size:1.25rem;">📦</span>
                    <div>
                        <h3 style="margin:0; font-weight:700; font-size:1.05rem; color:var(--cbm-text);">Detail Stok IMS (Inventory)</h3>
                        <div style="font-size:0.75rem; color:var(--cbm-text-muted);">P/N: <strong style="font-family:monospace; color:var(--cbm-text-main);">{{ $selectedImsItem->part_number }}</strong> &bull; {{ $selectedImsItem->name }}</div>
                    </div>
                </div>
                <button type="button" wire:click="closeImsModal" style="background:transparent; border:none; color:var(--cbm-text-muted); cursor:pointer; font-size:1.25rem; line-height:1;" aria-label="Tutup">&times;</button>
            </div>

            <div style="padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:1rem;">
                {{-- Item Overview Badges --}}
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; font-size:0.75rem;">
                    <span style="background:var(--cbm-bg); border:1px solid var(--cbm-border); padding:0.2rem 0.55rem; border-radius:0.35rem;">Kategori: <strong>{{ $selectedImsItem->category?->name ?? '-' }}</strong></span>
                    <span style="background:var(--cbm-bg); border:1px solid var(--cbm-border); padding:0.2rem 0.55rem; border-radius:0.35rem;">Satuan: <strong>{{ $selectedImsItem->unit?->name ?? 'PCS' }}</strong></span>
                    @if($selectedImsItem->location)
                        <span style="background:var(--cbm-bg); border:1px solid var(--cbm-border); padding:0.2rem 0.55rem; border-radius:0.35rem;">Gudang Utama: <strong>{{ $selectedImsItem->location->name }}</strong></span>
                    @endif
                    <span style="background:var(--cbm-bg); border:1px solid var(--cbm-border); padding:0.2rem 0.55rem; border-radius:0.35rem;">Tipe: <strong>{{ $selectedImsItem->is_rotable ? 'Rotable' : 'Expendable / Consumable' }}</strong></span>
                </div>

                {{-- Deskripsi Part --}}
                @if($selectedImsItem->description)
                <div style="font-size:0.8rem; color:var(--cbm-text-sub); line-height:1.4; background:var(--cbm-bg); padding:0.6rem 0.8rem; border-radius:0.4rem; border:1px solid var(--cbm-border);">
                    {{ $selectedImsItem->description }}
                </div>
                @endif

                {{-- Stocks Table per Location --}}
                <div>
                    <div style="font-size:0.75rem; font-weight:700; color:var(--cbm-text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.4rem;">
                        Ketersediaan Stok per Lokasi / Gudang
                    </div>
                    <div style="border:1px solid var(--cbm-border); border-radius:0.5rem; overflow:hidden;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.8rem; text-align:left;">
                            <thead style="background:var(--cbm-bg); border-bottom:1px solid var(--cbm-border); color:var(--cbm-text-muted); font-size:0.72rem; text-transform:uppercase;">
                                <tr>
                                    <th style="padding:0.5rem 0.75rem;">Lokasi / Gudang</th>
                                    <th style="padding:0.5rem 0.75rem; text-align:center;">On Hand</th>
                                    <th style="padding:0.5rem 0.75rem; text-align:center;">Reserved</th>
                                    <th style="padding:0.5rem 0.75rem; text-align:center;">Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedImsItem->stocks as $stock)
                                    @php
                                        $stockAvail = max(0, $stock->qty_on_hand - $stock->qty_reserved);
                                    @endphp
                                    <tr style="border-bottom:1px solid var(--cbm-border);">
                                        <td style="padding:0.55rem 0.75rem; font-weight:600; color:var(--cbm-text-main);">
                                            {{ $stock->location?->name ?? 'Gudang Utama' }}
                                            <span style="font-size:0.7rem; color:var(--cbm-text-muted); font-family:monospace;">({{ $stock->location?->code ?? '-' }})</span>
                                        </td>
                                        <td style="padding:0.55rem 0.75rem; text-align:center; font-family:monospace;">{{ $stock->qty_on_hand }}</td>
                                        <td style="padding:0.55rem 0.75rem; text-align:center; font-family:monospace; color:#ef4444;">{{ $stock->qty_reserved }}</td>
                                        <td style="padding:0.55rem 0.75rem; text-align:center; font-family:monospace; font-weight:700; color:{{ $stockAvail > 0 ? '#10b981' : '#f59e0b' }};">
                                            {{ $stockAvail }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="padding:1.5rem; text-align:center; color:var(--cbm-text-muted);">
                                            Belum ada pencatatan kuantitas stok di lokasi gudang.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($selectedImsItem->stocks->isNotEmpty())
                                @php
                                    $totOnHand = $selectedImsItem->stocks->sum('qty_on_hand');
                                    $totRes = $selectedImsItem->stocks->sum('qty_reserved');
                                    $totAvail = max(0, $totOnHand - $totRes);
                                @endphp
                                <tfoot style="background:var(--cbm-bg); font-weight:700; border-top:2px solid var(--cbm-border);">
                                    <tr>
                                        <td style="padding:0.55rem 0.75rem;">Total Keseluruhan</td>
                                        <td style="padding:0.55rem 0.75rem; text-align:center; font-family:monospace;">{{ $totOnHand }}</td>
                                        <td style="padding:0.55rem 0.75rem; text-align:center; font-family:monospace; color:#ef4444;">{{ $totRes }}</td>
                                        <td style="padding:0.55rem 0.75rem; text-align:center; font-family:monospace; color:{{ $totAvail > 0 ? '#10b981' : '#f59e0b' }}; font-size:0.95rem;">
                                            {{ $totAvail }}
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div style="padding:1rem 1.5rem; border-top:1px solid var(--cbm-card-border); background:var(--cbm-bg); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; gap:0.5rem;">
                    @if(\Illuminate\Support\Facades\Route::has('ims.requests'))
                        <a href="{{ route('ims.requests') }}" class="mod-btn-outline" style="font-size:0.78rem; display:inline-flex; align-items:center; gap:0.35rem;" target="_blank">
                            📝 Buat Permintaan Part
                        </a>
                    @endif
                </div>
                <button type="button" wire:click="closeImsModal" class="mod-btn-primary" style="padding:0.45rem 1.15rem; font-size:0.82rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Quick Register Part to IMS --}}
    @if($isQuickRegisterModalOpen)
    <div class="cbm-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; display:flex; align-items:center; justify-content:center; padding:1rem;" wire:keydown.escape="closeQuickRegisterModal">
        <div class="cbm-modal-content" style="background:var(--cbm-card-bg); width:100%; max-width:32rem; border-radius:0.875rem; overflow:hidden; box-shadow:0 1.25rem 1.875rem -0.5rem rgba(0,0,0,0.3); border:1px solid var(--cbm-card-border);">
            <form wire:submit.prevent="saveQuickRegisterIms">
                <div style="padding:1.15rem 1.5rem; border-bottom:1px solid var(--cbm-card-border); display:flex; justify-content:space-between; align-items:center; background:rgba(139,92,246,0.06);">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span style="font-size:1.25rem;">✨</span>
                        <div>
                            <h3 style="margin:0; font-weight:700; font-size:1.05rem; color:var(--cbm-text);">Daftarkan Part Baru ke IMS</h3>
                            <div style="font-size:0.75rem; color:var(--cbm-text-muted);">Integrasikan Part Number NSRDI Overdue ke katalog inventory</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeQuickRegisterModal" style="background:transparent; border:none; color:var(--cbm-text-muted); cursor:pointer; font-size:1.25rem; line-height:1;" aria-label="Tutup">&times;</button>
                </div>

                <div style="padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:0.9rem;">
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Part Number *</label>
                        <input type="text" wire:model="newPartNumber" class="mod-search-input" style="width:100%; font-family:monospace; font-weight:700; text-transform:uppercase;" placeholder="Contoh: 123-456-789">
                        @error('newPartNumber') <span style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:block;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Nama Part / Item Name *</label>
                        <input type="text" wire:model="newName" class="mod-search-input" style="width:100%;" placeholder="Contoh: Passenger Seat Cushion / Recline Lock">
                        @error('newName') <span style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:block;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Kategori IMS *</label>
                            <select wire:model="newCategoryId" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main); font-size:0.82rem;">
                                @foreach($imsCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('newCategoryId') <span style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:block;">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Satuan (Unit) *</label>
                            <select wire:model="newUnitId" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main); font-size:0.82rem;">
                                @foreach($imsUnits as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('newUnitId') <span style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:block;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:0.75rem;">
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Lokasi / Gudang Awal</label>
                            <select wire:model="newLocationId" style="display:block; width:100%; padding:0.5rem; border:1px solid var(--cbm-border); border-radius:6px; background:var(--cbm-bg); color:var(--cbm-text-main); font-size:0.82rem;">
                                <option value="">Tanpa Lokasi Awal</option>
                                @foreach($imsLocations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Stok Awal (Qty)</label>
                            <input type="number" wire:model="newInitialStock" min="0" class="mod-search-input" style="width:100%;" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--cbm-text-main); margin-bottom:0.35rem;">Keterangan / Defect Asal</label>
                        <textarea wire:model="newDescription" rows="2" class="mod-search-input" style="width:100%; font-size:0.8rem;" placeholder="Catatan part atau deskripsi defect..."></textarea>
                    </div>
                </div>

                <div style="padding:1rem 1.5rem; border-top:1px solid var(--cbm-card-border); background:var(--cbm-bg); display:flex; justify-content:flex-end; gap:0.75rem;">
                    <button type="button" wire:click="closeQuickRegisterModal" class="mod-btn-outline" style="border-radius:6px;">Batal</button>
                    <button type="submit" class="mod-btn-primary" style="border-radius:6px;" wire:loading.attr="disabled" wire:target="saveQuickRegisterIms">
                        <span wire:loading.remove wire:target="saveQuickRegisterIms">Daftarkan ke IMS</span>
                        <span wire:loading wire:target="saveQuickRegisterIms">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- IMS Badges Style --}}
    <style>
        .ims-stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }
        .ims-stock-badge.ready {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.35);
        }
        .ims-stock-badge.ready:hover {
            background: rgba(16, 185, 129, 0.25);
            transform: translateY(-1px);
        }
        .ims-stock-badge.empty {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
            border-color: rgba(245, 158, 11, 0.35);
        }
        .ims-stock-badge.empty:hover {
            background: rgba(245, 158, 11, 0.25);
            transform: translateY(-1px);
        }
        .ims-stock-badge.not-found {
            background: rgba(139, 92, 246, 0.08);
            color: #8b5cf6;
            border: 1px dashed rgba(139, 92, 246, 0.45);
        }
        .ims-stock-badge.not-found:hover {
            background: rgba(139, 92, 246, 0.2);
            border-style: solid;
            transform: translateY(-1px);
        }
    </style>
</div>
