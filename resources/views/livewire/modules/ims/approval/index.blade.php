<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-red">IMS Tracker</span>
            <h1 class="mod-title">Persetujuan Transaksi</h1>
            <p class="mod-subtitle">Tinjau dan proses permintaan barang, penambahan stok, atau penyesuaian.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #10b981;">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #ef4444;">
            {{ session('error') }}
        </div>
    @endif

    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--cbm-border); padding-bottom: 0;">
        <button type="button" wire:click="$set('activeTab', 'pending')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'pending' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'pending' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Menunggu Persetujuan
        </button>
        <button type="button" wire:click="$set('activeTab', 'history')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'history' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'history' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Riwayat Persetujuan
        </button>
    </div>

    <div class="mod-card">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>Pemohon</th>
                        <th>Tipe / Tujuan</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td>
                                <div class="mod-aircraft-name">{{ $trx->code }}</div>
                                <div class="mod-aircraft-sub">{{ $trx->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                <div>{{ $trx->requester->name ?? 'System/User' }}</div>
                            </td>
                            <td>
                                <div><span style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">{{ $trx->type }}</span> - {{ $trx->usage_type ?? 'N/A' }}</div>
                                <div class="mod-aircraft-sub" style="max-width: 15.625rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $trx->purpose_description }}</div>
                            </td>
                            <td>
                                <div>{{ $trx->items->count() }} jenis barang</div>
                                <div class="mod-aircraft-sub">Total Qty: {{ $trx->items->sum('qty') }}</div>
                            </td>
                            <td>
                                @if($trx->status == 'pending_approval')
                                    <span class="mod-badge-progress"><span class="mod-badge-dot"></span> Pending</span>
                                @elseif($trx->status == 'approved')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot"></span> Approved</span>
                                @elseif($trx->status == 'rejected')
                                    <span class="mod-badge-open"><span class="mod-badge-dot"></span> Rejected</span>
                                @endif
                            </td>
                            <td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button class="mod-action-btn">Detail</button>
                                @if($trx->status == 'pending_approval' && auth()->user()?->can('ims.approval.act'))
                                    <button wire:click="approve({{ $trx->id }})" class="mod-action-btn" style="color: #10b981; border-color: #10b981;" onclick="confirm('Setujui transaksi ini?') || event.stopImmediatePropagation()">Setujui</button>
                                    <button wire:click="confirmReject({{ $trx->id }})" class="mod-action-btn" style="color: #ef4444; border-color: #ef4444;">Tolak</button>
                                @endif
                            </td>
                        </tr>
                        
                        @if($selectedTransactionId == $trx->id)
                        <tr>
                            <td colspan="6" style="background: #fef2f2; padding: 1rem;">
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <label style="font-weight: 600; font-size: 0.875rem;">Alasan Penolakan untuk {{ $trx->code }}:</label>
                                    <textarea wire:model="rejectReason" class="mod-search-input" rows="2" style="width: 100%; border-color: #ef4444;"></textarea>
                                    @error('rejectReason') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem;">
                                        <button wire:click="$set('selectedTransactionId', null)" class="mod-action-btn">Batal</button>
                                        <button wire:click="reject" class="mod-btn-primary" style="background: #ef4444;">Konfirmasi Tolak</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Tidak ada data persetujuan.</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mod-pagination">
            {{ $transactions->links('pagination::tailwind') }}
        </div>
    </div>
</div>

