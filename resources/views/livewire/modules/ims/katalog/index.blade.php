<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">IMS Tracker</span>
            <h1 class="mod-title">Katalog Barang</h1>
            <p class="mod-subtitle">Daftar semua inventaris barang, tools, dan aset.</p>
        </div>

        <div class="mod-actions">
            <a href="{{ route('ims.requests') }}" wire:navigate class="mod-btn-outline" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/></svg>
                Picklist
                @if(count($picklist) > 0)
                    <span class="cbm-tab-count" style="background:var(--cbm-blue);color:#fff;border-color:transparent;">{{ count($picklist) }}</span>
                @endif
            </a>
            @can('ims.item.manage')
                <a href="{{ route('ims.master.items') }}" wire:navigate class="mod-btn-primary" style="text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                    Kelola Barang
                </a>
            @endcan
        </div>
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari PN, nama, atau deskripsi..." aria-label="Cari barang">
                </div>
                <label class="mod-field mod-field-labelled"><span>Kategori</span>
                    <select wire:model.live="categoryId" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                    </select>
                </label>
                <label class="mod-field mod-field-labelled"><span>Lokasi</span>
                    <select wire:model.live="locationId" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        @foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                    </select>
                </label>
                <label class="mod-field mod-field-labelled"><span>Status stok</span>
                    <select wire:model.live="status" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        <option value="available">Tersedia</option>
                        <option value="low">Menipis</option>
                        <option value="empty">Habis</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </label>
                @if($search || $categoryId || $locationId || $status)
                    <button type="button" wire:click="clearFilters" class="mod-btn-outline mod-btn-sm">Reset filter</button>
                @endif
                <span wire:loading wire:target="search, categoryId, locationId, status" class="cbm-spinner" aria-label="Memuat"></span>
            </div>
            <div class="mod-meta">
                <span class="mod-record-count">{{ number_format($items->total()) }} barang</span>
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Part Number</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th style="text-align:center;">Tersedia</th>
                        <th>Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody x-data="{ openId: null }">
                    @forelse($items as $item)
                        @php
                            $available = ($item->total_on_hand ?? 0) - ($item->total_reserved ?? 0);
                            $statusLabel = 'Tersedia';
                            $statusClass = 'mod-badge-closed';
                            if (! $item->is_active) {
                                $statusLabel = 'Nonaktif';
                                $statusClass = 'mod-badge-inactive';
                            } elseif ($available <= 0) {
                                $statusLabel = 'Habis';
                                $statusClass = 'mod-badge-open';
                            } elseif ($available <= $item->min_stock) {
                                $statusLabel = 'Menipis';
                                $statusClass = 'mod-badge-progress';
                            }
                            $stocksWithQty = $item->stocks->where('qty_on_hand', '>', 0);
                        @endphp
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="mod-aircraft-name">{{ $item->part_number }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $item->name }}</div>
                                <div class="mod-aircraft-sub" style="max-width:18.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item->description }}</div>
                            </td>
                            <td>{{ $item->category->name ?? '-' }}</td>
                            <td style="text-align:center;">
                                <div style="font-weight:800;font-size:1.1rem;">{{ $available }}</div>
                                <div class="mod-aircraft-sub">{{ $item->unit->code ?? '' }}@if($item->min_stock) &middot; min {{ $item->min_stock }}@endif</div>
                            </td>
                            <td>
                                <span class="{{ $statusClass }}">
                                    @if($statusLabel !== 'Nonaktif')<span class="mod-badge-dot"></span>@endif
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    <button type="button" class="mod-action-btn" @click="openId = openId === {{ $item->id }} ? null : {{ $item->id }}" :aria-expanded="openId === {{ $item->id }}">
                                        <span x-text="openId === {{ $item->id }} ? 'Tutup' : 'Detail'"></span>
                                    </button>
                                    @if($item->is_active && $available > 0)
                                        <button type="button" class="mod-btn-primary" style="padding:.3rem .75rem;font-size:.8rem;" wire:click="addToPicklist({{ $item->id }})" wire:loading.attr="disabled" wire:target="addToPicklist({{ $item->id }})">
                                            + Picklist
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr x-show="openId === {{ $item->id }}" x-cloak wire:key="item-detail-{{ $item->id }}">
                            <td colspan="6" style="background:var(--cbm-nav-hover);">
                                <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--cbm-text-muted);margin-bottom:.5rem;">Stok per lokasi</div>
                                @if($stocksWithQty->isEmpty())
                                    <span style="color:var(--cbm-text-muted);font-size:.8125rem;">Belum ada stok di lokasi mana pun.</span>
                                @else
                                    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                                        @foreach($stocksWithQty as $stock)
                                            <span class="mod-record-count">{{ $stock->location->name ?? '-' }}: <strong>{{ $stock->qty_on_hand }}</strong>@if($stock->qty_reserved) <span style="color:var(--cbm-text-muted);">({{ $stock->qty_reserved }} dikunci)</span>@endif</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Barang Tidak Ditemukan</h4>
                                    <p class="mod-empty-sub">Coba sesuaikan kata kunci pencarian atau filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="mod-pagination">{{ $items->links('pagination::tailwind') }}</div>
        @endif
    </div>
</div>
