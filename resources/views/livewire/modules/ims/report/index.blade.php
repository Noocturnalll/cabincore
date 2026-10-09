<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">IMS Tracker</span>
            <h1 class="mod-title">Laporan & Analitik Stok</h1>
            <p class="mod-subtitle">Lihat riwayat pergerakan dan tabel pivot matriks stok barang.</p>
        </div>
    </div>

    <div class="cbm-tabs" style="margin-bottom:1rem;padding-top:0;">
        <button type="button" wire:click="$set('activeTab', 'mutasi')" class="cbm-tab {{ $activeTab == 'mutasi' ? 'active' : '' }}">Log Mutasi (Keluar/Masuk)</button>
        <button type="button" wire:click="$set('activeTab', 'pivot_stock')" class="cbm-tab {{ $activeTab == 'pivot_stock' ? 'active' : '' }}">Pivot: Stok per Lokasi</button>
        <button type="button" wire:click="$set('activeTab', 'pivot_trx')" class="cbm-tab {{ $activeTab == 'pivot_trx' ? 'active' : '' }}">Pivot: Tren Transaksi 6 Bulan</button>
    </div>

    @if($activeTab == 'mutasi')
    <div wire:key="tab-mutasi" class="mod-card">
        <div class="mod-toolbar">
            <div style="display: flex; gap: 0.5rem; width: 100%; flex-wrap: wrap;">
                <select wire:model.live="type" class="mod-search-input" style="padding-left: 0.5rem; min-width: 9.375rem;">
                    <option value="">Semua Tipe</option>
                    <option value="in">Barang Masuk (In)</option>
                    <option value="out">Barang Keluar (Out)</option>
                    <option value="transfer_out">Transfer Keluar</option>
                    <option value="transfer_in">Transfer Masuk</option>
                    <option value="adjust_plus">Penyesuaian (+)</option>
                    <option value="adjust_minus">Penyesuaian (-)</option>
                </select>
                <input type="date" wire:model.live="startDate" class="mod-search-input">
                <input type="date" wire:model.live="endDate" class="mod-search-input">
                <div style="margin-left: auto;">
                    @can('ims.report.export')
                    <button type="button" class="mod-btn-primary" wire:click="exportMutasi" wire:loading.attr="disabled" wire:target="exportMutasi">
                        <span wire:loading.remove wire:target="exportMutasi">Ekspor CSV</span>
                        <span wire:loading wire:target="exportMutasi"><span class="cbm-spinner"></span> Mengekspor...</span>
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Tipe / Lokasi</th>
                        <th>Sblm -> Ssdh</th>
                        <th>Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                        <tr>
                            <td>{{ $mov->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $mov->item->name ?? '-' }}</div>
                                <div class="mod-aircraft-sub">PN: {{ $mov->item->part_number ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="mod-badge-progress">{{ $mov->movement_type }}</span>
                                <div class="mod-aircraft-sub">{{ $mov->location->name ?? '-' }}</div>
                            </td>
                            <td>{{ $mov->balance_before }} -> {{ $mov->balance_after }}</td>
                            <td>
                                <strong style="color: {{ $mov->qty_change > 0 ? '#10b981' : ($mov->qty_change < 0 ? '#ef4444' : 'inherit') }}">
                                    {{ $mov->qty_change > 0 ? '+' : '' }}{{ $mov->qty_change }}
                                </strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Tidak ada data.</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mod-pagination">
            {{ $movements->links('pagination::tailwind') }}
        </div>
    </div>
    @endif

    @if($activeTab == 'pivot_stock')
    <div wire:key="tab-pivot-stock" class="mod-card">
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="max-width: 18.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari PN atau Nama..." class="mod-search-input" wire:model.live.debounce.300ms="searchPivot">
            </div>
            <div style="margin-left: auto;">
                <button class="mod-btn-outline">Ekspor Excel</button>
            </div>
        </div>

        <div class="mod-table-wrap" style="overflow-x: auto;">
            <table class="mod-table" style="min-width: max-content;">
                <thead>
                    <tr>
                        <th style="position: sticky; left: 0; background: var(--cbm-card-bg); z-index: 10;">Part Number</th>
                        <th style="position: sticky; left: 9.375rem; background: var(--cbm-card-bg); z-index: 10;">Nama Barang</th>
                        <th style="text-align: center;">Total Tersedia</th>
                        @foreach($locations as $loc)
                            <th style="text-align: center; border-left: 1px solid var(--cbm-border);">{{ $loc->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($pivotStockItems as $item)
                        <tr>
                            <td class="mod-aircraft-name" style="position: sticky; left: 0; background: var(--cbm-card-bg); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.05);">{{ $item->part_number }}</td>
                            <td style="position: sticky; left: 9.375rem; background: var(--cbm-card-bg); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.05);">
                                <div class="mod-aircraft-name" style="max-width: 12.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->name }}</div>
                            </td>
                            <td style="text-align: center; font-weight: bold;">
                                {{ $item->stocks->sum('qty_on_hand') }}
                            </td>
                            @foreach($locations as $loc)
                                @php
                                    $stockQty = $item->stocks->where('location_id', $loc->id)->sum('qty_on_hand');
                                @endphp
                                <td style="text-align: center; border-left: 1px solid var(--cbm-border); color: {{ $stockQty > 0 ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
                                    {{ $stockQty > 0 ? $stockQty : '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($locations) }}">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Tidak ada data barang.</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mod-pagination">
            {{ $pivotStockItems->links('pagination::tailwind') }}
        </div>
    </div>
    @endif

    @if($activeTab == 'pivot_trx')
    <div wire:key="tab-pivot-trx" class="mod-card">
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="max-width: 18.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari PN atau Nama..." class="mod-search-input" wire:model.live.debounce.300ms="searchPivot">
            </div>
            <div style="margin-left: auto;">
                <button class="mod-btn-outline">Ekspor Excel</button>
            </div>
        </div>

        <div class="mod-table-wrap" style="overflow-x: auto;">
            <table class="mod-table" style="min-width: max-content;">
                <thead>
                    <tr>
                        <th rowspan="2" style="position: sticky; left: 0; background: var(--cbm-card-bg); z-index: 10; border-bottom: 2px solid var(--cbm-border);">Part Number</th>
                        <th rowspan="2" style="position: sticky; left: 9.375rem; background: var(--cbm-card-bg); z-index: 10; border-bottom: 2px solid var(--cbm-border);">Nama Barang</th>
                        @foreach($months as $m)
                            <th colspan="2" style="text-align: center; border-left: 1px solid var(--cbm-border); color: var(--cbm-text-muted);">{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M Y') }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($months as $m)
                            <th style="text-align: center; border-left: 1px solid var(--cbm-border); font-size: 0.75rem; color: #10b981;">Masuk</th>
                            <th style="text-align: center; border-left: 1px solid var(--cbm-border); font-size: 0.75rem; color: #ef4444;">Keluar</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($pivotTrxItems as $item)
                        <tr>
                            <td class="mod-aircraft-name" style="position: sticky; left: 0; background: var(--cbm-card-bg); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.05);">{{ $item->part_number }}</td>
                            <td style="position: sticky; left: 9.375rem; background: var(--cbm-card-bg); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.05);">
                                <div class="mod-aircraft-name" style="max-width: 12.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->name }}</div>
                            </td>
                            @foreach($months as $m)
                                @php
                                    $in = $item->trx_data[$m]['in'] ?? 0;
                                    $out = $item->trx_data[$m]['out'] ?? 0;
                                @endphp
                                <td style="text-align: center; border-left: 1px solid var(--cbm-border); color: {{ $in > 0 ? '#10b981' : 'var(--cbm-text-muted)' }};">
                                    {{ $in > 0 ? $in : '-' }}
                                </td>
                                <td style="text-align: center; border-left: 1px solid var(--cbm-border); color: {{ $out > 0 ? '#ef4444' : 'var(--cbm-text-muted)' }};">
                                    {{ $out > 0 ? $out : '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + (count($months) * 2) }}">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Tidak ada data.</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mod-pagination">
            {{ $pivotTrxItems->links('pagination::tailwind') }}
        </div>
    </div>
    @endif
</div>
