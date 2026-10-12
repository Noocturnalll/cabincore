<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Cabin Equipment</div>
            <div class="mod-title">Stretcher Tracking &amp; Management</div>
            <div class="mod-subtitle">Pencatatan CRUD transaksi pemakaian tandu pesawat (stretcher), tracking lokasi titik akhir (end point), dan monitoring armada.</div>
        </div>
        <div class="mod-actions" style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" class="mod-btn-outline" style="display:inline-flex;align-items:center;gap:.4rem;" title="Download Excel dengan Pivot & Rekapitulasi">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.1rem;height:1.1rem;color:var(--cbm-green,#10b981);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span wire:loading.remove wire:target="exportExcel">Export Excel (Pivot)</span>
                <span wire:loading wire:target="exportExcel">Mengekspor...</span>
            </button>
            <button type="button" wire:click="openCreateTransaction" class="mod-btn-primary" style="display:inline-flex;align-items:center;gap:.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.1rem;height:1.1rem;" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Catat Transaksi Stretcher
            </button>
            <button type="button" wire:click="openCreateUnit" class="mod-btn-outline" style="display:inline-flex;align-items:center;gap:.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.1rem;height:1.1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Unit Stretcher
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="cbm-seg" role="tablist" style="margin-bottom:1.25rem;">
        <button type="button" wire:click="setTab('tracking')" class="cbm-seg-btn {{ $activeTab === 'tracking' ? 'active' : '' }}">
            Status &amp; Titik Lokasi Unit ({{ $stats['total'] }})
        </button>
        <button type="button" wire:click="setTab('transactions')" class="cbm-seg-btn {{ $activeTab === 'transactions' ? 'active' : '' }}">
            Riwayat Transaksi &amp; Penerbangan ({{ $transactions->total() }})
        </button>
        <button type="button" wire:click="setTab('units')" class="cbm-seg-btn {{ $activeTab === 'units' ? 'active' : '' }}">
            Master Data Unit Stretcher
        </button>
    </div>

    <x-flash />

    {{-- Summary Cards --}}
    <div class="mod-stats" style="margin-bottom:1.5rem;">
        <div class="mod-stat">
            <div class="mod-stat-label">Total Unit Stretcher</div>
            <div class="mod-stat-value">{{ $stats['total'] }}</div>
            <div class="mod-stat-sub">seluruh armada kabin</div>
        </div>
        <div class="mod-stat">
            <div class="mod-stat-label">Tersedia (Ready)</div>
            <div class="mod-stat-value" style="color:var(--cbm-green,#10b981);">{{ $stats['available'] }}</div>
            <div class="mod-stat-sub">siap dipasang di pesawat</div>
        </div>
        <div class="mod-stat">
            <div class="mod-stat-label">Sedang Digunakan</div>
            <div class="mod-stat-value" style="color:var(--cbm-blue,#3b82f6);">{{ $stats['in_use'] }}</div>
            <div class="mod-stat-sub">dalam penerbangan / terpasang</div>
        </div>
        <div class="mod-stat">
            <div class="mod-stat-label">Sebaran Stasiun Aktif</div>
            <div class="mod-stat-value" style="font-size:1.15rem;display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                @foreach($stats['station_counts'] as $stn => $cnt)
                    <span style="background:var(--cbm-card-bg);border:1px solid var(--cbm-card-border);padding:.15rem .5rem;border-radius:.4rem;font-size:.8rem;font-weight:700;">
                        {{ $stn }}: <span style="color:var(--cbm-blue,#3b82f6);">{{ $cnt }}</span>
                    </span>
                @endforeach
            </div>
            <div class="mod-stat-sub">titik lokasi terakhir</div>
        </div>
    </div>

    {{-- TAB 1: TRACKING OVERVIEW --}}
    @if($activeTab === 'tracking')
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,21rem),1fr));gap:1.15rem;margin-bottom:1.5rem;">
            @forelse($stretchers as $s)
                @php
                    $latest = $s->latestTransaction;
                    $statusColor = match($s->status) {
                        'AVAILABLE' => '#10b981',
                        'IN_USE' => '#3b82f6',
                        'MAINTENANCE' => '#ef4444',
                        default => '#6b7280',
                    };
                @endphp
                <div class="mod-card" style="padding:1.25rem;border-top:3px solid {{ $statusColor }};display:flex;flex-direction:column;justify-content:space-between;gap:1rem;">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.5rem;">
                            <div>
                                <span style="font-family:monospace;font-size:.78rem;font-weight:800;color:var(--cbm-text-muted);background:var(--cbm-nav-hover);padding:.15rem .45rem;border-radius:.3rem;">
                                    {{ $s->code }}
                                </span>
                                <h3 style="font-size:1.05rem;font-weight:800;color:var(--cbm-text);margin-top:.35rem;">
                                    {{ $s->name }}
                                </h3>
                            </div>
                            <span style="font-size:.72rem;font-weight:800;padding:.2rem .55rem;border-radius:999px;background:{{ $statusColor }}20;color:{{ $statusColor }};">
                                {{ $s->status }}
                            </span>
                        </div>

                        {{-- Current Location Badge (End Point) --}}
                        <div style="background:rgba(59,130,246,0.08);border:1px dashed rgba(59,130,246,0.3);border-radius:.6rem;padding:.65rem .85rem;margin:.75rem 0;display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-size:.7rem;font-weight:700;color:var(--cbm-text-muted);text-transform:uppercase;">Titik Lokasi Saat Ini (End Point)</div>
                                <div style="font-size:1.25rem;font-weight:900;color:var(--cbm-blue);display:flex;align-items:center;gap:.35rem;">
                                    <span>📍</span> {{ $s->current_station }}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:.7rem;color:var(--cbm-text-muted);">Tipe Pesawat</div>
                                <div style="font-size:.85rem;font-weight:700;color:var(--cbm-text);">{{ $s->aircraft_type ?: 'All Fleet' }}</div>
                            </div>
                        </div>

                        {{-- Latest Movement / Flight --}}
                        <div style="font-size:.8rem;color:var(--cbm-text-muted);line-height:1.45;border-top:1px solid var(--cbm-card-border);padding-top:.65rem;">
                            @if($latest)
                                <div style="font-weight:700;color:var(--cbm-text);margin-bottom:.2rem;">
                                    Penerbangan Terakhir:
                                </div>
                                <div><strong>{{ $latest->flight_number }}</strong> ({{ $latest->aircraft_registration }}) &bull; {{ $latest->route }}</div>
                                <div>Pasien: <span style="color:var(--cbm-text);font-weight:600;">{{ $latest->passenger_name }}</span></div>
                                <div style="font-size:.72rem;margin-top:.2rem;">{{ \Carbon\Carbon::parse($latest->date)->format('d M Y') }}</div>
                            @else
                                <div style="font-style:italic;">Belum ada riwayat pergerakan penerbangan.</div>
                            @endif
                        </div>
                    </div>

                    {{-- Card Action --}}
                    <div style="display:flex;gap:.5rem;border-top:1px solid var(--cbm-card-border);padding-top:.75rem;">
                        <button type="button" wire:click="openCreateTransaction({{ $s->id }})" class="mod-btn-primary" style="flex:1;justify-content:center;padding:.45rem .75rem;font-size:.8rem;">
                            🛫 Catat Terbang Baru
                        </button>
                        <button type="button" wire:click="openEditUnit({{ $s->id }})" class="mod-btn-outline" style="padding:.45rem .65rem;font-size:.8rem;" title="Edit Unit">
                            ⚙️
                        </button>
                    </div>
                </div>
            @empty
                <div class="mod-empty" style="grid-column:1/-1;">
                    <div class="mod-empty-title">Belum ada unit stretcher</div>
                    <div class="mod-empty-sub">Klik "Tambah Unit Stretcher" untuk mendaftarkan tandu baru.</div>
                </div>
            @endforelse
        </div>
    @endif

    {{-- TAB 2: TRANSAKSI & PENERBANGAN --}}
    @if($activeTab === 'transactions' || $activeTab === 'tracking')
        <div class="mod-card mod-card-accent-blue" style="margin-top:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                <h3 style="font-size:1.1rem;font-weight:800;color:var(--cbm-text);">
                    Data Transaksi &amp; Pergerakan Stretcher
                </h3>
            </div>

            {{-- Filter Bar --}}
            <div style="display:flex;flex-wrap:wrap;gap:.65rem;align-items:center;margin-bottom:1rem;background:var(--cbm-nav-hover);padding:.75rem 1rem;border-radius:.65rem;">
                <div style="flex:1;min-width:14rem;">
                    <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari pesawat, flight no, penumpang, rute..." class="mod-search-input mod-input-plain" style="width:100%;text-align:left;">
                </div>
                <select wire:model.live="stretcherFilter" class="mod-select" style="min-width:10rem;">
                    <option value="">Semua Stretcher</option>
                    @foreach($stretchers as $st)
                        <option value="{{ $st->id }}">{{ $st->code }} - {{ $st->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="stationFilter" class="mod-select" style="min-width:9rem;">
                    <option value="">Semua Stasiun</option>
                    @foreach($commonStations as $cs)
                        <option value="{{ $cs }}">{{ $cs }}</option>
                    @endforeach
                </select>
                <input type="date" wire:model.live="dateFilter" class="mod-input-plain" style="padding:.4rem .6rem;border-radius:.4rem;border:1px solid var(--cbm-input-border);background:var(--cbm-input-bg);color:var(--cbm-text);font-size:.825rem;">
                @if($search || $stretcherFilter || $stationFilter || $dateFilter)
                    <button type="button" wire:click="$set('search', ''); $set('stretcherFilter', ''); $set('stationFilter', ''); $set('dateFilter', '');" class="mod-btn-outline" style="padding:.4rem .7rem;font-size:.78rem;">
                        Reset Filter
                    </button>
                @endif
            </div>

            {{-- Transactions Table --}}
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>STRETCHER</th>
                            <th>PESAWAT (REG)</th>
                            <th>FLIGHT NO</th>
                            <th>RUTE (ORIGIN &rarr; END POINT)</th>
                            <th>PENUMPANG (PASIEN)</th>
                            <th>KONDISI / MEDIS</th>
                            <th>STATUS</th>
                            <th style="text-align:right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                            <tr wire:key="trx-{{ $t->id }}">
                                <td style="white-space:nowrap;font-weight:700;">
                                    {{ \Carbon\Carbon::parse($t->date)->format('d M Y') }}
                                </td>
                                <td>
                                    <div style="font-weight:800;color:var(--cbm-blue);">{{ $t->stretcher?->code }}</div>
                                    <div class="mod-aircraft-sub" style="font-size:.72rem;">{{ $t->stretcher?->name }}</div>
                                </td>
                                <td>
                                    <span class="mod-aircraft-name" style="font-size:.85rem;background:var(--cbm-nav-hover);padding:.15rem .45rem;border-radius:.3rem;">
                                        {{ $t->aircraft_registration }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color:var(--cbm-text);">{{ $t->flight_number }}</strong>
                                </td>
                                <td>
                                    <div style="display:inline-flex;align-items:center;gap:.35rem;font-weight:800;">
                                        <span style="background:rgba(59,130,246,0.12);color:var(--cbm-blue);padding:.15rem .45rem;border-radius:.3rem;">{{ $t->origin_station }}</span>
                                        <span style="color:var(--cbm-text-muted);">&rarr;</span>
                                        <span style="background:rgba(16,185,129,0.15);color:var(--cbm-green,#10b981);padding:.15rem .45rem;border-radius:.3rem;" title="Titik Akhir (End Point)">📍 {{ $t->destination_station }}</span>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color:var(--cbm-text);font-size:.875rem;">{{ $t->passenger_name }}</strong>
                                </td>
                                <td style="max-width:16rem;white-space:normal;font-size:.8rem;color:var(--cbm-text-muted);">
                                    {{ $t->medical_notes ?: '-' }}
                                </td>
                                <td>
                                    @if($t->status === 'COMPLETED')
                                        <span class="mod-badge-closed">Tiba / Selesai</span>
                                    @elseif($t->status === 'IN_TRANSIT')
                                        <span class="mod-badge-progress">Dalam Penerbangan</span>
                                    @else
                                        <span class="mod-badge-open">Batal</span>
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <div style="display:inline-flex;gap:.35rem;">
                                        <button type="button" wire:click="openEditTransaction({{ $t->id }})" class="mod-action-btn" title="Edit Transaksi">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="deleteTransaction({{ $t->id }})" wire:confirm="Hapus transaksi ini? Posisi stretcher akan diselaraskan ulang." class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);" title="Hapus">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="mod-empty">
                                        <div class="mod-empty-title">Belum ada transaksi pergerakan stretcher</div>
                                        <div class="mod-empty-sub">Klik tombol "Catat Transaksi Stretcher" untuk menambah pemakaian baru.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="mod-pagination" style="margin-top:1rem;">
                    {{ $transactions->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 3: MASTER UNIT STRETCHER --}}
    @if($activeTab === 'units')
        <div class="mod-card mod-card-accent-blue" style="margin-top:1.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h3 style="font-size:1.1rem;font-weight:800;color:var(--cbm-text);">Daftar Master Unit Stretcher</h3>
                    <div style="font-size:.8rem;color:var(--cbm-text-muted);">Kelola unit fisik stretcher, nomor seri, dan penempatan station awal.</div>
                </div>
                <button type="button" wire:click="openCreateUnit" class="mod-btn-primary" style="font-size:.825rem;padding:.45rem .85rem;">
                    + Tambah Unit Stretcher
                </button>
            </div>

            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>KODE</th>
                            <th>NAMA UNIT</th>
                            <th>TIPE PESAWAT</th>
                            <th>PART &amp; SERIAL NUMBER</th>
                            <th>LOKASI TERKINI (END POINT)</th>
                            <th>KONDISI</th>
                            <th>STATUS</th>
                            <th style="text-align:right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stretchers as $st)
                            <tr wire:key="unit-{{ $st->id }}">
                                <td>
                                    <strong style="font-family:monospace;font-size:.9rem;color:var(--cbm-blue);">{{ $st->code }}</strong>
                                </td>
                                <td>
                                    <strong style="color:var(--cbm-text);">{{ $st->name }}</strong>
                                    @if($st->notes)
                                        <div style="font-size:.75rem;color:var(--cbm-text-muted);">{{ $st->notes }}</div>
                                    @endif
                                </td>
                                <td>{{ $st->aircraft_type ?: 'All Fleet' }}</td>
                                <td style="font-size:.8rem;font-family:monospace;">
                                    <div>PN: {{ $st->part_number ?: '-' }}</div>
                                    <div>SN: {{ $st->serial_number ?: '-' }}</div>
                                </td>
                                <td>
                                    <span style="background:rgba(59,130,246,0.12);color:var(--cbm-blue);font-weight:800;padding:.2rem .6rem;border-radius:.4rem;display:inline-flex;align-items:center;gap:.25rem;">
                                        📍 {{ $st->current_station }}
                                    </span>
                                </td>
                                <td>
                                    @if($st->condition === 'GOOD')
                                        <span class="mod-badge-closed">Baik</span>
                                    @elseif($st->condition === 'FAIR')
                                        <span class="mod-badge-progress">Cukup</span>
                                    @else
                                        <span class="mod-badge-open">Rusak</span>
                                    @endif
                                </td>
                                <td>
                                    @if($st->status === 'AVAILABLE')
                                        <span class="mod-badge-closed">Ready</span>
                                    @elseif($st->status === 'IN_USE')
                                        <span class="mod-badge-progress">Dipakai</span>
                                    @else
                                        <span class="mod-badge-open">Maintenance</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;gap:.35rem;">
                                        <button type="button" wire:click="openEditUnit({{ $st->id }})" class="mod-action-btn">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="deleteUnit({{ $st->id }})" wire:confirm="Hapus unit stretcher ini?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="mod-empty"><div class="mod-empty-title">Belum ada unit stretcher</div></div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODAL 1: FORM TRANSAKSI PERGERAKAN STRETCHER --}}
    @if($isTransactionModalOpen)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:999;padding:1rem;">
            <div class="mod-card" style="width:100%;max-width:38rem;max-height:92vh;overflow-y:auto;padding:1.5rem;box-shadow:0 20px 40px rgba(0,0,0,0.4);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--cbm-card-border);padding-bottom:.85rem;">
                    <div>
                        <h3 style="font-size:1.15rem;font-weight:800;color:var(--cbm-text);">
                            {{ $editingTransactionId ? 'Edit Transaksi Stretcher' : 'Catat Pemakaian & Pergerakan Stretcher' }}
                        </h3>
                        <p style="font-size:.78rem;color:var(--cbm-text-muted);margin-top:.2rem;">
                            Stretcher akan otomatis terlacak titik posisinya di stasiun tujuan (End Point).
                        </p>
                    </div>
                    <button type="button" wire:click="closeTransactionModal" style="background:transparent;border:none;font-size:1.5rem;cursor:pointer;color:var(--cbm-text-muted);">&times;</button>
                </div>

                <form wire:submit.prevent="saveTransaction" style="display:flex;flex-direction:column;gap:1rem;">
                    {{-- Row: Stretcher & Tanggal --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;">
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Pilih Unit Stretcher: *</label>
                            <select wire:model.live="stretcher_id" class="mod-select" style="width:100%;margin-top:.3rem;">
                                <option value="">-- Pilih Unit --</option>
                                @foreach($stretchers as $s)
                                    <option value="{{ $s->id }}">{{ $s->code }} (Posisi: {{ $s->current_station }} - {{ $s->name }})</option>
                                @endforeach
                            </select>
                            @error('stretcher_id') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Tanggal Penerbangan: *</label>
                            <input type="date" wire:model="date" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                            @error('date') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Row: Pesawat & Flight Number --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;">
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Pesawat (A/C Reg): *</label>
                            <input type="text" wire:model="aircraft_registration" placeholder="Misal: PK-LGF / PK-BTI" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;text-transform:uppercase;">
                            @error('aircraft_registration') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">No. Penerbangan: *</label>
                            <input type="text" wire:model="flight_number" placeholder="Misal: JT-380 / ID-6812" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;text-transform:uppercase;">
                            @error('flight_number') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Row: Origin & Destination (End Point) --}}
                    <div style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:.65rem;padding:.85rem;display:grid;grid-template-columns:1fr auto 1fr;gap:.65rem;align-items:center;">
                        <div>
                            <label style="font-size:.75rem;font-weight:700;color:var(--cbm-text);">Titik Awal (Origin Station): *</label>
                            <input type="text" wire:model.live="origin_station" placeholder="CGK" class="mod-search-input mod-input-plain" style="width:100%;text-align:center;font-weight:800;margin-top:.25rem;text-transform:uppercase;">
                            @error('origin_station') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div style="font-size:1.2rem;color:var(--cbm-blue);font-weight:900;padding-top:.85rem;">&rarr;</div>
                        <div>
                            <label style="font-size:.75rem;font-weight:700;color:var(--cbm-text);">Titik Akhir (End Point Tujuan): *</label>
                            <input type="text" wire:model.live="destination_station" placeholder="KNO" class="mod-search-input mod-input-plain" style="width:100%;text-align:center;font-weight:800;color:var(--cbm-green,#10b981);margin-top:.25rem;text-transform:uppercase;">
                            @error('destination_station') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Row: Rute Generated --}}
                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Rute Penerbangan:</label>
                        <input type="text" wire:model="route" placeholder="CGK - KNO" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;text-transform:uppercase;font-weight:700;">
                        @error('route') <span class="mod-field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Row: Penumpang / Pasien --}}
                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Nama Penumpang / Pasien (Siapa yang naik): *</label>
                        <input type="text" wire:model="passenger_name" placeholder="Misal: Budi Santoso / Siti Rahma" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                        @error('passenger_name') <span class="mod-field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Row: Kondisi / Catatan Medis --}}
                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Kondisi Medis / Catatan Khusus:</label>
                        <input type="text" wire:model="medical_notes" placeholder="Misal: Medevac Stroke, butuh oksigen, didampingi dr. Agus" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                    </div>

                    {{-- Row: PIC & Status --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;">
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Petugas / PIC CBM:</label>
                            <input type="text" wire:model="pic_name" placeholder="Nama teknisi" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                        </div>
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Status Pergerakan:</label>
                            <select wire:model="status" class="mod-select" style="width:100%;margin-top:.3rem;">
                                <option value="COMPLETED">Tiba / Selesai (Stretcher Standby di End Point)</option>
                                <option value="IN_TRANSIT">Dalam Penerbangan (In Transit)</option>
                                <option value="CANCELLED">Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Keterangan Tambahan / Nomor Baris Seat:</label>
                        <textarea wire:model="notes" rows="2" placeholder="Misal: Terpasang di seat 28-30 ABC. Bracket aman." class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;resize:vertical;"></textarea>
                    </div>

                    {{-- Actions --}}
                    <div style="display:flex;justify-content:flex-end;gap:.65rem;margin-top:.5rem;border-top:1px solid var(--cbm-card-border);padding-top:.85rem;">
                        <button type="button" wire:click="closeTransactionModal" class="mod-btn-outline">
                            Batal
                        </button>
                        <button type="submit" class="mod-btn-primary" style="padding:.55rem 1.25rem;">
                            Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL 2: FORM MASTER UNIT STRETCHER --}}
    @if($isUnitModalOpen)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:999;padding:1rem;">
            <div class="mod-card" style="width:100%;max-width:32rem;max-height:90vh;overflow-y:auto;padding:1.5rem;box-shadow:0 20px 40px rgba(0,0,0,0.4);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--cbm-card-border);padding-bottom:.85rem;">
                    <div>
                        <h3 style="font-size:1.15rem;font-weight:800;color:var(--cbm-text);">
                            {{ $editingUnitId ? 'Edit Unit Stretcher' : 'Tambah Unit Stretcher Baru' }}
                        </h3>
                    </div>
                    <button type="button" wire:click="closeUnitModal" style="background:transparent;border:none;font-size:1.5rem;cursor:pointer;color:var(--cbm-text-muted);">&times;</button>
                </div>

                <form wire:submit.prevent="saveUnit" style="display:flex;flex-direction:column;gap:1rem;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;">
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Kode Unit: *</label>
                            <input type="text" wire:model="unit_code" placeholder="STR-01" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;text-transform:uppercase;font-family:monospace;font-weight:800;">
                            @error('unit_code') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Tipe Armada:</label>
                            <input type="text" wire:model="unit_aircraft_type" placeholder="B737 / A320 / A330" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;text-transform:uppercase;">
                        </div>
                    </div>

                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Nama Stretcher: *</label>
                        <input type="text" wire:model="unit_name" placeholder="Misal: Stretcher Boeing 737 Unit 1" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                        @error('unit_name') <span class="mod-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;">
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Part Number:</label>
                            <input type="text" wire:model="unit_part_number" placeholder="PN..." class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                        </div>
                        <div>
                            <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Serial Number:</label>
                            <input type="text" wire:model="unit_serial_number" placeholder="SN..." class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;">
                        <div>
                            <label style="font-size:.75rem;font-weight:700;color:var(--cbm-text);">Lokasi / Station: *</label>
                            <input type="text" wire:model="unit_current_station" placeholder="CGK" class="mod-search-input mod-input-plain" style="width:100%;text-align:center;font-weight:800;margin-top:.3rem;text-transform:uppercase;">
                            @error('unit_current_station') <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="font-size:.75rem;font-weight:700;color:var(--cbm-text);">Status:</label>
                            <select wire:model="unit_status" class="mod-select" style="width:100%;margin-top:.3rem;">
                                <option value="AVAILABLE">Ready</option>
                                <option value="IN_USE">In Use</option>
                                <option value="MAINTENANCE">Perbaikan</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:.75rem;font-weight:700;color:var(--cbm-text);">Kondisi:</label>
                            <select wire:model="unit_condition" class="mod-select" style="width:100%;margin-top:.3rem;">
                                <option value="GOOD">Baik</option>
                                <option value="FAIR">Cukup</option>
                                <option value="DAMAGED">Rusak</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:var(--cbm-text);">Catatan:</label>
                        <textarea wire:model="unit_notes" rows="2" class="mod-search-input mod-input-plain" style="width:100%;text-align:left;margin-top:.3rem;resize:vertical;"></textarea>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:.65rem;margin-top:.5rem;border-top:1px solid var(--cbm-card-border);padding-top:.85rem;">
                        <button type="button" wire:click="closeUnitModal" class="mod-btn-outline">Batal</button>
                        <button type="submit" class="mod-btn-primary" style="padding:.55rem 1.25rem;">Simpan Unit</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
