<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Aircraft Painting Data Bank</div>
            <div class="mod-title">Daily Report - Aircraft Painting</div>
            <div class="mod-subtitle">Pusat penampungan dan arsip laporan harian pengecatan pesawat (NSRDI Painting).</div>
        </div>
        <div class="mod-actions" style="display:flex;gap:.5rem;align-items:center;">
            <button type="button" wire:click="openWaModal" class="mod-btn-primary" style="background:#238636;border-color:#2ea043;display:inline-flex;align-items:center;gap:.4rem;box-shadow:0 3px 10px rgba(35,134,54,.25);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.1rem;height:1.1rem;">
                    <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 013-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 01-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 006.697 6.697c.103.038.251.009.352-.126l.97-1.293a1.875 1.875 0 011.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 01-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5z" clip-rule="evenodd" />
                </svg>
                📲 Kirim Laporan WA
            </button>
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
        </div>
    </div>

    {{-- Main Card --}}
    <div class="mod-card mod-card-accent-purple">
        {{-- Tabs --}}
        <div class="cbm-tabs">
            <button type="button" wire:click="setTab('dja-painting')" class="cbm-tab {{ $activeTab === 'dja-painting' ? 'active' : '' }}">DJA Painting (Planned)</button>
            <button type="button" wire:click="setTab('unplanned-painting')" class="cbm-tab {{ $activeTab === 'unplanned-painting' ? 'active' : '' }}">Unplanned Painting</button>
            <button type="button" wire:click="setTab('summary')" class="cbm-tab {{ $activeTab === 'summary' ? 'active' : '' }}">Summary Pivot</button>
        </div>

        <div class="mod-hint" style="margin:1rem 1.25rem 0;background:var(--cbm-nav-hover);color:var(--cbm-text-muted);border-color:var(--cbm-card-border);">
            Arsip menampilkan log pengecatan <strong>sebelum {{ \Carbon\Carbon::parse($cutoffDate)->locale('id')->isoFormat('D MMMM YYYY') }}</strong>; pekerjaan hari aktif berjalan di modul NSRDI Aircraft Painting.
        </div>

        @if($heldCount > 0)
            <div class="mod-hint mod-hint-warn" style="margin:1rem 1.25rem 0;">
                <strong>{{ $heldCount }} log pengecatan belum masuk arsip</strong>: sudah lewat cutoff tetapi masih Open tanpa code reason + remarks. Lengkapi di modul NSRDI Aircraft Painting agar otomatis masuk ke sini.
            </div>
        @endif

        {{-- Toolbar --}}
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari registrasi, nomor NSRDI, deskripsi..." aria-label="Cari">
                    @if($search)
                        <button type="button" wire:click="clearSearch" class="mod-clear-btn" aria-label="Hapus pencarian">&times;</button>
                    @endif
                </div>

                <label class="mod-field mod-field-labelled">
                    <span>Tanggal</span>
                    <input wire:model.live="dateFilter" type="date" class="mod-search-input mod-input-plain">
                </label>
                @if($dateFilter)
                    <button type="button" wire:click="$set('dateFilter', '')" class="mod-btn-outline" style="padding:.35rem .65rem; font-size:.78rem;">Reset Tanggal</button>
                @endif
                <span wire:loading wire:target="search, dateFilter, perPage, setTab" class="cbm-spinner" aria-label="Memuat"></span>
            </div>

            @if($activeTab !== 'summary')
                <div class="mod-meta">
                    <span class="mod-record-count">
                        {{ $logs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $logs->total() : count($logs) }} data
                    </span>
                    <label class="mod-perpage">
                        <span>Per halaman</span>
                        <select wire:model.live="perPage" class="mod-search-input mod-input-plain">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </label>
                </div>
            @endif
        </div>

        @if($activeTab === 'summary')
            <div style="padding: 1.25rem;">
                {{-- KPI Cards --}}
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1.25rem;">
                        <div style="font-size:.8rem; color:var(--cbm-text-muted); font-weight:600; text-transform:uppercase;">Total Deploy Painting</div>
                        <div style="font-size:1.75rem; font-weight:800; color:var(--cbm-text); margin-top:.35rem;">{{ number_format($summaryData['total'] ?? 0) }}</div>
                        <div style="font-size:.75rem; color:var(--cbm-text-muted); margin-top:.25rem;">Akumulasi pekerjaan arsip</div>
                    </div>
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1.25rem;">
                        <div style="font-size:.8rem; color:var(--cbm-text-muted); font-weight:600; text-transform:uppercase;">Closed</div>
                        <div style="font-size:1.75rem; font-weight:800; color:#34d399; margin-top:.35rem;">{{ number_format($summaryData['closed'] ?? 0) }}</div>
                        <div style="font-size:.75rem; color:#34d399; margin-top:.25rem;">Selesai dikerjakan</div>
                    </div>
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1.25rem;">
                        <div style="font-size:.8rem; color:var(--cbm-text-muted); font-weight:600; text-transform:uppercase;">Open / Hold</div>
                        <div style="font-size:1.75rem; font-weight:800; color:{{ ($summaryData['open'] ?? 0) > 0 ? '#f87171' : 'var(--cbm-text)' }}; margin-top:.35rem;">{{ number_format($summaryData['open'] ?? 0) }}</div>
                        <div style="font-size:.75rem; color:var(--cbm-text-muted); margin-top:.25rem;">Masih tertunda</div>
                    </div>
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1.25rem;">
                        <div style="font-size:.8rem; color:var(--cbm-text-muted); font-weight:600; text-transform:uppercase;">Achievement Rate</div>
                        <div style="font-size:1.75rem; font-weight:800; color:#a78bfa; margin-top:.35rem;">{{ $summaryData['rate'] ?? 0 }}%</div>
                        <div style="font-size:.75rem; color:#a78bfa; margin-top:.25rem;">Planned: {{ $summaryData['planned'] ?? 0 }} | Unplan: {{ $summaryData['unplanned'] ?? 0 }}</div>
                    </div>
                </div>

                {{-- Matrix Station vs Job Type --}}
                @if(!empty($summaryData['stationMatrix']))
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1rem; margin-bottom:1.5rem;">
                        <div style="font-weight:700; font-size:.95rem; color:var(--cbm-text); margin-bottom:.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span>Matriks Stasiun vs Jenis Pekerjaan Pengecatan (PPO / Livery / Placard / Touch Up)</span>
                            <span style="font-size:.75rem; color:var(--cbm-text-muted);">{{ count($summaryData['stationMatrix']) }} stasiun aktif</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="mod-table" style="font-size:.825rem; white-space:nowrap; text-align:right;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;">STASIUN</th>
                                        @foreach($summaryData['jobTypes'] as $jt)
                                            <th style="text-align:right;">{{ $jt }}</th>
                                        @endforeach
                                        <th style="text-align:right; font-weight:800; background:rgba(255,255,255,.04);">TOTAL</th>
                                        <th style="text-align:right;">CLOSED</th>
                                        <th style="text-align:right;">OPEN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summaryData['stationMatrix'] as $sm)
                                        <tr>
                                            <td style="text-align:left; font-weight:700;">{{ $sm['station'] }}</td>
                                            @foreach($summaryData['jobTypes'] as $jt)
                                                <td style="color:{{ $sm['types'][$jt] > 0 ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
                                                    {{ $sm['types'][$jt] ?: '-' }}
                                                </td>
                                            @endforeach
                                            <td style="font-weight:800; background:rgba(255,255,255,.04); color:#c084fc;">{{ $sm['total'] }}</td>
                                            <td style="color:#34d399; font-weight:700;">{{ $sm['closed'] }}</td>
                                            <td style="color:{{ $sm['open'] > 0 ? '#f87171' : 'var(--cbm-text-muted)' }}; font-weight:700;">{{ $sm['open'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Tables: Breakdown Station & AOC --}}
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
                    {{-- By Station --}}
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1rem;">
                        <div style="font-weight:700; font-size:.9rem; color:var(--cbm-text); margin-bottom:.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span>Rekapitulasi per Stasiun</span>
                            <span style="font-size:.75rem; color:var(--cbm-text-muted);">{{ count($summaryData['byStation'] ?? []) }} stasiun</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="mod-table" style="font-size:.825rem; white-space:nowrap;">
                                <thead>
                                    <tr>
                                        <th>STASIUN</th>
                                        <th style="text-align:right;">TOTAL</th>
                                        <th style="text-align:right;">CLOSED</th>
                                        <th style="text-align:right;">OPEN</th>
                                        <th style="text-align:right;">RATE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summaryData['byStation'] ?? [] as $st)
                                        <tr>
                                            <td style="font-weight:700;">{{ $st['station'] }}</td>
                                            <td style="text-align:right;">{{ $st['total'] }}</td>
                                            <td style="text-align:right; color:#34d399; font-weight:600;">{{ $st['closed'] }}</td>
                                            <td style="text-align:right; color:{{ $st['open'] > 0 ? '#f87171' : 'inherit' }}; font-weight:600;">{{ $st['open'] }}</td>
                                            <td style="text-align:right; font-weight:700; color:#a78bfa;">{{ $st['rate'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" style="text-align:center; color:var(--cbm-text-muted);">Belum ada data stasiun</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- By AOC --}}
                    <div style="background:var(--cbm-nav-hover); border:1px solid var(--cbm-card-border); border-radius:.75rem; padding:1rem;">
                        <div style="font-weight:700; font-size:.9rem; color:var(--cbm-text); margin-bottom:.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span>Rekapitulasi per Maskapai / AOC</span>
                            <span style="font-size:.75rem; color:var(--cbm-text-muted);">{{ count($summaryData['byAoc'] ?? []) }} maskapai</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="mod-table" style="font-size:.825rem; white-space:nowrap;">
                                <thead>
                                    <tr>
                                        <th>AOC / MASKAPAI</th>
                                        <th style="text-align:right;">TOTAL</th>
                                        <th style="text-align:right;">CLOSED</th>
                                        <th style="text-align:right;">OPEN</th>
                                        <th style="text-align:right;">RATE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summaryData['byAoc'] ?? [] as $aoc)
                                        <tr>
                                            <td style="font-weight:700;">{{ $aoc['aoc'] }}</td>
                                            <td style="text-align:right;">{{ $aoc['total'] }}</td>
                                            <td style="text-align:right; color:#34d399; font-weight:600;">{{ $aoc['closed'] }}</td>
                                            <td style="text-align:right; color:{{ $aoc['open'] > 0 ? '#f87171' : 'inherit' }}; font-weight:600;">{{ $aoc['open'] }}</td>
                                            <td style="text-align:right; font-weight:700; color:#a78bfa;">{{ $aoc['rate'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" style="text-align:center; color:var(--cbm-text-muted);">Belum ada data maskapai</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Data Table --}}
            <div class="mod-table-wrap">
                <table class="mod-table" style="white-space: nowrap;">
                    <thead>
                        <tr>
                            <th>PLAN DATE</th>
                            <th>A/C REG</th>
                            <th>NSRDI NUMBER</th>
                            <th>FINDING DESCRIPTION</th>
                            <th>CATEGORY</th>
                            <th>PLAN STA</th>
                            <th>ACT STA</th>
                            <th>AOC</th>
                            <th>STATUS</th>
                            <th>CODE REASON</th>
                            <th>REASON OPEN / REMARKS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->plan_date ? $log->plan_date->format('Y-m-d') : ($log->report_date ? $log->report_date->format('Y-m-d') : '-') }}</td>
                                <td style="font-weight:700;">{{ $log->aircraft_registration }}</td>
                                <td style="font-weight:600; color:var(--cbm-text-accent);">{{ $log->nsrdi_number ?: '-' }}</td>
                                <td style="max-width:320px; overflow:hidden; text-overflow:ellipsis;" title="{{ $log->description }}">{{ $log->description }}</td>
                                <td>
                                    <span style="display:inline-block; padding:.15rem .45rem; border-radius:4px; font-size:.7rem; font-weight:700; background:rgba(168,85,247,0.15); color:#c084fc; border:1px solid rgba(168,85,247,0.3);">
                                        {{ $log->category }}
                                    </span>
                                </td>
                                <td>{{ $log->plan_station ?: '-' }}</td>
                                <td>{{ $log->act_station ?: '-' }}</td>
                                <td>{{ $log->aoc ?: '-' }}</td>
                                <td>
                                    @if(strtolower($log->status) === 'closed')
                                        <span class="mod-badge mod-badge-green">Closed</span>
                                    @else
                                        <span class="mod-badge mod-badge-red">Open</span>
                                    @endif
                                </td>
                                <td>{{ $log->hold_reason_category ?: ($log->code_open ?: '-') }}</td>
                                <td style="max-width:240px; overflow:hidden; text-overflow:ellipsis;" title="{{ $log->hold_remarks ?: $log->reason_open }}">{{ $log->hold_remarks ?: ($log->reason_open ?: '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="mod-empty">
                                        <div class="mod-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                        </div>
                                        <div class="mod-empty-title">Belum ada arsip log pengecatan</div>
                                        <div class="mod-empty-sub">Log pengecatan yang sudah selesai atau memiliki reason akan otomatis masuk ke sini setelah melewati cutoff operasional.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
                <div style="padding:1rem 1.25rem; border-top:1px solid var(--cbm-card-border);">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
