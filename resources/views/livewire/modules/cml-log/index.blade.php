<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Cabin Maintenance Log</div>
            <div class="mod-title">CML &amp; Pivot Defect Kabin</div>
            <div class="mod-subtitle">Bank data catatan kerusakan kabin pesawat dan rekapitulasi statistik perbaikan</div>
        </div>
        <div class="mod-actions" style="display:flex;gap:.5rem;align-items:center;">
            <button type="button" wire:click="openWaModal" class="mod-btn-primary" style="background:#238636;border-color:#2ea043;display:inline-flex;align-items:center;gap:.4rem;box-shadow:0 3px 10px rgba(35,134,54,.25);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.1rem;height:1.1rem;">
                    <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 013-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 01-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 006.697 6.697c.103.038.251.009.352-.126l.97-1.293a1.875 1.875 0 011.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 01-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5z" clip-rule="evenodd" />
                </svg>
                📲 Kirim Laporan WA
            </button>
            <button wire:click="exportExcel" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="exportExcel">
                <span wire:loading.remove wire:target="exportExcel">
                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Import CML
            </button>
        </div>
    </div>

    {{-- Main Tabs --}}
    <div style="display:flex; gap:.4rem; margin-bottom:1.1rem; border-bottom:1px solid var(--cbm-card-border); padding-bottom:.65rem;">
        <button type="button" wire:click="setTab('logs')" class="ch-tab {{ $activeTab === 'logs' ? 'on' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:.95rem;height:.95rem;display:inline-block;vertical-align:-2px;margin-right:.3rem;"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
            Daftar Log CML (Data Mentah)
        </button>
        <button type="button" wire:click="setTab('pivot')" class="ch-tab {{ $activePillar ?? '' }} {{ $activeTab === 'pivot' ? 'on' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:.95rem;height:.95rem;display:inline-block;vertical-align:-2px;margin-right:.3rem;"><path d="M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z" /></svg>
            Pivot &amp; Rekapitulasi Defect Kabin
        </button>
    </div>

    {{-- TAB 1: LOG TABLE --}}
    @if($activeTab === 'logs')
        <div class="mod-card mod-card-accent-blue">
            {{-- Toolbar --}}
            <x-log-toolbar :logs="$logs" mode="range" :active="(bool) ($search || $filterStation)" />

            {{-- Table --}}
            <div class="mod-table-wrap">
                <table class="mod-table">
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
                                <td><strong>{{ $log->station ?? '' }}</strong></td>
                                <td>{{ $log->doc_type ?? '' }}</td>
                                <td>{{ $log->no_doc ?? '' }}</td>
                                <td><x-text-popup :text="$log->description ?? ''" title="Action Taken / Reason" /></td>
                                <td>
                                    @if(strtolower((string) $log->status) === 'closed')
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
                                        <div class="mod-empty-title">{{ ($search || $filterStation) ? 'Tidak ada data yang cocok' : 'Belum ada data CML' }}</div>
                                        <div class="mod-empty-sub">{{ ($search || $filterStation) ? 'Ubah kata kunci atau tanggal, atau reset filter.' : 'Klik "Import CML" untuk mengunggah log pertama.' }}</div>
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
    @endif

    {{-- TAB 2: PIVOT & SUMMARY --}}
    @if($activeTab === 'pivot' && !empty($pivotData))
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            {{-- KPI Summary Cards --}}
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
                <div class="mod-card" style="border-left:4px solid #3b82f6;">
                    <div style="font-size:.72rem; color:var(--cbm-text-muted); font-weight:800; text-transform:uppercase;">Total Defect CML</div>
                    <div style="font-size:1.75rem; font-weight:800; color:var(--cbm-text); margin:.25rem 0;">{{ number_format($pivotData['total']) }}</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Akumulasi temuan kerusakan kabin terdata</div>
                </div>
                <div class="mod-card" style="border-left:4px solid #10b981;">
                    <div style="font-size:.72rem; color:var(--cbm-text-muted); font-weight:800; text-transform:uppercase;">Defect Closed (Selesai)</div>
                    <div style="font-size:1.75rem; font-weight:800; color:#10b981; margin:.25rem 0;">{{ number_format($pivotData['closed']) }}</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Terselesaikan oleh teknisi di lapangan</div>
                </div>
                <div class="mod-card" style="border-left:4px solid #ef4444;">
                    <div style="font-size:.72rem; color:var(--cbm-text-muted); font-weight:800; text-transform:uppercase;">Defect Open (Aktif)</div>
                    <div style="font-size:1.75rem; font-weight:800; color:#ef4444; margin:.25rem 0;">{{ number_format($pivotData['open']) }}</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Memerlukan tindakan perbaikan segera</div>
                </div>
                <div class="mod-card" style="border-left:4px solid #8b5cf6;">
                    <div style="font-size:.72rem; color:var(--cbm-text-muted); font-weight:800; text-transform:uppercase;">Tingkat Penyelesaian (Rate)</div>
                    <div style="font-size:1.75rem; font-weight:800; color:#8b5cf6; margin:.25rem 0;">{{ $pivotData['rate'] }}%</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Persentase defect berhasil di-close</div>
                </div>
            </div>

            {{-- PIVOT 1: Station vs Operator Matrix --}}
            <div class="mod-card">
                <div style="margin-bottom:1rem;">
                    <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">
                        Pivot 1: Matriks Stasiun vs Operator Maskapai
                    </h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">
                        Sebaran temuan kerusakan kabin berdasarkan bandara operasional dan armada maskapai
                    </p>
                </div>

                <div class="mod-table-wrap">
                    <table class="mod-table" style="text-align:right;">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Stasiun</th>
                                @foreach($pivotData['operators'] as $op)
                                    <th style="text-align:right;">{{ $op }}</th>
                                @endforeach
                                <th style="text-align:right; font-weight:800; background:rgba(255,255,255,.04);">Total</th>
                                <th style="text-align:right;">Closed</th>
                                <th style="text-align:right;">Open</th>
                                <th style="text-align:right;">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pivotData['stationMatrix'] as $sta => $data)
                                <tr>
                                    <td style="text-align:left; font-weight:700;">{{ $sta }}</td>
                                    @foreach($pivotData['operators'] as $op)
                                        <td style="color:{{ ($data['operators'][$op] ?? 0) > 0 ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
                                            {{ $data['operators'][$op] ?: '-' }}
                                        </td>
                                    @endforeach
                                    <td style="font-weight:800; background:rgba(255,255,255,.04); color:#3b82f6;">
                                        {{ $data['total'] }}
                                    </td>
                                    <td style="color:#10b981; font-weight:700;">{{ $data['closed'] }}</td>
                                    <td style="color:{{ $data['open'] > 0 ? '#ef4444' : 'var(--cbm-text-muted)' }}; font-weight:700;">{{ $data['open'] }}</td>
                                    <td>
                                        @php $r = $data['total'] > 0 ? round(($data['closed'] / $data['total']) * 100) : 0; @endphp
                                        <span style="font-weight:700; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; background:{{ $r >= 80 ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)' }}; color:{{ $r >= 80 ? '#10b981' : '#ef4444' }};">
                                            {{ $r }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($pivotData['operators']) + 5 }}" style="text-align:center;">Tidak ada data matriks stasiun.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PIVOT 2: Defect Categories & Top Aircraft --}}
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:1rem;">
                {{-- Category Breakdown --}}
                <div class="mod-card">
                    <h3 style="font-size:1rem; font-weight:700; color:var(--cbm-text); margin:0 0 .85rem;">
                        Pivot 2: Kategori Sistem Kerusakan Kabin
                    </h3>
                    <div class="mod-table-wrap">
                        <table class="mod-table" style="text-align:right;">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Sistem / Kategori</th>
                                    <th style="text-align:right;">Total</th>
                                    <th style="text-align:right;">Closed</th>
                                    <th style="text-align:right;">Open</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pivotData['categories'] as $catName => $catStat)
                                    <tr>
                                        <td style="text-align:left; font-weight:600;">{{ $catName }}</td>
                                        <td style="font-weight:800; color:#3b82f6;">{{ $catStat['total'] }}</td>
                                        <td style="color:#10b981; font-weight:700;">{{ $catStat['closed'] }}</td>
                                        <td style="color:{{ $catStat['open'] > 0 ? '#ef4444' : 'var(--cbm-text-muted)' }}; font-weight:700;">{{ $catStat['open'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Aircraft affected --}}
                <div class="mod-card">
                    <h3 style="font-size:1rem; font-weight:700; color:var(--cbm-text); margin:0 0 .85rem;">
                        Registrasi Pesawat dengan Temuan CML Terbanyak
                    </h3>
                    <div class="mod-table-wrap">
                        <table class="mod-table" style="text-align:right;">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">A/C Registration</th>
                                    <th style="text-align:right;">Total Defect</th>
                                    <th style="text-align:right;">Closed</th>
                                    <th style="text-align:right;">Open</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pivotData['topAircraft'] as $ac)
                                    <tr>
                                        <td style="text-align:left; font-weight:800; color:var(--cbm-text);">{{ $ac['reg'] }}</td>
                                        <td style="font-weight:800; color:#3b82f6;">{{ $ac['total'] }}</td>
                                        <td style="color:#10b981; font-weight:700;">{{ $ac['closed'] }}</td>
                                        <td style="color:{{ $ac['open'] > 0 ? '#ef4444' : 'var(--cbm-text-muted)' }}; font-weight:700;">{{ $ac['open'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Import modal --}}
    @if($isImportModalOpen)
        <div class="mod-modal-backdrop" wire:click.self="$set('isImportModalOpen', false)">
            <div class="mod-modal" style="max-width:32rem;">
                <div class="mod-modal-header">
                    <div class="mod-modal-title">Import Data CML</div>
                    <button type="button" class="mod-modal-close" wire:click="$set('isImportModalOpen', false)">&times;</button>
                </div>
                <div class="mod-modal-body">
                    <p style="font-size:.85rem;color:var(--cbm-text-muted);margin-bottom:1rem;">
                        Unggah file Excel (<code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code>) yang berisi log Cabin Maintenance Log.
                    </p>
                    <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" style="margin-bottom:1rem;">
                    @error('file') <span style="color:#ef4444;font-size:.8rem;display:block;margin-bottom:1rem;">{{ $message }}</span> @enderror
                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                        <button type="button" class="mod-btn-outline" wire:click="$set('isImportModalOpen', false)">Batal</button>
                        <button type="button" class="mod-btn-primary" wire:click="importCml" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="importCml">Import Sekarang</span>
                            <span wire:loading wire:target="importCml">Mengimpor...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
