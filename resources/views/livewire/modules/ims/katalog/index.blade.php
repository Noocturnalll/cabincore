<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">IMS Tracker</span>
            <h1 class="mod-title">Katalog Barang</h1>
            <p class="mod-subtitle">Daftar semua inventaris barang, tools, dan aset.</p>
        </div>
        
        <div class="mod-actions">
            <a href="{{ route('ims.requests') }}" wire:navigate class="mod-btn-outline" style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z" /></svg>
                Picklist 
                @if(count($picklist) > 0)
                <span style="background: var(--cbm-blue); color: white; border-radius: 62.4375rem; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold; margin-left: 0.25rem;">{{ count($picklist) }}</span>
                @endif
            </a>
            @can('ims.item.manage')
            <button class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Barang Baru
            </button>
            @endcan
        </div>
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari PN, Nama, atau Deskripsi..." class="mod-search-input" wire:model.live.debounce.300ms="search">
            </div>
            
            <div style="display: flex; gap: 0.5rem;">
                <select wire:model.live="categoryId" class="mod-search-input" style="padding-left: 0.5rem; min-width: 9.375rem;">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="status" class="mod-search-input" style="padding-left: 0.5rem; min-width: 7.5rem;">
                    <option value="">Semua Status</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Part Number</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th style="text-align: center;">Tersedia</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $available = ($item->total_on_hand ?? 0) - ($item->total_reserved ?? 0);
                            
                            $statusLabel = 'Tersedia';
                            $statusClass = 'mod-badge-closed'; // green
                            
                            if (!$item->is_active) {
                                $statusLabel = 'Nonaktif';
                                $statusClass = 'mod-badge-inactive'; // grey (need to define in css, use default for now)
                            } elseif ($available == 0) {
                                $statusLabel = 'Habis';
                                $statusClass = 'mod-badge-open'; // red
                            } elseif ($available <= $item->min_stock) {
                                $statusLabel = 'Menipis';
                                $statusClass = 'mod-badge-progress'; // yellow
                            }
                        @endphp
                        <tr>
                            <td class="mod-aircraft-name">{{ $item->part_number }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $item->name }}</div>
                                <div class="mod-aircraft-sub" style="max-width: 18.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->description }}</div>
                            </td>
                            <td>{{ $item->category->name ?? '-' }}</td>
                            <td style="text-align: center;">
                                <div class="fw-bold" style="font-size: 1.1rem;">{{ $available }}</div>
                                <div class="mod-aircraft-sub" style="margin-bottom: 0.25rem;">{{ $item->unit->code ?? '' }}</div>
                                @if($available > 0)
                                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25rem; margin-top: 0.25rem;">
                                    @foreach($item->stocks->where('qty_on_hand', '>', 0)->take(2) as $stock)
                                        <span style="font-size: 0.65rem; background: var(--cbm-nav-hover); color: var(--cbm-text-muted); padding: 0.1rem 0.35rem; border-radius: 4px; white-space: nowrap;">{{ $stock->location->name ?? 'N/A' }}</span>
                                    @endforeach
                                    @if($item->stocks->where('qty_on_hand', '>', 0)->count() > 2)
                                        <span style="font-size: 0.65rem; background: var(--cbm-nav-hover); color: var(--cbm-text-muted); padding: 0.1rem 0.35rem; border-radius: 4px;">+{{ $item->stocks->where('qty_on_hand', '>', 0)->count() - 2 }}</span>
                                    @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $statusClass }}">
                                    @if($statusLabel != 'Nonaktif') <span class="mod-badge-dot"></span> @endif 
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button class="mod-action-btn">Detail</button>
                                @if($item->is_active && $available > 0)
                                <button class="mod-btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;" wire:click="addToPicklist({{ $item->id }})">
                                    + Picklist
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mod-empty">
                                    <div class="mod-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    </div>
                                    <h4 class="mod-empty-title">Barang Tidak Ditemukan</h4>
                                    <p class="mod-empty-sub">Coba sesuaikan kata kunci pencarian atau filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mod-pagination">
            {{ $items->links('pagination::tailwind') }}
        </div>
    </div>
</div>

