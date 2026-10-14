<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">Aircraft Interior &amp; Exterior Cleaning</span>
            <h1 class="mod-title">AIEC Report &amp; Capacity Hub</h1>
            <p class="mod-subtitle">Monitoring log aktivitas harian, matriks kapabilitas stasiun, dan rekapitulasi pivot 2026</p>
        </div>
        <div class="mod-actions" style="display:flex;gap:.5rem;align-items:center;">
            <button type="button" wire:click="openWaModal" class="mod-btn-primary" style="background:#238636;border-color:#2ea043;display:inline-flex;align-items:center;gap:.4rem;box-shadow:0 3px 10px rgba(35,134,54,.25);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.1rem;height:1.1rem;">
                    <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 013-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 01-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 006.697 6.697c.103.038.251.009.352-.126l.97-1.293a1.875 1.875 0 011.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 01-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5z" clip-rule="evenodd" />
                </svg>
                📲 Kirim Laporan WA
            </button>
            <button type="button" wire:click="$set('isImportModalOpen', true)" class="mod-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.1rem;height:1.1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Import Data AIEC
            </button>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="mod-btn-primary">
                <span wire:loading.remove wire:target="exportExcel" style="display:flex;align-items:center;gap:.4rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.1rem;height:1.1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export Excel (+Pivot)
                </span>
                <span wire:loading wire:target="exportExcel">
                    Mengekspor...
                </span>
            </button>
        </div>
    </div>

    {{-- Main Tab Navigation --}}
    <div style="display:flex; gap:.5rem; margin-bottom:1.25rem; flex-wrap:wrap; border-bottom:1px solid var(--cbm-card-border); padding-bottom:.75rem;">
        <button type="button" wire:click="setMainTab('logs')" class="ch-tab {{ $mainTab === 'logs' ? 'on' : '' }}" style="display:flex;align-items:center;gap:.4rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
            Log Aktivitas Pembersihan
        </button>
        <button type="button" wire:click="setMainTab('capability')" class="ch-tab {{ $mainTab === 'capability' ? 'on' : '' }}" style="display:flex;align-items:center;gap:.4rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path d="M10 2a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 2zM10 15a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 15zM10 7a3 3 0 100 6 3 3 0 000-6z" /></svg>
            Kapasitas &amp; Kapabilitas Stasiun (DCIE)
        </button>
        <button type="button" wire:click="setMainTab('pivots')" class="ch-tab {{ $mainTab === 'pivots' ? 'on' : '' }}" style="display:flex;align-items:center;gap:.4rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path d="M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z" /></svg>
            Pivot &amp; Rekapitulasi Matriks
        </button>
        <button type="button" wire:click="setMainTab('overdue')" class="ch-tab {{ $mainTab === 'overdue' ? 'on' : '' }}" style="display:flex;align-items:center;gap:.4rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
            Monitoring Overdue Batik OD (9M-)
        </button>
    </div>

    {{-- TAB 1: LOG AKTIVITAS PEMBERSIHAN --}}
    @if($mainTab === 'logs')
        <div class="mod-card mod-card-accent-blue">
            {{-- Cleaning Type Filter Pills --}}
            <div style="display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.5rem; margin-bottom:.9rem;">
                <button type="button" wire:click="setCleaningType('')" class="ch-tab {{ $cleaningType === '' ? 'on' : '' }}">Semua Jenis (All)</button>
                <button type="button" wire:click="setCleaningType('Transit')" class="ch-tab {{ $cleaningType === 'Transit' ? 'on' : '' }}">Transit</button>
                <button type="button" wire:click="setCleaningType('General')" class="ch-tab {{ $cleaningType === 'General' ? 'on' : '' }}">General</button>
                <button type="button" wire:click="setCleaningType('DCI')" class="ch-tab {{ $cleaningType === 'DCI' ? 'on' : '' }}">Deep Interior (DCI)</button>
                <button type="button" wire:click="setCleaningType('DCE')" class="ch-tab {{ $cleaningType === 'DCE' ? 'on' : '' }}">Deep Exterior (DCE)</button>
                <button type="button" wire:click="setCleaningType('GCI')" class="ch-tab {{ $cleaningType === 'GCI' ? 'on' : '' }}">GCI</button>
                <button type="button" wire:click="setCleaningType('GCE')" class="ch-tab {{ $cleaningType === 'GCE' ? 'on' : '' }}">GCE</button>
                <button type="button" wire:click="setCleaningType('LGT')" class="ch-tab {{ $cleaningType === 'LGT' ? 'on' : '' }}">LGT</button>
            </div>

            {{-- Multi-Criteria Filters Toolbar --}}
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:.6rem; margin-bottom:1rem; padding:.75rem; background:var(--cbm-input-bg); border-radius:.5rem; border:1px solid var(--cbm-input-border);">
                <div>
                    <label style="font-size:.7rem; color:var(--cbm-text-muted); display:block; margin-bottom:.2rem;">Pencarian Teks</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Reg, Doc, Station..." style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);" />
                </div>
                <div>
                    <label style="font-size:.7rem; color:var(--cbm-text-muted); display:block; margin-bottom:.2rem;">Periode Bulan</label>
                    <select wire:model.live="monthFilter" style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                        <option value="">Semua Bulan</option>
                        <option value="2026-10">Oktober 2026 (Berjalan)</option>
                        <option value="2026-09">September 2026</option>
                        <option value="2026-08">Agustus 2026</option>
                        <option value="2026-07">Juli 2026</option>
                        <option value="2026-06">Juni 2026</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:.7rem; color:var(--cbm-text-muted); display:block; margin-bottom:.2rem;">Stasiun / Bandara</label>
                    <select wire:model.live="stationFilter" style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                        <option value="">Semua Stasiun</option>
                        @foreach($availableStations as $sta)
                            <option value="{{ $sta }}">{{ $sta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.7rem; color:var(--cbm-text-muted); display:block; margin-bottom:.2rem;">Shift Kerja</label>
                    <select wire:model.live="shiftFilter" style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                        <option value="">Semua Shift</option>
                        <option value="Pagi">Pagi</option>
                        <option value="Siang">Siang (Normal)</option>
                        <option value="Malam">Malam</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:.7rem; color:var(--cbm-text-muted); display:block; margin-bottom:.2rem;">Operator Maskapai</label>
                    <select wire:model.live="operatorFilter" style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                        <option value="">Semua Operator</option>
                        @foreach($availableOperators as $op)
                            <option value="{{ $op }}">{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.7rem; color:var(--cbm-text-muted); display:block; margin-bottom:.2rem;">Status Pekerjaan</label>
                    <select wire:model.live="statusFilter" style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:var(--cbm-card-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                        <option value="">Semua Status</option>
                        <option value="Closed">Closed (Selesai)</option>
                        <option value="Open">Open (Aktif)</option>
                    </select>
                </div>
                <div style="display:flex; align-items:flex-end;">
                    <button type="button" wire:click="resetFilters" style="width:100%; font-size:.8rem; padding:.35rem .6rem; border-radius:.35rem; background:transparent; border:1px solid var(--cbm-card-border); color:var(--cbm-text-muted); cursor:pointer;">
                        Reset Filter
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>STA</th>
                            <th>A/C Reg &amp; Tipe</th>
                            <th>Operator</th>
                            <th>Jenis Cleaning</th>
                            <th>A/C Status</th>
                            <th>Shift</th>
                            <th>No. Dokumen / Ref</th>
                            <th>Jam Kerja</th>
                            <th>MP / MHRS</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td style="white-space:nowrap; font-weight:600;">
                                    {{ \Carbon\Carbon::parse($log->date)->format('d M Y') }}
                                </td>
                                <td>
                                    <span style="font-weight:700; padding:.15rem .4rem; background:rgba(59,130,246,.12); color:#3b82f6; border-radius:.25rem; font-size:.75rem;">
                                        {{ $log->station ?: '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="mod-aircraft-name" style="font-weight:700;">{{ $log->aircraft_registration }}</div>
                                    @if($log->aircraft_type)
                                        <div style="font-size:.7rem; color:var(--cbm-text-muted);">{{ $log->aircraft_type }}</div>
                                    @endif
                                </td>
                                <td style="font-size:.8rem;">{{ $log->operator ?: '-' }}</td>
                                <td>
                                    @php
                                        $typeBadgeStyle = match($log->type) {
                                            'DCI' => 'background:rgba(168,85,247,.15); color:#a855f7;',
                                            'DCE' => 'background:rgba(236,72,153,.15); color:#ec4899;',
                                            'Transit' => 'background:rgba(59,130,246,.15); color:#3b82f6;',
                                            'General' => 'background:rgba(16,185,129,.15); color:#10b981;',
                                            default => 'background:rgba(255,255,255,.08); color:inherit;'
                                        };
                                    @endphp
                                    <span style="display:inline-block; font-size:.75rem; font-weight:700; padding:.15rem .45rem; border-radius:.25rem; {{ $typeBadgeStyle }}">
                                        {{ $log->type }}
                                    </span>
                                </td>
                                <td style="font-size:.75rem; color:var(--cbm-text-muted);">
                                    {{ $log->ac_status ?: '-' }}
                                </td>
                                <td>
                                    @php
                                        $shiftStyle = match(ucfirst(strtolower($log->shift ?? ''))) {
                                            'Pagi' => 'color:#10b981;',
                                            'Siang' => 'color:#f59e0b;',
                                            'Malam' => 'color:#6366f1;',
                                            default => 'color:inherit;'
                                        };
                                    @endphp
                                    <span style="font-size:.75rem; font-weight:600; {{ $shiftStyle }}">
                                        {{ $log->shift ?: '-' }}
                                    </span>
                                </td>
                                <td style="font-size:.75rem; font-family:monospace;">{{ $log->ref_no ?: '-' }}</td>
                                <td style="font-size:.75rem; white-space:nowrap;">
                                    @if($log->start_at && $log->end_at)
                                        {{ \Carbon\Carbon::parse($log->start_at)->format('H:i') }} - {{ \Carbon\Carbon::parse($log->end_at)->format('H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="font-size:.8rem; white-space:nowrap;">
                                    <span title="Total MP">{{ $log->man_power ?? 0 }} MP</span> /
                                    <span style="font-weight:600;" title="Actual MHRS">{{ number_format($log->man_hour ?? 0, 1) }}h</span>
                                </td>
                                <td>
                                    @if(strtolower($log->status) === 'closed')
                                        <span class="mod-badge-closed"><span class="mod-badge-dot"></span>Closed</span>
                                    @else
                                        <span class="mod-badge-open"><span class="mod-badge-dot"></span>Open</span>
                                    @endif
                                </td>
                                <td><x-text-popup :text="$log->remarks ?: '-'" title="Remarks" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">
                                    <div class="mod-empty">
                                        <div class="mod-empty-title">Tidak ada data cleaning ditemukan</div>
                                        <div class="mod-empty-sub">Silakan gunakan tombol "Import Data AIEC" untuk memasukkan data dari AIEC Report 2026.xlsx.</div>
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

    {{-- TAB 2: KAPASITAS & KAPABILITAS STASIUN (DCIE) --}}
    @if($mainTab === 'capability')
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            {{-- KPI Summary Cards --}}
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
                <div class="mod-card" style="border-left:4px solid #3b82f6;">
                    <div style="font-size:.75rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:700;">Total Kapasitas Harian (Sheet24)</div>
                    <div style="font-size:1.75rem; font-weight:800; color:var(--cbm-text); margin:.35rem 0;">12 DCI + 9 DCE</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Akumulasi kapasitas per hari seluruh stasiun aktif</div>
                </div>
                <div class="mod-card" style="border-left:4px solid #10b981;">
                    <div style="font-size:.75rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:700;">Stasiun Kapabilitas Aktif</div>
                    <div style="font-size:1.75rem; font-weight:800; color:var(--cbm-text); margin:.35rem 0;">10 Stasiun</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">CGK, SUB, KNO, UPG, BPN, CBN, KOE, AMQ, MDC, DMK</div>
                </div>
                <div class="mod-card" style="border-left:4px solid #f59e0b;">
                    <div style="font-size:.75rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:700;">Stasiun Beban Maksimal (Penuh)</div>
                    <div style="font-size:1.75rem; font-weight:800; color:var(--cbm-text); margin:.35rem 0;">UPG, CBN, CGK</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Berjalan tepat di limit 100% kapasitas setiap hari</div>
                </div>
                <div class="mod-card" style="border-left:4px solid #ef4444;">
                    <div style="font-size:.75rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:700;">Pekerjaan di Stasiun Non-Kapabilitas</div>
                    <div style="font-size:1.75rem; font-weight:800; color:#ef4444; margin:.35rem 0;">78 Open Job</div>
                    <div style="font-size:.75rem; color:var(--cbm-text-muted);">Memerlukan rotasi RON segera ke hub utama</div>
                </div>
            </div>

            {{-- Non-Capability Warning Banner --}}
            @if($nonCapAlerts->isNotEmpty())
                <div class="mod-card" style="background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.25);">
                    <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.35rem;height:1.35rem;color:#ef4444;"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>
                        <h3 style="font-size:1rem; font-weight:700; color:#ef4444; margin:0;">Peringatan Operasional: Jadwal DCIE di Stasiun Tanpa Kapabilitas</h3>
                    </div>
                    <p style="font-size:.8rem; color:var(--cbm-text); margin-bottom:.75rem;">
                        Tercatat pekerjaan open DCIE dijadwalkan RON di bandara non-kapabilitas (misal BDJ, BTH, SRG, WGP, PLM, YIA, dll.). Pekerjaan ini tidak dapat diselesaikan di lokasi dan harus dipindahkan/dirotasi ke CGK, SUB, UPG, atau KNO.
                    </p>
                    <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                        @foreach($nonCapAlerts->take(12) as $alert)
                            <span style="font-size:.75rem; padding:.2rem .5rem; background:rgba(239,68,68,.15); color:#ef4444; border-radius:.25rem; font-weight:600;">
                                {{ $alert->station }}: {{ $alert->aircraft_registration }} ({{ $alert->type }})
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 12 Station Capability Matrix Table --}}
            <div class="mod-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
                    <div>
                        <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">Matriks Kapabilitas 12 Stasiun (DCI / DCE)</h3>
                        <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">Berdasarkan acuan terverifikasi Sheet24 &amp; realisasi lapangan AIEC</p>
                    </div>
                    <span style="font-size:.75rem; padding:.25rem .6rem; border-radius:999px; background:rgba(59,130,246,.12); color:#3b82f6; font-weight:700;">
                        Acuan Resmi: Sheet24 (Single Source of Truth)
                    </span>
                </div>

                <div class="mod-table-wrap">
                    <table class="mod-table">
                        <thead>
                            <tr>
                                <th>STA</th>
                                <th>Nama Bandara / Wilayah</th>
                                <th>Limit DCI</th>
                                <th>Realisasi DCI</th>
                                <th>Limit DCE</th>
                                <th>Realisasi DCE</th>
                                <th>General &amp; Transit</th>
                                <th>MP Rencana</th>
                                <th>Tipe Armada</th>
                                <th>Status Beban</th>
                                <th>Catatan Operasional</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($capabilityMetrics as $m)
                                <tr>
                                    <td>
                                        <span style="font-weight:800; font-size:.85rem; color:var(--cbm-text);">{{ $m['code'] }}</span>
                                    </td>
                                    <td style="font-size:.8rem; font-weight:600;">{{ $m['name'] }}</td>
                                    <td style="text-align:center; font-weight:700;">{{ $m['dci_cap'] ?: '-' }}</td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:700; color:#3b82f6;">{{ $m['dci_done'] }}</span>
                                        <div style="font-size:.7rem; color:var(--cbm-text-muted);">({{ $m['avg_dci'] }}/hari)</div>
                                    </td>
                                    <td style="text-align:center; font-weight:700;">{{ $m['dce_cap'] ?: '-' }}</td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:700; color:#ec4899;">{{ $m['dce_done'] }}</span>
                                        <div style="font-size:.7rem; color:var(--cbm-text-muted);">({{ $m['avg_dce'] }}/hari)</div>
                                    </td>
                                    <td style="font-size:.75rem; white-space:nowrap;">
                                        <span>Gen: {{ $m['general_done'] }}</span> | <span>Tra: {{ $m['transit_done'] }}</span>
                                    </td>
                                    <td style="text-align:center; font-size:.8rem;">
                                        {{ $m['mp_plan'] > 0 ? $m['mp_plan'].' MP' : '-' }}
                                    </td>
                                    <td style="font-size:.75rem; color:var(--cbm-text-muted);">{{ $m['ac_types'] }}</td>
                                    <td>
                                        @php
                                            $stClass = match($m['status_class']) {
                                                'red' => 'background:rgba(239,68,68,.15); color:#ef4444;',
                                                'amber' => 'background:rgba(245,158,11,.15); color:#f59e0b;',
                                                'green' => 'background:rgba(16,185,129,.15); color:#10b981;',
                                                default => 'background:rgba(255,255,255,.08); color:var(--cbm-text-muted);'
                                            };
                                        @endphp
                                        <span style="display:inline-block; font-size:.75rem; font-weight:700; padding:.15rem .45rem; border-radius:.25rem; {{ $stClass }}">
                                            {{ $m['status_label'] }}
                                        </span>
                                    </td>
                                    <td style="font-size:.75rem; color:var(--cbm-text-muted); max-width:260px;">
                                        {{ $m['notes'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 3: PIVOT & REKAPITULASI MATRIKS --}}
    @if($mainTab === 'pivots')
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            {{-- PIVOT 1: Station vs Jenis Cleaning --}}
            <div class="mod-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.85rem; flex-wrap:wrap; gap:.5rem;">
                    <div>
                        <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">Pivot 1: Matriks Stasiun vs Jenis Cleaning</h3>
                        <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">Distribusi aktivitas per bandara dan jenis pekerjaan (Transit, General, DCI, DCE, DBI, LGT)</p>
                    </div>
                </div>

                <div class="mod-table-wrap">
                    <table class="mod-table" style="text-align:right;">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Stasiun / Bandara</th>
                                @foreach($stationTypePivot['types'] as $t)
                                    <th style="text-align:right;">{{ $t }}</th>
                                @endforeach
                                <th style="text-align:right; font-weight:800; background:rgba(255,255,255,.04);">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stationTypePivot['matrix'] as $sta => $counts)
                                <tr>
                                    <td style="text-align:left; font-weight:700;">{{ $sta }}</td>
                                    @foreach($stationTypePivot['types'] as $t)
                                        <td style="color: {{ $counts[$t] > 0 ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
                                            {{ $counts[$t] ?: '-' }}
                                        </td>
                                    @endforeach
                                    <td style="font-weight:800; background:rgba(255,255,255,.04); color:#3b82f6;">
                                        {{ number_format($counts['_total']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;">Belum ada data aktivitas untuk pivot.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:800; border-top:2px solid var(--cbm-card-border); background:rgba(255,255,255,.05);">
                                <td style="text-align:left;">Total Sistem</td>
                                @foreach($stationTypePivot['types'] as $t)
                                    <td>{{ number_format($stationTypePivot['totals'][$t]) }}</td>
                                @endforeach
                                <td style="color:#10b981; font-size:.95rem;">
                                    {{ number_format($stationTypePivot['grand_total']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- PIVOT 2: Station vs Shift --}}
            <div class="mod-card">
                <div style="margin-bottom:.85rem;">
                    <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">Pivot 2: Matriks Stasiun vs Shift Kerja</h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">Rekapitulasi shift Pagi, Siang (termasuk 179 data pulih), dan Malam</p>
                </div>

                <div class="mod-table-wrap">
                    <table class="mod-table" style="text-align:right;">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Stasiun / Bandara</th>
                                @foreach($stationShiftPivot['shifts'] as $s)
                                    <th style="text-align:right;">{{ $s }}</th>
                                @endforeach
                                <th style="text-align:right; font-weight:800; background:rgba(255,255,255,.04);">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stationShiftPivot['matrix'] as $sta => $counts)
                                <tr>
                                    <td style="text-align:left; font-weight:700;">{{ $sta }}</td>
                                    @foreach($stationShiftPivot['shifts'] as $s)
                                        <td>{{ $counts[$s] ?: '-' }}</td>
                                    @endforeach
                                    <td style="font-weight:800; background:rgba(255,255,255,.04); color:#3b82f6;">
                                        {{ number_format($counts['_total']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;">Belum ada data shift untuk pivot.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:800; border-top:2px solid var(--cbm-card-border); background:rgba(255,255,255,.05);">
                                <td style="text-align:left;">Total Sistem</td>
                                @foreach($stationShiftPivot['shifts'] as $s)
                                    <td>{{ number_format($stationShiftPivot['totals'][$s]) }}</td>
                                @endforeach
                                <td style="color:#10b981; font-size:.95rem;">
                                    {{ number_format($stationShiftPivot['grand_total']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- PIVOT 3: Tren Bulanan Historis (Juni s/d Oktober 2026) --}}
            <div class="mod-card">
                <div style="margin-bottom:.85rem;">
                    <h3 style="font-size:1.1rem; font-weight:700; color:var(--cbm-text); margin:0;">Pivot 3: Tren Bulanan Historis (Juni - Oktober 2026)</h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">Perbandingan performa per bulan berdasarkan data sheet JUN 26, JUL 26, AUG 26, SEP, dan DATABASE</p>
                </div>

                <div class="mod-table-wrap">
                    <table class="mod-table" style="text-align:right;">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Periode Bulan</th>
                                <th style="text-align:right;">Hari Aktif</th>
                                <th style="text-align:right;">Total Aktivitas</th>
                                <th style="text-align:right;">Rata-rata / Hari</th>
                                <th style="text-align:right;">Transit</th>
                                <th style="text-align:right;">General</th>
                                <th style="text-align:right;">DCI + DCE</th>
                                <th style="text-align:right;">Total Manhours</th>
                                <th style="text-align:right;">Armada Aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlyTrends as $t)
                                <tr>
                                    <td style="text-align:left; font-weight:700;">{{ $t['label'] }}</td>
                                    <td>{{ $t['days'] }} hari</td>
                                    <td style="font-weight:700; color:#3b82f6;">{{ number_format($t['total_jobs']) }}</td>
                                    <td>{{ $t['avg_daily'] }}/hari</td>
                                    <td>{{ number_format($t['transit']) }}</td>
                                    <td>{{ number_format($t['general']) }}</td>
                                    <td style="font-weight:700; color:#ec4899;">{{ number_format($t['dcie_sum']) }}</td>
                                    <td>{{ number_format($t['total_mhrs'], 1) }}h</td>
                                    <td>{{ $t['active_ac'] }} A/C</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 4: MONITORING OVERDUE BATIK OD (9M-) --}}
    @if($mainTab === 'overdue')
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            <div class="mod-card" style="border-left:4px solid #ef4444;">
                <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.5rem;height:1.5rem;color:#ef4444;"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                    <h3 style="font-size:1.1rem; font-weight:700; color:#ef4444; margin:0;">Status Kepatuhan Deep Cleaning: 45 Pekerjaan Overdue Batik OD</h3>
                </div>
                <p style="font-size:.85rem; color:var(--cbm-text); margin:0;">
                    Berdasarkan sheet <code>DCIE OD</code>, terdapat 45 pekerjaan deep cleaning (interior &amp; exterior) yang telah melampaui batas siklus 30/45 hari. <strong>22 pekerjaan di antaranya berstatus pesawat OPERATE</strong> yang sedang aktif terbang.
                </p>
            </div>

            <div class="mod-card">
                <div style="margin-bottom:1rem;">
                    <h3 style="font-size:1rem; font-weight:700; color:var(--cbm-text); margin:0;">Daftar Armada Batik OD Prioritas Penjadwalan</h3>
                    <p style="font-size:.8rem; color:var(--cbm-text-muted); margin:0;">Prioritaskan pesawat dengan status OPERATE dan keterlambatan tertinggi</p>
                </div>

                <div class="mod-table-wrap">
                    <table class="mod-table">
                        <thead>
                            <tr>
                                <th>A/C Reg</th>
                                <th>Jenis Deep Cleaning</th>
                                <th>Status Pesawat</th>
                                <th>Keterlambatan (Hari)</th>
                                <th>Tingkat Urgensi</th>
                                <th>Catatan Operasional</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueBatikOd as $od)
                                <tr>
                                    <td>
                                        <span style="font-weight:800; font-size:.9rem; color:var(--cbm-text);">{{ $od['reg'] }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight:700; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; {{ $od['type'] === 'DCI' ? 'background:rgba(168,85,247,.15); color:#a855f7;' : 'background:rgba(236,72,153,.15); color:#ec4899;' }}">
                                            {{ $od['type'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($od['status'] === 'OPERATE')
                                            <span style="font-weight:700; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; background:rgba(239,68,68,.15); color:#ef4444;">
                                                OPERATE (Aktif Terbang)
                                            </span>
                                        @elseif($od['status'] === 'AOG')
                                            <span style="font-weight:700; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; background:rgba(245,158,11,.15); color:#f59e0b;">
                                                AOG Grounded
                                            </span>
                                        @else
                                            <span style="font-weight:700; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; background:rgba(59,130,246,.15); color:#3b82f6;">
                                                {{ $od['status'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="font-weight:800; color:#ef4444; font-size:.9rem;">
                                        +{{ $od['days_overdue'] }} Hari
                                    </td>
                                    <td>
                                        @if($od['status'] === 'OPERATE' && $od['days_overdue'] > 30)
                                            <span style="font-size:.75rem; font-weight:800; color:#ef4444; text-transform:uppercase;">KRITIS</span>
                                        @elseif($od['status'] === 'OPERATE')
                                            <span style="font-size:.75rem; font-weight:700; color:#f59e0b; text-transform:uppercase;">TINGGI</span>
                                        @else
                                            <span style="font-size:.75rem; font-weight:600; color:var(--cbm-text-muted); text-transform:uppercase;">SEDANG</span>
                                        @endif
                                    </td>
                                    <td style="font-size:.8rem; color:var(--cbm-text-muted);">
                                        {{ $od['notes'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL IMPORT MULTI-SHEET AIEC --}}
    @if($isImportModalOpen)
        <div class="mod-modal-overlay" wire:click.self="$set('isImportModalOpen', false)">
            <div class="mod-modal" style="max-width: 580px;">
                <div class="mod-modal-header">
                    <h3 class="mod-modal-title">Import Data AIEC Report 2026</h3>
                    <button type="button" wire:click="$set('isImportModalOpen', false)" class="mod-modal-close">&times;</button>
                </div>
                <form wire:submit.prevent="importData">
                    <div class="mod-modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                        {{-- Pilih Target Sheet --}}
                        <div>
                            <label style="font-size:.85rem; font-weight:700; color:var(--cbm-text); display:block; margin-bottom:.4rem;">
                                Pilih Sheet / Periode Bulan yang Ingin Diimport
                            </label>
                            <select wire:model="importTargetSheet" style="width:100%; font-size:.85rem; padding:.5rem .75rem; border-radius:.375rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text);">
                                <option value="ALL">✨ Semua Sheet Bulanan (DATABASE + SEP + AUG + JUL + JUN)</option>
                                <option value="DATABASE">Sheet: DATABASE (Oktober 2026 / Berjalan)</option>
                                <option value="SEP">Sheet: SEP (September 2026)</option>
                                <option value="AUG 26">Sheet: AUG 26 (Agustus 2026)</option>
                                <option value="JUL 26">Sheet: JUL 26 (Juli 2026)</option>
                                <option value="JUN 26">Sheet: JUN 26 (Juni 2026)</option>
                            </select>
                            <p style="font-size:.75rem; color:var(--cbm-text-muted); margin-top:.25rem;">
                                Anda dapat mengimport seluruh 5 bulan sekaligus atau memilih sheet tertentu dari file <code>AIEC Report 2026.xlsx</code>.
                            </p>
                        </div>

                        {{-- File Input --}}
                        <div>
                            <label style="font-size:.85rem; font-weight:700; color:var(--cbm-text); display:block; margin-bottom:.4rem;">
                                File Excel / CSV
                            </label>
                            <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" style="width:100%; font-size:.85rem; padding:.5rem; border-radius:.375rem; background:var(--cbm-input-bg); border:1px dashed var(--cbm-input-border); color:var(--cbm-text);" />
                            @error('file') <span style="font-size:.75rem; color:#ef4444;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Info Sanitasi Otomatis --}}
                        <div style="background:rgba(59,130,246,.08); padding:.75rem; border-radius:.375rem; border:1px solid rgba(59,130,246,.2); font-size:.75rem; color:var(--cbm-text);">
                            <div style="font-weight:700; color:#3b82f6; margin-bottom:.25rem;">Fitur Sanitasi Otomatis AIEC:</div>
                            <ul style="margin:0; padding-left:1.2rem; display:flex; flex-direction:column; gap:.2rem;">
                                <li>Membaca header di baris 2 secara otomatis (mengabaikan banner judul).</li>
                                <li>Konversi serial date Excel (<code>46296.0</code> ➔ <code>2026-10-01</code>) &amp; jam desimal.</li>
                                <li>Normalisasi shift <strong>SIANG</strong> (memulihkan data yang sebelumnya hilang di sheet manual).</li>
                                <li>Proteksi manhours: mencegah anomali nilai negatif ekstrem.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mod-modal-footer">
                        <button type="button" wire:click="$set('isImportModalOpen', false)" class="mod-btn-outline">Batal</button>
                        <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="importData">
                            <span wire:loading.remove wire:target="importData">Mulai Import Data</span>
                            <span wire:loading wire:target="importData">Memproses Data...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
