<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-red">IMS Tracker</span>
            <h1 class="mod-title">Rak Perbaikan (Repair)</h1>
            <p class="mod-subtitle">Pantau barang yang rusak dari tahap menunggu, diproses, hingga selesai.</p>
        </div>
    </div>

    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--cbm-border); padding-bottom: 0;">
        <button type="button" wire:click="$set('activeTab', 'waiting')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'waiting' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'waiting' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Rak Menunggu
        </button>
        <button type="button" wire:click="$set('activeTab', 'process')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'process' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'process' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Rak Diproses
        </button>
        <button type="button" wire:click="$set('activeTab', 'completed')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'completed' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'completed' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Rak Selesai
        </button>
    </div>

    <div class="mod-card">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Kode Repair</th>
                        <th>Barang</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->repair_code }}</td>
                            <td>
                                <div class="mod-aircraft-name">{{ $item->item->name ?? '-' }}</div>
                                <div class="mod-aircraft-sub">SN: {{ $item->serial_number ?? 'N/A' }}</div>
                            </td>
                            <td>{{ $item->qty }}</td>
                            <td><span class="mod-badge-progress">{{ ucfirst($activeTab) }}</span></td>
                            <td style="text-align: right;">
                                <button class="mod-action-btn">Proses</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Rak Kosong.</h4>
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

