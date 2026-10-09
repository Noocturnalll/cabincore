<div>
    <x-master.page-header title="Persetujuan Transaksi" subtitle="Setujui permintaan barang, catat serah terima, dan terima pengembalian pinjaman." accent="red" eyebrow="IMS Tracker" />

    <x-flash />

    @if($counts['overdue'] > 0)
        <div class="mod-hint mod-hint-warn" role="alert">
            <strong>{{ $counts['overdue'] }} pinjaman lewat tanggal kembali.</strong>
            <a href="#" wire:click.prevent="setTab('loans')" style="color:inherit;text-decoration:underline;">Lihat pinjaman aktif</a>
        </div>
    @endif

    <div class="mod-card mod-card-accent-red">
        <div class="cbm-tabs">
            <button type="button" wire:click="setTab('pending')" class="cbm-tab {{ $activeTab === 'pending' ? 'active' : '' }}">Menunggu Persetujuan <span class="cbm-tab-count">{{ $counts['pending'] }}</span></button>
            <button type="button" wire:click="setTab('loans')" class="cbm-tab {{ $activeTab === 'loans' ? 'active' : '' }}">Pinjaman Aktif <span class="cbm-tab-count">{{ $counts['loans'] }}</span></button>
            <button type="button" wire:click="setTab('history')" class="cbm-tab {{ $activeTab === 'history' ? 'active' : '' }}">Riwayat</button>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>DOKUMEN</th>
                        <th>PEMOHON</th>
                        <th>TIPE / TUJUAN</th>
                        <th>ITEM</th>
                        <th>{{ $activeTab === 'loans' ? 'KEMBALI' : 'STATUS' }}</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody x-data="{ openId: null }">
                    @forelse($transactions as $trx)
                        @php
                            $isOutApproved = $trx->type === 'out' && $trx->status === 'approved';
                            $isLoan = $isOutApproved && $trx->usage_type === 'loan';
                            $overdue = $isLoan && ! $trx->is_returned && $trx->expected_return_date && $trx->expected_return_date->isBefore(today());
                        @endphp
                        <tr wire:key="trx-{{ $trx->id }}">
                            <td>
                                <div class="mod-aircraft-name">{{ $trx->code }}</div>
                                <div class="mod-aircraft-sub">{{ $trx->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td>{{ $trx->requester->name ?? 'System/User' }}</td>
                            <td>
                                <div><span style="font-weight:700;text-transform:uppercase;font-size:.75rem;">{{ $trx->type }}</span> &middot; {{ $trx->usage_type ?? 'N/A' }}</div>
                                <div class="mod-aircraft-sub" style="max-width:15.6rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $trx->purpose_description }}</div>
                            </td>
                            <td>
                                <div>{{ $trx->items->count() }} jenis barang</div>
                                <div class="mod-aircraft-sub">Total qty: {{ $trx->items->sum('qty') }}</div>
                            </td>
                            <td>
                                @if($activeTab === 'loans')
                                    <span class="{{ $overdue ? 'mod-badge-open' : 'mod-badge-progress' }}">
                                        {{ $trx->expected_return_date?->format('d M Y') ?? '-' }}
                                    </span>
                                    @if($overdue)<div class="mod-aircraft-sub" style="color:#ef4444;">Terlambat {{ $trx->expected_return_date->diffInDays(today()) }} hari</div>@endif
                                @elseif($trx->status === 'pending_approval')
                                    <span class="mod-badge-progress"><span class="mod-badge-dot"></span> Pending</span>
                                @elseif($trx->status === 'approved')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot"></span> Approved</span>
                                    @if($isOutApproved)
                                        <div class="mod-aircraft-sub">
                                            {{ $trx->picked_up_at ? 'Diserahkan ke '.$trx->picked_up_by_name.' ('.$trx->picked_up_at->format('d M H:i').')' : 'Belum diserahterimakan' }}
                                        </div>
                                    @endif
                                    @if($isLoan)
                                        <div class="mod-aircraft-sub" style="{{ $overdue ? 'color:#ef4444;' : '' }}">{{ $trx->is_returned ? 'Sudah dikembalikan' : 'Pinjaman, kembali '.($trx->expected_return_date?->format('d M Y') ?? '-') }}</div>
                                    @endif
                                @elseif($trx->status === 'rejected')
                                    <span class="mod-badge-open"><span class="mod-badge-dot"></span> Rejected</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap;">
                                    <button type="button" class="mod-action-btn" @click="openId = openId === {{ $trx->id }} ? null : {{ $trx->id }}"><span x-text="openId === {{ $trx->id }} ? 'Tutup' : 'Detail'"></span></button>

                                    @if($trx->status === 'pending_approval')
                                        @can('ims.approval.act')
                                            @if((int) $trx->requested_by === (int) auth()->id() && ! config('ims.approval.allow_self_approval'))
                                                <span class="mod-aircraft-sub" title="Permintaan Anda sendiri harus disetujui orang lain">Menunggu approver lain</span>
                                            @else
                                                <button type="button" wire:click="approve({{ $trx->id }})" wire:confirm="Setujui transaksi {{ $trx->code }}?" class="mod-action-btn" style="color:#10b981;border-color:rgba(16,185,129,.4);">Setujui</button>
                                            @endif
                                            <button type="button" wire:click="confirmReject({{ $trx->id }})" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Tolak</button>
                                        @endcan
                                    @endif

                                    @can('ims.stock.handover')
                                        @if($isOutApproved && ! $trx->picked_up_at)
                                            <button type="button" wire:click="openHandover({{ $trx->id }})" class="mod-action-btn">Serah terima</button>
                                        @endif
                                        @if($isLoan && ! $trx->is_returned)
                                            <button type="button" wire:click="receiveLoan({{ $trx->id }})" wire:confirm="Terima pengembalian {{ $trx->code }}? Stok akan bertambah." class="mod-action-btn" style="color:#10b981;border-color:rgba(16,185,129,.4);">Terima kembali</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>

                        <tr x-show="openId === {{ $trx->id }}" x-cloak wire:key="trx-detail-{{ $trx->id }}">
                            <td colspan="6" style="background:var(--cbm-nav-hover);">
                                <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--cbm-text-muted);margin-bottom:.5rem;">Rincian barang</div>
                                @if($trx->purpose_description)<div style="font-size:.8125rem;margin-bottom:.5rem;">{{ $trx->purpose_description }}</div>@endif
                                <div style="display:flex;flex-direction:column;gap:.25rem;">
                                    @foreach($trx->items as $line)
                                        <div style="font-size:.8125rem;">
                                            <strong>{{ $line->qty }}&times;</strong> {{ $line->item->name ?? '-' }}
                                            <span style="color:var(--cbm-text-muted);">PN {{ $line->item->part_number ?? '-' }} &middot; {{ $line->location->name ?? '-' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if($trx->handover_note)<div class="mod-aircraft-sub" style="margin-top:.5rem;">Catatan serah terima: {{ $trx->handover_note }}</div>@endif
                                @if($trx->status === 'rejected' && $trx->rejected_reason)
                                    <div class="mod-hint mod-hint-warn" style="margin:.75rem 0 0;">Ditolak: {{ $trx->rejected_reason }}</div>
                                @endif
                            </td>
                        </tr>

                        @if($selectedTransactionId == $trx->id)
                            <tr>
                                <td colspan="6" style="background:var(--cbm-nav-hover);padding:1rem;">
                                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                                        <label class="cbm-form-label" for="rej-{{ $trx->id }}">Alasan penolakan untuk {{ $trx->code }} *</label>
                                        <textarea id="rej-{{ $trx->id }}" wire:model="rejectReason" class="cbm-form-textarea" rows="2" style="border-color:#ef4444;"></textarea>
                                        @error('rejectReason') <span class="mod-field-error">{{ $message }}</span> @enderror
                                        <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                                            <button type="button" wire:click="$set('selectedTransactionId', null)" class="mod-btn-outline">Batal</button>
                                            <button type="button" wire:click="reject" class="mod-btn-primary" style="background:#ef4444;">Konfirmasi tolak</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ ['pending' => 'Tidak ada yang menunggu persetujuan', 'loans' => 'Tidak ada pinjaman aktif', 'history' => 'Belum ada riwayat'][$activeTab] }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="mod-pagination">{{ $transactions->links('pagination::tailwind') }}</div>
        @endif
    </div>

    {{-- Serah terima --}}
    <x-master.modal :show="(bool) $handoverId" title="Serah Terima Barang" subtitle="Catat siapa yang mengambil barang." submit="saveHandover" close="closeHandover" max-width="26rem" submit-label="Simpan serah terima">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ho-name">Nama penerima *</label>
            <input id="ho-name" type="text" wire:model="picked_up_by_name" class="cbm-form-input" maxlength="100">
            @error('picked_up_by_name') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="ho-note">Catatan</label>
            <textarea id="ho-note" wire:model="handover_note" class="cbm-form-textarea" maxlength="1000"></textarea>
            @error('handover_note') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
