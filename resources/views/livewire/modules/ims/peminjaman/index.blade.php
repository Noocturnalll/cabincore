<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-orange">IMS Tracker</span>
            <h1 class="mod-title">Pengeluaran Barang</h1>
            <p class="mod-subtitle">Ajukan permintaan pengeluaran barang dari picklist dan pantau statusnya.</p>
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
        <button type="button" wire:click="$set('activeTab', 'picklist')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'picklist' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'picklist' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Picklist ({{ count($picklist) }})
        </button>
        <button type="button" wire:click="$set('activeTab', 'history')" style="padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid {{ $activeTab == 'history' ? 'var(--cbm-blue)' : 'transparent' }}; color: {{ $activeTab == 'history' ? 'var(--cbm-text)' : 'var(--cbm-text-muted)' }};">
            Riwayat Permintaan Saya
        </button>
    </div>

    @if($activeTab == 'picklist')
    <div wire:key="tab-picklist" class="mod-card mod-card-accent-orange" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; background: transparent; border: none; box-shadow: none; padding: 0;">
        
        <!-- Left: Items -->
        <div class="mod-card" style="padding: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Daftar Barang (Picklist)</h3>
            
            @if(count($picklist) > 0)
                <div class="mod-table-wrap">
                    <table class="mod-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Lokasi Sumber</th>
                                <th style="width: 100px;">Qty</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($picklist as $id => $item)
                                <tr>
                                    <td>
                                        <div class="mod-aircraft-name">{{ $item['name'] }}</div>
                                        <div class="mod-aircraft-sub">PN: {{ $item['part_number'] }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $item['location_name'] ?? 'Pilih Lokasi' }}</div>
                                        <div class="mod-aircraft-sub">Tersedia: {{ $item['available'] ?? 0 }} {{ $item['unit'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <input type="number" min="1" max="{{ $item['available'] ?? 1 }}" value="{{ $item['qty'] }}" 
                                            wire:change="updateQty({{ $id }}, $event.target.value)"
                                            class="mod-search-input" style="width: 80px; padding: 0.25rem 0.5rem; text-align: center;">
                                    </td>
                                    <td style="text-align: right;">
                                        <button wire:click="removeItem({{ $id }})" style="color: #ef4444; background: none; border: none; cursor: pointer;">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mod-empty" style="padding: 3rem 1rem;">
                    <div class="mod-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                    </div>
                    <h4 class="mod-empty-title">Picklist Kosong</h4>
                    <p class="mod-empty-sub">Silakan tambahkan barang dari Katalog Barang terlebih dahulu.</p>
                    <a href="{{ route('ims.catalog') }}" wire:navigate class="mod-btn-primary" style="display: inline-block; margin-top: 1rem;">Ke Katalog Barang</a>
                </div>
            @endif
        </div>

        <!-- Right: Form -->
        <div class="mod-card" style="padding: 1.5rem; align-self: start;">
            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1.5rem;">Form Pengajuan</h3>
            
            <form wire:submit.prevent="submitRequest">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Tujuan Penggunaan <span style="color: red;">*</span></label>
                    <textarea wire:model="purpose_description" class="mod-search-input" rows="3" style="width: 100%; border-radius: var(--cbm-radius-md);" placeholder="Contoh: Penggantian part rusak pada pesawat PK-ABC... (min 10 karakter)"></textarea>
                    @error('purpose_description') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Sifat Pengajuan <span style="color: red;">*</span></label>
                    <select wire:model.live="usage_type" class="mod-search-input" style="width: 100%;">
                        <option value="consume">Habis Pakai (Consume)</option>
                        <option value="loan">Pinjam (Harus Kembali)</option>
                    </select>
                </div>

                @if($usage_type == 'loan')
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Tgl Harapan Kembali <span style="color: red;">*</span></label>
                    <input type="date" wire:model="expected_return_date" class="mod-search-input" style="width: 100%;">
                    @error('expected_return_date') <span style="color: red; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                @endif

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">No. Referensi (WO/Task Card)</label>
                    <input type="text" wire:model="reference_no" class="mod-search-input" style="width: 100%;" placeholder="Opsional">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Registrasi Pesawat</label>
                    <input type="text" wire:model="aircraft_registration" class="mod-search-input" style="width: 100%;" placeholder="Opsional (mis. PK-ABC)">
                </div>

                <button type="submit" class="mod-btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem;" @if(count($picklist) == 0) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>
                    Ajukan Permintaan
                </button>
                <p style="font-size: 0.75rem; color: var(--cbm-text-muted); text-align: center; margin-top: 0.75rem;">
                    Barang akan otomatis dikunci (reserved) setelah pengajuan berhasil.
                </p>
            </form>
        </div>
    </div>
    @endif

    @if($activeTab == 'history')
    <div wire:key="tab-history" class="mod-card">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>No. Dokumen</th>
                        <th>Tgl Pengajuan</th>
                        <th>Tujuan</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $trx)
                        <tr>
                            <td class="mod-aircraft-name">{{ $trx->code }}</td>
                            <td>{{ $trx->requested_at->format('d M Y H:i') }}</td>
                            <td>
                                <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $trx->purpose_description }}</div>
                                <div class="mod-aircraft-sub">{{ $trx->items->count() }} item</div>
                            </td>
                            <td>
                                @if($trx->status == 'pending_approval')
                                    <span class="mod-badge-progress"><span class="mod-badge-dot"></span> Menunggu Persetujuan</span>
                                @elseif($trx->status == 'approved')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot"></span> Disetujui</span>
                                @elseif($trx->status == 'rejected')
                                    <span class="mod-badge-open"><span class="mod-badge-dot"></span> Ditolak</span>
                                @else
                                    <span class="mod-badge-inactive">{{ ucfirst($trx->status) }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button class="mod-action-btn">Detail</button>
                                @if(in_array($trx->status, ['draft', 'pending_approval']))
                                <button wire:click="cancelRequest({{ $trx->id }})" class="mod-action-btn" style="color: red; border-color: red;" onclick="confirm('Yakin ingin membatalkan pengajuan ini?') || event.stopImmediatePropagation()">Batal</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Belum Ada Riwayat Permintaan</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(method_exists($history, 'links'))
        <div class="mod-pagination">
            {{ $history->links('pagination::tailwind') }}
        </div>
        @endif
    </div>
    @endif
</div>

