<div>
    @php
        $statusBadge = [
            'valid' => ['bg' => 'rgba(16, 185, 129, 0.15)', 'text' => '#10b981', 'label' => 'Valid / Aktif'],
            'expiring_soon' => ['bg' => 'rgba(245, 158, 11, 0.15)', 'text' => '#f59e0b', 'label' => 'Segera Habis'],
            'expired' => ['bg' => 'rgba(239, 68, 68, 0.15)', 'text' => '#ef4444', 'label' => 'Kedaluwarsa'],
            'scheduled' => ['bg' => 'rgba(59, 130, 246, 0.15)', 'text' => '#3b82f6', 'label' => 'Terjadwal'],
            'unscheduled' => ['bg' => 'rgba(148, 163, 184, 0.15)', 'text' => '#94a3b8', 'label' => 'Belum Terjadwal'],
        ];
    @endphp

    <x-master.page-header 
        title="Database Training Cabin Maintenance" 
        subtitle="Monitoring kualifikasi personel, matriks pelatihan, masa berlaku recurrent (HF & SMS), serta jadwal training." 
        accent="blue" 
        eyebrow="Development & Training" 
    />

    <x-flash />

    {{-- KPI Cards Summary Strip --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem;margin-bottom:1rem;">
        <div class="mod-card" style="padding:.9rem 1rem;">
            <div class="mod-subtitle">Total Personel / Rekor</div>
            <div style="font-size:1.45rem;font-weight:700;">
                <span style="color:#38bdf8;">{{ number_format($summary['total_employees']) }}</span> 
                <span style="font-size:.9rem;color:var(--cbm-text-muted);font-weight:500;">/ {{ number_format($summary['total_trainings']) }} rekor</span>
            </div>
            <div class="mod-subtitle">karyawan terdaftar</div>
        </div>

        <button type="button" wire:click="$set('statusFilter','valid')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Kualifikasi Valid</div>
            <div style="font-size:1.45rem;font-weight:700;color:#10b981;">{{ number_format($summary['valid']) }}</div>
            <div class="mod-subtitle">memenuhi standar</div>
        </button>

        <button type="button" wire:click="setTab('recurrent'); $set('statusFilter','expiring_soon')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Segera Kedaluwarsa (&lt; 60 Hari)</div>
            <div style="font-size:1.45rem;font-weight:700;color:#f59e0b;">{{ number_format($summary['expiring_soon']) }}</div>
            <div class="mod-subtitle">perlu koordinasi jadwal</div>
        </button>

        <button type="button" wire:click="setTab('recurrent'); $set('statusFilter','expired')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Kedaluwarsa (Expired)</div>
            <div style="font-size:1.45rem;font-weight:700;color:#ef4444;">{{ number_format($summary['expired']) }}</div>
            <div class="mod-subtitle">prioritas recurrent</div>
        </button>

        <button type="button" wire:click="setTab('schedule'); $set('statusFilter','scheduled')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Terjadwal (Scheduled)</div>
            <div style="font-size:1.45rem;font-weight:700;color:#3b82f6;">{{ number_format($summary['scheduled']) }}</div>
            <div class="mod-subtitle">dalam antrean training</div>
        </button>

        <button type="button" wire:click="setTab('schedule'); $set('statusFilter','unscheduled')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Belum Terjadwal</div>
            <div style="font-size:1.45rem;font-weight:700;color:#94a3b8;">{{ number_format($summary['unscheduled']) }}</div>
            <div class="mod-subtitle">menunggu slot kuota</div>
        </button>
    </div>

    {{-- Main Module Tabs --}}
    <div class="train-tabs" role="tablist" aria-label="Tab Pelatihan">
        <button type="button" wire:click="setTab('matrix')" class="train-tab {{ $tab === 'matrix' ? 'on' : '' }}">
            📋 Matriks Pelatihan (Semua) <span>{{ number_format($summary['total_trainings']) }}</span>
        </button>
        <button type="button" wire:click="setTab('recurrent')" class="train-tab {{ $tab === 'recurrent' ? 'on' : '' }}">
            ⏱️ Recurrent Expiry (HF & SMS) <span>{{ number_format($summary['expiring_soon'] + $summary['expired']) }}</span>
        </button>
        <button type="button" wire:click="setTab('schedule')" class="train-tab {{ $tab === 'schedule' ? 'on' : '' }}">
            📅 Jadwal & Antrean Training <span>{{ number_format($summary['scheduled'] + $summary['unscheduled']) }}</span>
        </button>
        <button type="button" wire:click="setTab('pivot')" class="train-tab {{ $tab === 'pivot' ? 'on' : '' }}" style="border-color:rgba(56,189,248,.35);">
            📊 Summary & Pivot Kualifikasi <span>3 Matriks</span>
        </button>
    </div>

    {{-- Tabs per Rumpun Kualifikasi --}}
    <div style="margin-bottom:.5rem;">
        <div style="font-size:.76rem;font-weight:700;color:var(--cbm-text-muted);margin-bottom:.3rem;letter-spacing:.02em;">
            🎓 RUMPUN KUALIFIKASI:
        </div>
        <div class="train-tabs" role="tablist" aria-label="Rumpun Kualifikasi">
            <button type="button" role="tab" wire:click="setCategory('')" class="train-tab {{ $categoryFilter === '' ? 'on' : '' }}">
                Semua Kualifikasi <span>{{ number_format($summary['total_trainings']) }}</span>
            </button>
            @foreach($categories as $cat)
                @if(($categoryCounts[$cat] ?? 0) > 0 || $categoryFilter === $cat)
                    <button type="button" role="tab" wire:click="setCategory('{{ $cat }}')" class="train-tab {{ $categoryFilter === $cat ? 'on' : '' }}">
                        {{ $cat }} <span>{{ number_format($categoryCounts[$cat] ?? 0) }}</span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Tabs per Divisi --}}
    <div style="margin-bottom:.5rem;">
        <div style="font-size:.76rem;font-weight:700;color:var(--cbm-text-muted);margin-bottom:.3rem;letter-spacing:.02em;">
            🏢 FILTER DIVISI:
        </div>
        <div class="train-tabs" role="tablist" aria-label="Divisi">
            <button type="button" role="tab" wire:click="setDivision('')" class="train-tab {{ $divisionFilter === '' ? 'on' : '' }}">
                Semua Divisi <span>{{ number_format($summary['total_trainings']) }}</span>
            </button>
            @foreach($divisions as $d)
                @if(($divisionCounts[$d->id] ?? 0) > 0 || (string) $divisionFilter === (string) $d->id)
                    <button type="button" role="tab" wire:click="setDivision('{{ $d->id }}')" class="train-tab {{ (string) $divisionFilter === (string) $d->id ? 'on' : '' }}">
                        {{ $d->name }} <span>{{ number_format($divisionCounts[$d->id] ?? 0) }}</span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Tabs per Station --}}
    <div style="margin-bottom:.9rem;">
        <div style="font-size:.76rem;font-weight:700;color:var(--cbm-text-muted);margin-bottom:.3rem;letter-spacing:.02em;">
            📍 FILTER STATION:
        </div>
        <div class="train-tabs" role="tablist" aria-label="Station">
            <button type="button" role="tab" wire:click="setStation('')" class="train-tab {{ $stationFilter === '' ? 'on' : '' }}">
                Semua Station <span>{{ number_format($summary['total_trainings']) }}</span>
            </button>
            @foreach($stations as $stn)
                @if(($stationCounts[$stn] ?? 0) > 0 || $stationFilter === $stn)
                    <button type="button" role="tab" wire:click="setStation('{{ $stn }}')" class="train-tab {{ $stationFilter === $stn ? 'on' : '' }}">
                        {{ $stn }} <span>{{ number_format($stationCounts[$stn] ?? 0) }}</span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>

    <style>
        .train-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.35rem; margin-bottom:.3rem; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .train-tabs::-webkit-scrollbar { display:none; }
        .train-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.4rem .85rem; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .15s; }
        .train-tab span { margin-left:.35rem; font-size:.75rem; opacity:.75; font-weight:700; background:rgba(255,255,255,.08); padding:1px 6px; border-radius:99px; }
        .train-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
        .mod-field-compact { display:flex; align-items:center; }
        .badge-status { display:inline-flex; align-items:center; gap:.3rem; padding:.22rem .65rem; border-radius:999px; font-size:.75rem; font-weight:600; }
        .badge-dot { width:6px; height:6px; border-radius:50%; }
        .cbm-kbd-hint {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--cbm-text-muted);
            background: rgba(255,255,255,.07);
            border: 1px solid var(--cbm-input-border);
            border-radius: 4px;
            padding: 1px 6px;
            pointer-events: none;
            letter-spacing: .03em;
        }
        @keyframes cbmGlowPulse {
            0% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7); border-color: #38bdf8; }
            50% { box-shadow: 0 0 0 6px rgba(56, 189, 248, 0.3); border-color: #38bdf8; }
            100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); }
        }
        .cbm-search-glow {
            animation: cbmGlowPulse 1.2s ease-out;
            border-color: #38bdf8 !important;
        }
    </style>

    {{-- Main Table & Filters Card --}}
    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar" style="flex-wrap:wrap;gap:.75rem;">
            <div class="mod-filters" style="flex-wrap:wrap;gap:.5rem;flex:1 1 60%;">
                {{-- Search with Excel Ctrl+F shortcut --}}
                <div class="mod-field mod-field-search" style="min-width:16rem;flex:1 1 auto;position:relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="training-search-input" wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari nama, NIK, jabatan, atau nama kursus... (Ctrl + F)" aria-label="Cari pelatihan">
                    <span class="cbm-kbd-hint" title="Tekan Ctrl + F untuk fokus pencarian seperti di Excel">Ctrl + F</span>
                </div>

                {{-- Divisi --}}
                <div class="cbm-select-wrap">
                    <select wire:model.live="divisionFilter" class="cbm-form-select" aria-label="Filter divisi">
                        <option value="">Semua Divisi</option>
                        @foreach($divisions as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ number_format($divisionCounts[$d->id] ?? 0) }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Station --}}
                <div class="cbm-select-wrap">
                    <select wire:model.live="stationFilter" class="cbm-form-select" aria-label="Filter station">
                        <option value="">Semua Station</option>
                        @foreach($stations as $stn)
                            <option value="{{ $stn }}">{{ $stn }} ({{ number_format($stationCounts[$stn] ?? 0) }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Perusahaan --}}
                <div class="cbm-select-wrap">
                    <select wire:model.live="companyFilter" class="cbm-form-select" aria-label="Filter perusahaan">
                        <option value="">Semua Perusahaan</option>
                        @foreach($companies as $cmp)
                            <option value="{{ $cmp }}">{{ $cmp }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Kategori --}}
                <div class="cbm-select-wrap">
                    <select wire:model.live="categoryFilter" class="cbm-form-select" aria-label="Filter kategori">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="cbm-select-wrap">
                    <select wire:model.live="statusFilter" class="cbm-form-select" aria-label="Filter status">
                        <option value="">Semua Status</option>
                        <option value="valid">Valid / Aktif</option>
                        <option value="expiring_soon">Segera Habis (&lt; 60 Hari)</option>
                        <option value="expired">Kedaluwarsa (Expired)</option>
                        <option value="scheduled">Terjadwal</option>
                        <option value="unscheduled">Belum Terjadwal</option>
                    </select>
                </div>

                {{-- Reset Button --}}
                @if($search || $divisionFilter || $stationFilter || $companyFilter || $categoryFilter || $statusFilter || $courseFilter)
                    <button type="button" wire:click="resetFilters" class="kd-act-btn" style="background:var(--cbm-input-bg);color:var(--cbm-text-muted);border:1px solid var(--cbm-input-border);border-radius:6px;padding:.38rem .7rem;font-size:.78rem;font-weight:600;cursor:pointer;">
                        ✕ Reset
                    </button>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="mod-meta" style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                @if($canManage)
                    <button type="button" wire:click="openImportModal" class="kd-act-btn" title="Import data excel training baru" style="background:#2563eb;color:#fff;border-color:#2563eb;display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .8rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:1rem;height:1rem;" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        Import Excel
                    </button>
                @endif

                <button type="button" wire:click="exportExcel" class="kd-act-btn" title="Unduh rekapitulasi data training multi-sheet Pivot Excel" style="background:#107c41;color:#fff;border-color:#107c41;display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .8rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;">
                    <span>📊</span> Ekspor Excel (Pivot)
                </button>

                <span class="mod-record-count" style="font-size:.8rem;font-weight:600;color:var(--cbm-text-muted);">
                    {{ number_format($records->total()) }} data
                </span>
            </div>
        </div>

        @if($tab === 'pivot' && $pivots)
            {{-- Summary & Pivot Tables View --}}
            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1.5rem;">
                {{-- Executive Highlight Banner --}}
                <div style="background:linear-gradient(135deg, rgba(30,58,138,.3), rgba(15,23,42,.6));border:1px solid rgba(59,130,246,.3);border-radius:10px;padding:1.15rem 1.4rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                    <div>
                        <div style="font-size:1.1rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:.5rem;">
                            <span>📊</span> Cross-Tabulation & Matriks Pivot Kualifikasi Personel
                        </div>
                        <div style="font-size:.82rem;color:var(--cbm-text-muted);margin-top:3px;">
                            Analisis kepatuhan sertifikasi, status recurrent, dan antrean pelatihan lintas Station, Rumpun Kualifikasi, dan Divisi Organisasi.
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <button type="button" wire:click="exportExcel" class="kd-act-btn" style="background:#107c41;color:#fff;border-color:#107c41;display:inline-flex;align-items:center;gap:.35rem;padding:.42rem .85rem;border-radius:6px;font-size:.82rem;font-weight:600;cursor:pointer;">
                            <span>📥</span> Unduh Excel Pivot Lengkap
                        </button>
                        <button type="button" wire:click="setTab('matrix')" class="kd-act-btn" style="background:var(--cbm-input-bg);color:var(--cbm-text);border:1px solid var(--cbm-input-border);border-radius:6px;padding:.42rem .85rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                            📋 Lihat Detail Baris Data
                        </button>
                    </div>
                </div>

                {{-- 1. Pivot Table: Station vs Status Pelatihan --}}
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;flex-wrap:wrap;gap:.5rem;">
                        <div>
                            <div style="font-size:1.02rem;font-weight:700;color:var(--cbm-text);">
                                📍 Matriks Pivot 1: Kualifikasi per Station Kerja
                            </div>
                            <div style="font-size:.78rem;color:var(--cbm-text-muted);">
                                Klik nama station atau angka status kedaluwarsa untuk langsung memfilter data detail
                            </div>
                        </div>
                        <span style="font-size:.78rem;font-weight:600;color:var(--cbm-text-muted);">
                            {{ count($pivots['stations']) }} Station Terdaftar
                        </span>
                    </div>

                    <div class="mod-table-wrap">
                        <table class="mod-table" style="font-size:.82rem;">
                            <thead>
                                <tr>
                                    <th style="width:18%;">STATION</th>
                                    <th style="text-align:right;width:11%;color:#10b981;">VALID</th>
                                    <th style="text-align:right;width:11%;color:#f59e0b;">SEGERA HABIS</th>
                                    <th style="text-align:right;width:11%;color:#ef4444;">EXPIRED</th>
                                    <th style="text-align:right;width:11%;color:#3b82f6;">TERJADWAL</th>
                                    <th style="text-align:right;width:12%;color:#94a3b8;">BLM TERJADWAL</th>
                                    <th style="text-align:right;width:12%;font-weight:700;">TOTAL REKOR</th>
                                    <th style="text-align:right;width:15%;">% KEPATUHAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pivots['stations'] as $stnName => $stn)
                                    @php
                                        $pRate = $stn['compliance'];
                                        $pColor = $pRate >= 95 ? '#10b981' : ($pRate >= 85 ? '#f59e0b' : '#ef4444');
                                    @endphp
                                    <tr>
                                        <td>
                                            <button type="button" wire:click="setStation('{{ $stnName === 'Tanpa Station' ? '' : $stnName }}'); setTab('matrix')" style="background:none;border:none;padding:0;font-weight:700;color:var(--cbm-accent);cursor:pointer;text-align:left;display:inline-flex;align-items:center;gap:.3rem;" title="Klik untuk filter station ini">
                                                <span>{{ $stnName }}</span>
                                                <span style="font-size:.7rem;opacity:.7;">↗</span>
                                            </button>
                                        </td>
                                        <td style="text-align:right;font-weight:600;color:#10b981;">{{ number_format($stn['valid']) }}</td>
                                        <td style="text-align:right;font-weight:600;color:#f59e0b;">
                                            @if($stn['expiring_soon'] > 0)
                                                <button type="button" wire:click="setStation('{{ $stnName }}'); setTab('recurrent'); $set('statusFilter','expiring_soon')" style="background:none;border:none;padding:0;color:#f59e0b;cursor:pointer;font-weight:700;text-decoration:underline;" title="Buka recurrent segera habis station ini">
                                                    {{ number_format($stn['expiring_soon']) }}
                                                </button>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td style="text-align:right;font-weight:600;color:#ef4444;">
                                            @if($stn['expired'] > 0)
                                                <button type="button" wire:click="setStation('{{ $stnName }}'); setTab('recurrent'); $set('statusFilter','expired')" style="background:none;border:none;padding:0;color:#ef4444;cursor:pointer;font-weight:700;text-decoration:underline;" title="Buka kualifikasi expired station ini">
                                                    {{ number_format($stn['expired']) }}
                                                </button>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td style="text-align:right;font-weight:600;color:#3b82f6;">{{ number_format($stn['scheduled']) }}</td>
                                        <td style="text-align:right;font-weight:500;color:#94a3b8;">{{ number_format($stn['unscheduled']) }}</td>
                                        <td style="text-align:right;font-weight:700;color:var(--cbm-text);">{{ number_format($stn['total']) }}</td>
                                        <td style="text-align:right;">
                                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:.5rem;">
                                                <div style="flex:1;max-width:3.8rem;background:rgba(255,255,255,.08);height:6px;border-radius:99px;overflow:hidden;">
                                                    <div style="width:{{ min(100, $pRate) }}%;background:{{ $pColor }};height:100%;"></div>
                                                </div>
                                                <span style="font-weight:700;color:{{ $pColor }};min-width:2.8rem;">{{ $pRate }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                @php
                                    $tot = $pivots['stationTotals'];
                                    $totRate = $tot['compliance'];
                                    $totColor = $totRate >= 95 ? '#10b981' : ($totRate >= 85 ? '#f59e0b' : '#ef4444');
                                @endphp
                                <tr style="background:rgba(255,255,255,.04);font-weight:700;border-top:2px solid var(--cbm-input-border);">
                                    <td style="color:var(--cbm-text);">GRAND TOTAL</td>
                                    <td style="text-align:right;color:#10b981;">{{ number_format($tot['valid']) }}</td>
                                    <td style="text-align:right;color:#f59e0b;">{{ number_format($tot['expiring_soon']) }}</td>
                                    <td style="text-align:right;color:#ef4444;">{{ number_format($tot['expired']) }}</td>
                                    <td style="text-align:right;color:#3b82f6;">{{ number_format($tot['scheduled']) }}</td>
                                    <td style="text-align:right;color:#94a3b8;">{{ number_format($tot['unscheduled']) }}</td>
                                    <td style="text-align:right;color:var(--cbm-text);font-size:.9rem;">{{ number_format($tot['total']) }}</td>
                                    <td style="text-align:right;">
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.5rem;">
                                            <div style="flex:1;max-width:3.8rem;background:rgba(255,255,255,.08);height:6px;border-radius:99px;overflow:hidden;">
                                                <div style="width:{{ min(100, $totRate) }}%;background:{{ $totColor }};height:100%;"></div>
                                            </div>
                                            <span style="font-weight:700;color:{{ $totColor }};min-width:2.8rem;font-size:.9rem;">{{ $totRate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Grid: Pivot Rumpun & Pivot Divisi --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(27rem,1fr));gap:1.25rem;">
                    {{-- 2. Pivot Table: Rumpun Kualifikasi --}}
                    <div>
                        <div style="margin-bottom:.75rem;">
                            <div style="font-size:1.02rem;font-weight:700;color:var(--cbm-text);">
                                🎓 Matriks Pivot 2: Rumpun Kualifikasi Pelatihan
                            </div>
                            <div style="font-size:.78rem;color:var(--cbm-text-muted);">
                                Mandatory Training, Type Rating & Systems, dan Additional Training
                            </div>
                        </div>

                        <div class="mod-table-wrap">
                            <table class="mod-table" style="font-size:.82rem;">
                                <thead>
                                    <tr>
                                        <th style="width:26%;">RUMPUN KUALIFIKASI</th>
                                        <th style="text-align:right;width:12%;color:#10b981;">VALID</th>
                                        <th style="text-align:right;width:12%;color:#f59e0b;">SEGERA HABIS</th>
                                        <th style="text-align:right;width:12%;color:#ef4444;">EXPIRED</th>
                                        <th style="text-align:right;width:12%;color:#3b82f6;">TERJADWAL</th>
                                        <th style="text-align:right;width:12%;color:#94a3b8;">BLM TERJADWAL</th>
                                        <th style="text-align:right;width:14%;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pivots['categories'] as $catName => $cat)
                                        @php
                                            $cRate = $cat['compliance'];
                                            $cColor = $cRate >= 95 ? '#10b981' : ($cRate >= 85 ? '#f59e0b' : '#ef4444');
                                        @endphp
                                        <tr>
                                            <td>
                                                <button type="button" wire:click="setCategory('{{ $catName === 'Lainnya' ? '' : $catName }}'); setTab('matrix')" style="background:none;border:none;padding:0;font-weight:700;color:var(--cbm-accent);cursor:pointer;text-align:left;display:inline-flex;align-items:center;gap:.3rem;" title="Klik untuk filter kategori ini">
                                                    <span>{{ $catName }}</span>
                                                    <span style="font-size:.7rem;opacity:.7;">↗</span>
                                                </button>
                                            </td>
                                            <td style="text-align:right;font-weight:600;color:#10b981;">{{ number_format($cat['valid']) }}</td>
                                            <td style="text-align:right;font-weight:600;color:#f59e0b;">{{ number_format($cat['expiring_soon']) }}</td>
                                            <td style="text-align:right;font-weight:600;color:#ef4444;">{{ number_format($cat['expired']) }}</td>
                                            <td style="text-align:right;font-weight:600;color:#3b82f6;">{{ number_format($cat['scheduled']) }}</td>
                                            <td style="text-align:right;font-weight:500;color:#94a3b8;">{{ number_format($cat['unscheduled']) }}</td>
                                            <td style="text-align:right;font-weight:700;color:var(--cbm-text);">
                                                {{ number_format($cat['total']) }}
                                                <div style="font-size:.72rem;color:{{ $cColor }};font-weight:600;">{{ $cRate }}%</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php $cTot = $pivots['categoryTotals']; @endphp
                                    <tr style="background:rgba(255,255,255,.04);font-weight:700;border-top:2px solid var(--cbm-input-border);">
                                        <td style="color:var(--cbm-text);">TOTAL</td>
                                        <td style="text-align:right;color:#10b981;">{{ number_format($cTot['valid']) }}</td>
                                        <td style="text-align:right;color:#f59e0b;">{{ number_format($cTot['expiring_soon']) }}</td>
                                        <td style="text-align:right;color:#ef4444;">{{ number_format($cTot['expired']) }}</td>
                                        <td style="text-align:right;color:#3b82f6;">{{ number_format($cTot['scheduled']) }}</td>
                                        <td style="text-align:right;color:#94a3b8;">{{ number_format($cTot['unscheduled']) }}</td>
                                        <td style="text-align:right;color:var(--cbm-text);">
                                            {{ number_format($cTot['total']) }}
                                            <div style="font-size:.72rem;color:#10b981;">{{ $cTot['compliance'] }}%</div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Pivot Table: Divisi Karyawan --}}
                    <div>
                        <div style="margin-bottom:.75rem;">
                            <div style="font-size:1.02rem;font-weight:700;color:var(--cbm-text);">
                                🏢 Matriks Pivot 3: Kualifikasi per Divisi Unit Kerja
                            </div>
                            <div style="font-size:.78rem;color:var(--cbm-text-muted);">
                                Distribusi kualifikasi personel berdasarkan divisi organisasi
                            </div>
                        </div>

                        <div class="mod-table-wrap">
                            <table class="mod-table" style="font-size:.82rem;">
                                <thead>
                                    <tr>
                                        <th style="width:26%;">DIVISI UNIT</th>
                                        <th style="text-align:right;width:12%;color:#10b981;">VALID</th>
                                        <th style="text-align:right;width:12%;color:#f59e0b;">SEGERA HABIS</th>
                                        <th style="text-align:right;width:12%;color:#ef4444;">EXPIRED</th>
                                        <th style="text-align:right;width:12%;color:#3b82f6;">TERJADWAL</th>
                                        <th style="text-align:right;width:12%;color:#94a3b8;">BLM TERJADWAL</th>
                                        <th style="text-align:right;width:14%;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pivots['divisions'] as $dName => $div)
                                        @php
                                            $dRate = $div['compliance'];
                                            $dColor = $dRate >= 95 ? '#10b981' : ($dRate >= 85 ? '#f59e0b' : '#ef4444');
                                        @endphp
                                        <tr>
                                            <td>
                                                <button type="button" wire:click="setDivision('{{ $dName }}'); setTab('matrix')" style="background:none;border:none;padding:0;font-weight:700;color:var(--cbm-accent);cursor:pointer;text-align:left;display:inline-flex;align-items:center;gap:.3rem;" title="Klik untuk filter divisi ini">
                                                    <span>{{ $dName }}</span>
                                                    <span style="font-size:.7rem;opacity:.7;">↗</span>
                                                </button>
                                            </td>
                                            <td style="text-align:right;font-weight:600;color:#10b981;">{{ number_format($div['valid']) }}</td>
                                            <td style="text-align:right;font-weight:600;color:#f59e0b;">{{ number_format($div['expiring_soon']) }}</td>
                                            <td style="text-align:right;font-weight:600;color:#ef4444;">{{ number_format($div['expired']) }}</td>
                                            <td style="text-align:right;font-weight:600;color:#3b82f6;">{{ number_format($div['scheduled']) }}</td>
                                            <td style="text-align:right;font-weight:500;color:#94a3b8;">{{ number_format($div['unscheduled']) }}</td>
                                            <td style="text-align:right;font-weight:700;color:var(--cbm-text);">
                                                {{ number_format($div['total']) }}
                                                <div style="font-size:.72rem;color:{{ $dColor }};font-weight:600;">{{ $dRate }}%</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php $dTot = $pivots['divisionTotals']; @endphp
                                    <tr style="background:rgba(255,255,255,.04);font-weight:700;border-top:2px solid var(--cbm-input-border);">
                                        <td style="color:var(--cbm-text);">TOTAL</td>
                                        <td style="text-align:right;color:#10b981;">{{ number_format($dTot['valid']) }}</td>
                                        <td style="text-align:right;color:#f59e0b;">{{ number_format($dTot['expiring_soon']) }}</td>
                                        <td style="text-align:right;color:#ef4444;">{{ number_format($dTot['expired']) }}</td>
                                        <td style="text-align:right;color:#3b82f6;">{{ number_format($dTot['scheduled']) }}</td>
                                        <td style="text-align:right;color:#94a3b8;">{{ number_format($dTot['unscheduled']) }}</td>
                                        <td style="text-align:right;color:var(--cbm-text);">
                                            {{ number_format($dTot['total']) }}
                                            <div style="font-size:.72rem;color:#10b981;">{{ $dTot['compliance'] }}%</div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Table Content --}}
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th style="width:10%;">ID / NIK</th>
                            <th style="width:20%;">NAMA & JABATAN</th>
                            <th style="width:10%;">STATION / CORP</th>
                            <th style="width:20%;">KURSUS / PELATIHAN</th>
                            <th style="width:12%;">TGL TRAINING</th>
                            <th style="width:15%;">RECURRENT & MASA BERLAKU</th>
                            <th style="width:10%;">STATUS</th>
                            <th style="text-align:right;width:3%;">TRANSKRIP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $r)
                            @php
                                $cfg = $statusBadge[$r->status] ?? $statusBadge['valid'];
                                $now = now()->startOfDay();
                                $daysRemaining = null;
                                if ($r->expiry_date) {
                                    $daysRemaining = $now->diffInDays($r->expiry_date, false);
                                }
                            @endphp
                            <tr wire:key="rec-{{ $r->id }}">
                                <td style="font-weight:600;color:var(--cbm-text);">
                                    {{ $r->employee_nik }}
                                </td>
                                <td>
                                    <div class="mod-aircraft-name" style="font-weight:600;">
                                        {{ $r->employee_name }}
                                    </div>
                                    <div class="mod-subtitle" style="font-size:.78rem;">
                                        {{ $r->job_title ?: '-' }}
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:600;color:var(--cbm-accent);">{{ $r->station ?: '-' }}</span>
                                    @if($r->company)
                                        <div class="mod-subtitle" style="font-size:.75rem;">{{ $r->company }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--cbm-text);line-height:1.25;">
                                        {{ $r->course_name }}
                                    </div>
                                    <div class="mod-subtitle" style="font-size:.75rem;margin-top:2px;">
                                        {{ $r->category }}
                                    </div>
                                </td>
                                <td>
                                    @if($r->course_date)
                                        <div style="font-size:.82rem;font-weight:600;">{{ $r->course_date->format('d/m/Y') }}</div>
                                    @elseif($r->raw_value && in_array(strtoupper($r->raw_value), ['X','V','YA','CHECK'], true))
                                        <span style="font-size:.8rem;color:#10b981;font-weight:600;">✓ Kualifikasi</span>
                                    @else
                                        <span class="mod-subtitle">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->expiry_date)
                                        <div style="display:flex;flex-direction:column;gap:2px;">
                                            <div style="font-size:.82rem;font-weight:600;">
                                                Exp: {{ $r->expiry_date->format('d/m/Y') }}
                                            </div>
                                            @if($daysRemaining !== null)
                                                @if($daysRemaining < 0)
                                                    <div style="font-size:.74rem;color:#ef4444;font-weight:600;">
                                                        ⚠️ Lewat {{ abs($daysRemaining) }} hari
                                                    </div>
                                                @elseif($daysRemaining <= 60)
                                                    <div style="font-size:.74rem;color:#f59e0b;font-weight:600;">
                                                        ⏳ Sisa {{ $daysRemaining }} hari
                                                    </div>
                                                @else
                                                    <div style="font-size:.74rem;color:#10b981;font-weight:500;">
                                                        ✓ Sisa {{ $daysRemaining }} hari
                                                    </div>
                                                @endif
                                            @endif
                                            @if($r->last_recurrent_date)
                                                <div class="mod-subtitle" style="font-size:.72rem;">Last: {{ $r->last_recurrent_date->format('d/m/Y') }}</div>
                                            @endif
                                        </div>
                                    @elseif($r->last_recurrent_date)
                                        <div style="font-size:.82rem;">Last: {{ $r->last_recurrent_date->format('d/m/Y') }}</div>
                                    @elseif($r->schedule_status)
                                        <div style="font-size:.78rem;color:#3b82f6;font-weight:600;">
                                            📌 {{ $r->schedule_status }}
                                        </div>
                                    @else
                                        <span class="mod-subtitle">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-status" style="background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }};">
                                        <span class="badge-dot" style="background:{{ $cfg['text'] }};"></span>
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <button type="button" wire:click="openDetail('{{ $r->employee_nik }}')" class="kd-act-btn" title="Buka profil & transkrip seluruh pelatihan karyawan ini" style="background:var(--cbm-input-bg);color:var(--cbm-text);border:1px solid var(--cbm-input-border);border-radius:6px;padding:.3rem .55rem;font-size:.75rem;cursor:pointer;">
                                        👁️
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--cbm-text-muted);">
                                    Tidak ada rekor data training yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="padding:.75rem 1rem;border-top:1px solid var(--cbm-border-subtle);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
                <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--cbm-text-muted);">
                    <span>Tampilkan per halaman:</span>
                    <select wire:model.live="perPage" class="cbm-form-select" style="padding:.2rem .5rem;font-size:.8rem;">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div>
                    {{ $records->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Detail Modal: Transkrip Kualifikasi Pelatihan Karyawan --}}
    @if($isDetailOpen)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;z-index:999;padding:1rem;" wire:click.self="closeDetail">
            <div class="mod-card" style="width:100%;max-width:52rem;max-height:90vh;display:flex;flex-direction:column;background:var(--cbm-card-bg);border:1px solid var(--cbm-card-border);box-shadow:0 20px 40px rgba(0,0,0,.45);border-radius:12px;overflow:hidden;">
                {{-- Header --}}
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--cbm-border-subtle);display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.02);">
                    <div>
                        <div style="font-size:1.15rem;font-weight:700;color:var(--cbm-text);">
                            {{ $selectedEmployeeName }}
                        </div>
                        <div style="font-size:.82rem;color:var(--cbm-text-muted);display:flex;gap:.75rem;margin-top:2px;">
                            <span>ID: <strong>{{ $selectedEmployeeNik }}</strong></span>
                            <span>·</span>
                            <span>Station: <strong>{{ $selectedEmployeeStation ?: '-' }}</strong></span>
                            <span>·</span>
                            <span>Jabatan: <strong>{{ $selectedEmployeeJob ?: '-' }}</strong></span>
                            @if($selectedEmployeeCompany)
                                <span>·</span>
                                <span>Corp: <strong>{{ $selectedEmployeeCompany }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="closeDetail" style="background:transparent;border:none;color:var(--cbm-text-muted);font-size:1.4rem;cursor:pointer;padding:.2rem .5rem;">
                        ✕
                    </button>
                </div>

                {{-- Body (Scrollable) --}}
                <div style="padding:1.25rem 1.5rem;overflow-y:auto;flex:1;">
                    @forelse($employeeTrainingsGrouped as $category => $items)
                        <div style="margin-bottom:1.5rem;">
                            <div style="font-size:.9rem;font-weight:700;color:var(--cbm-accent);border-bottom:1px solid var(--cbm-border-subtle);padding-bottom:.4rem;margin-bottom:.6rem;display:flex;justify-content:space-between;">
                                <span>{{ $category }}</span>
                                <span style="font-size:.78rem;color:var(--cbm-text-muted);font-weight:500;">{{ count($items) }} kursus</span>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(22rem,1fr));gap:.5rem;">
                                @foreach($items as $item)
                                    @php
                                        $cfg = $statusBadge[$item['status']] ?? $statusBadge['valid'];
                                    @endphp
                                    <div style="padding:.65rem .85rem;border:1px solid var(--cbm-border-subtle);border-radius:8px;background:rgba(255,255,255,.015);display:flex;flex-direction:column;justify-content:space-between;gap:.4rem;">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
                                            <div style="font-weight:600;font-size:.84rem;color:var(--cbm-text);line-height:1.25;">
                                                {{ $item['course_name'] }}
                                            </div>
                                            <span class="badge-status" style="background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }};white-space:nowrap;font-size:.7rem;padding:.15rem .5rem;">
                                                {{ $cfg['label'] }}
                                            </span>
                                        </div>
                                        <div style="font-size:.76rem;color:var(--cbm-text-muted);display:flex;justify-content:space-between;align-items:center;border-top:1px dashed var(--cbm-border-subtle);padding-top:.35rem;">
                                            <div>
                                                @if($item['course_date'])
                                                    <span>Tgl: {{ $item['course_date'] }}</span>
                                                @elseif($item['raw_value'])
                                                    <span>Ket: {{ $item['raw_value'] }}</span>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </div>
                                            <div>
                                                @if($item['expiry_date'])
                                                    <span style="color:{{ $item['status'] === 'expired' ? '#ef4444' : ($item['status'] === 'expiring_soon' ? '#f59e0b' : 'inherit') }};font-weight:600;">
                                                        Exp: {{ $item['expiry_date'] }}
                                                    </span>
                                                @elseif($item['schedule_status'])
                                                    <span style="color:#3b82f6;font-weight:600;">📌 {{ $item['schedule_status'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:2rem;color:var(--cbm-text-muted);">
                            Tidak ada data kualifikasi pelatihan untuk karyawan ini.
                        </div>
                    @endforelse
                </div>

                {{-- Footer --}}
                <div style="padding:.9rem 1.5rem;border-top:1px solid var(--cbm-border-subtle);display:flex;justify-content:flex-end;background:rgba(255,255,255,.02);">
                    <button type="button" wire:click="closeDetail" class="kd-act-btn" style="background:var(--cbm-input-bg);color:var(--cbm-text);border:1px solid var(--cbm-input-border);border-radius:6px;padding:.45rem 1.1rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                        Tutup Transkrip
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import Modal --}}
    @if($isImportOpen)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;z-index:999;padding:1rem;" wire:click.self="closeImportModal">
            <div class="mod-card" style="width:100%;max-width:36rem;background:var(--cbm-card-bg);border:1px solid var(--cbm-card-border);box-shadow:0 20px 40px rgba(0,0,0,.45);border-radius:12px;overflow:hidden;">
                {{-- Header --}}
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--cbm-border-subtle);display:flex;justify-content:space-between;align-items:center;">
                    <div style="font-size:1.1rem;font-weight:700;color:var(--cbm-text);">
                        Import Data Base Training Excel
                    </div>
                    <button type="button" wire:click="closeImportModal" style="background:transparent;border:none;color:var(--cbm-text-muted);font-size:1.3rem;cursor:pointer;">
                        ✕
                    </button>
                </div>

                {{-- Body --}}
                <div style="padding:1.25rem 1.5rem;">
                    <p style="font-size:.85rem;color:var(--cbm-text-muted);line-height:1.5;margin-bottom:1rem;">
                        Pilih file Excel database training master (misal: <code>DATA BASE TRAINING CABIN MAINTENANCE TERUPDATE 2026 MASTER.xlsx</code>). Sistem akan membaca sheet matriks pelatihan, mendeteksi masa berlaku recurrent (Human Factors & SMS), dan menyinkronkan status jadwal secara otomatis.
                    </p>

                    <div style="border:2px dashed var(--cbm-input-border);border-radius:8px;padding:1.5rem;text-align:center;background:rgba(255,255,255,.015);margin-bottom:1rem;">
                        <input type="file" wire:model="importFile" id="trainingFileInput" accept=".xlsx,.xls" style="display:none;">
                        <label for="trainingFileInput" style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:.5rem;">
                            <span style="font-size:2rem;">📁</span>
                            <span style="font-weight:600;font-size:.9rem;color:var(--cbm-text);">
                                {{ $importFile ? $importFile->getClientOriginalName() : 'Klik untuk memilih file Excel (.xlsx / .xls)' }}
                            </span>
                            <span style="font-size:.78rem;color:var(--cbm-text-muted);">
                                Maksimal ukuran file 50MB
                            </span>
                        </label>
                    </div>

                    @error('importFile')
                        <div style="color:#ef4444;font-size:.82rem;margin-bottom:.8rem;font-weight:600;">
                            ⚠️ {{ $message }}
                        </div>
                    @enderror

                    <div wire:loading wire:target="importExcel" style="font-size:.85rem;color:#38bdf8;margin-bottom:.8rem;font-weight:600;">
                        ⏳ Sedang memproses dan mengimpor matriks pelatihan ke database... Harap tunggu sebentar.
                    </div>

                    @if($importResult)
                        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:.85rem;font-size:.82rem;margin-top:1rem;">
                            <div style="font-weight:700;color:#10b981;margin-bottom:.4rem;">✓ Hasil Sinkronisasi Import:</div>
                            <ul style="margin:0;padding-left:1.2rem;color:var(--cbm-text);line-height:1.5;">
                                <li>Karyawan diproses: <strong>{{ number_format($importResult['employees_processed']) }}</strong></li>
                                <li>Rekor kualifikasi tersimpan: <strong>{{ number_format($importResult['trainings_recorded']) }}</strong></li>
                                <li>Kualifikasi valid: <strong>{{ number_format($importResult['valid_count']) }}</strong></li>
                                <li>Recurrent mendekati habis: <strong>{{ number_format($importResult['expiring_soon']) }}</strong></li>
                                <li>Recurrent expired: <strong>{{ number_format($importResult['expired']) }}</strong></li>
                                <li>Status terjadwal: <strong>{{ number_format($importResult['scheduled']) }}</strong></li>
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div style="padding:1rem 1.5rem;border-top:1px solid var(--cbm-border-subtle);display:flex;justify-content:flex-end;gap:.6rem;background:rgba(255,255,255,.02);">
                    <button type="button" wire:click="closeImportModal" class="kd-act-btn" style="background:var(--cbm-input-bg);color:var(--cbm-text);border:1px solid var(--cbm-input-border);border-radius:6px;padding:.45rem 1rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                        Batal
                    </button>
                    <button type="button" wire:click="importExcel" wire:loading.attr="disabled" class="kd-act-btn" style="background:#2563eb;color:#fff;border-color:#2563eb;border-radius:6px;padding:.45rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                        Mulai Import
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('keydown', function (e) {
            // Excel-style Ctrl + F or Cmd + F to jump to table search
            if ((e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'F')) {
                const searchInput = document.getElementById('training-search-input');
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                    searchInput.classList.add('cbm-search-glow');
                    setTimeout(() => {
                        searchInput.classList.remove('cbm-search-glow');
                    }, 1200);
                }
            } else if (e.key === 'Escape') {
                const searchInput = document.getElementById('training-search-input');
                if (searchInput && document.activeElement === searchInput) {
                    searchInput.blur();
                }
            }
        });
    </script>
</div>
