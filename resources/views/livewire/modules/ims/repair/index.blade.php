<div>
    <x-master.page-header title="Rak Perbaikan (Repair)" subtitle="Lacak barang rusak dari menunggu, diproses, hingga selesai dan kembali ke stok." accent="red" eyebrow="IMS Tracker">
        @can('ims.repair.request')
            <button type="button" wire:click="openReceive" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                Terima Barang Rusak
            </button>
        @endcan
    </x-master.page-header>

    <x-flash />

    <div class="mod-card mod-card-accent-orange">
        <div class="cbm-tabs">
            @foreach(['waiting' => 'Menunggu', 'process' => 'Diproses', 'completed' => 'Selesai'] as $key => $label)
                <button type="button" wire:click="setTab('{{ $key }}')" class="cbm-tab {{ $activeTab === $key ? 'active' : '' }}">
                    {{ $label }} <span class="cbm-tab-count">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </div>

        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari kode repair, barang, kerusakan..." aria-label="Cari repair">
                </div>
            </div>
            <div class="mod-meta">
                <span class="mod-record-count">{{ number_format($items->total()) }} data</span>
                @if($activeTab === 'completed')<span class="mod-record-count" title="Serviceable dan belum dikembalikan">{{ $counts['completed'] }} siap kembali ke stok</span>@endif
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>KODE</th>
                        <th>BARANG</th>
                        <th style="text-align:center;">QTY</th>
                        <th>KERUSAKAN</th>
                        @if($activeTab === 'waiting')<th>PRIORITAS</th><th>DITERIMA</th>@endif
                        @if($activeTab === 'process')<th>WO / VENDOR</th><th>TEKNISI</th><th>ESTIMASI</th>@endif
                        @if($activeTab === 'completed')<th>HASIL</th><th>SELESAI</th>@endif
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        <tr wire:key="repair-{{ $activeTab }}-{{ $row->id }}">
                            <td><div class="mod-aircraft-name">{{ $row->repair_code }}</div></td>
                            <td>
                                <div>{{ $row->item->name ?? '-' }}</div>
                                <div class="mod-aircraft-sub">PN {{ $row->item->part_number ?? '-' }}@if(! empty($row->aircraft_registration)) &middot; {{ $row->aircraft_registration }}@endif</div>
                            </td>
                            <td style="text-align:center;font-weight:800;">{{ $row->qty }}</td>
                            <td style="max-width:20rem;white-space:normal;"><x-text-popup :text="$row->fault_description ?: '-'" title="Kerusakan" /></td>

                            @if($activeTab === 'waiting')
                                <td>
                                    <span class="{{ $row->priority === 'high' ? 'mod-badge-open' : ($row->priority === 'low' ? 'mod-badge-inactive' : 'mod-badge-progress') }}">{{ $priorities[$row->priority] ?? ucfirst((string) $row->priority) }}</span>
                                </td>
                                <td style="white-space:nowrap;">{{ $row->received_at?->format('d M Y H:i') }}<div class="mod-aircraft-sub">{{ $row->received_at?->diffForHumans() }}</div></td>
                                <td style="text-align:right;">
                                    @can('ims.repair.manage')<button type="button" wire:click="openStart('{{ $row->repair_code }}')" class="mod-action-btn">Mulai proses</button>@endcan
                                </td>
                            @elseif($activeTab === 'process')
                                <td>{{ $row->work_order_no ?: '-' }}<div class="mod-aircraft-sub">{{ $row->vendor->name ?? 'Internal' }}</div></td>
                                <td>{{ $row->technician->name ?? '-' }}</td>
                                <td style="white-space:nowrap;">
                                    @if($row->estimated_completion_date)
                                        <span class="{{ $row->estimated_completion_date->isPast() ? 'mod-badge-open' : '' }}">{{ $row->estimated_completion_date->format('d M Y') }}</span>
                                    @else - @endif
                                </td>
                                <td style="text-align:right;">
                                    @can('ims.repair.manage')<button type="button" wire:click="openComplete('{{ $row->repair_code }}')" class="mod-action-btn" style="color:#10b981;border-color:rgba(16,185,129,.4);">Selesaikan</button>@endcan
                                </td>
                            @else
                                <td>
                                    <span class="{{ $row->result === 'serviceable' ? 'mod-badge-closed' : 'mod-badge-open' }}">{{ ['serviceable' => 'Serviceable', 'unserviceable' => 'Unserviceable', 'scrap' => 'Scrap / BER'][$row->result] ?? $row->result }}</span>
                                    @if($row->returned_to_stock_at)<div class="mod-aircraft-sub">Kembali ke stok {{ $row->returned_to_stock_at->format('d M Y') }}</div>@endif
                                </td>
                                <td style="white-space:nowrap;">{{ $row->completed_at?->format('d M Y H:i') }}</td>
                                <td style="text-align:right;">
                                    @if($row->result === 'serviceable' && ! $row->returned_to_stock_at)
                                        @can('ims.repair.back_stage')<button type="button" wire:click="openReturn('{{ $row->repair_code }}')" class="mod-action-btn" style="color:#10b981;border-color:rgba(16,185,129,.4);">Kembalikan ke stok</button>@endcan
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ $search ? 'Tidak ada data yang cocok' : 'Rak kosong' }}</div>
                                <div class="mod-empty-sub">{{ $search ? 'Ubah kata kunci pencarian.' : 'Tidak ada barang pada tahap ini.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="mod-pagination">{{ $items->links('pagination::tailwind') }}</div>
        @endif
    </div>

    {{-- Terima --}}
    <x-master.modal :show="$modal === 'receive'" title="Terima Barang Rusak" subtitle="Barang masuk ke rak Menunggu. Stok tidak berubah sampai barang kembali serviceable." submit="receive" close="closeModal" max-width="34rem" submit-label="Terima">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="rp-search">Cari barang</label>
            <input id="rp-search" type="search" wire:model.live.debounce.300ms="searchItem" class="cbm-form-input" placeholder="Nama atau part number..." autocomplete="off">
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="rp-item">Barang *</label>
            <div class="cbm-select-wrap">
                <select id="rp-item" wire:model="itemId" class="cbm-form-select">
                    <option value="">-- Pilih barang --</option>
                    @foreach($pickerItems as $it)<option value="{{ $it->id }}">{{ $it->part_number }} - {{ $it->name }}</option>@endforeach
                </select>
            </div>
            @error('itemId') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="rp-loc">Disimpan di lokasi *</label>
            <div class="cbm-select-wrap"><select id="rp-loc" wire:model="location_id" class="cbm-form-select"><option value="">-- Pilih lokasi --</option>@foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div>
            @error('location_id') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));gap:1rem;">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rp-qty">Jumlah *</label>
                <input id="rp-qty" type="number" min="1" wire:model="qty" class="cbm-form-input">
                @error('qty') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rp-prio">Prioritas *</label>
                <div class="cbm-select-wrap"><select id="rp-prio" wire:model="priority" class="cbm-form-select">@foreach($priorities as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                @error('priority') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rp-src">Asal *</label>
                <div class="cbm-select-wrap"><select id="rp-src" wire:model="source" class="cbm-form-select">@foreach($sources as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                @error('source') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rp-reg">Registrasi pesawat</label>
                <input id="rp-reg" type="text" wire:model="aircraft_registration" class="cbm-form-input" maxlength="20" placeholder="PK-..." style="text-transform:uppercase;">
                @error('aircraft_registration') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="rp-fault">Deskripsi kerusakan *</label>
            <textarea id="rp-fault" wire:model="fault_description" class="cbm-form-textarea" maxlength="1000" placeholder="Apa yang rusak / gejalanya..."></textarea>
            @error('fault_description') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="rp-notes">Catatan</label>
            <input id="rp-notes" type="text" wire:model="notes" class="cbm-form-input" maxlength="1000">
            @error('notes') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>

    {{-- Mulai proses --}}
    <x-master.modal :show="$modal === 'start'" :title="'Mulai Proses '.$code" subtitle="Barang berpindah ke rak Diproses." submit="start" close="closeModal" max-width="30rem" submit-label="Mulai proses">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="st-tech">Teknisi</label>
            <div class="cbm-select-wrap"><select id="st-tech" wire:model="technician_id" class="cbm-form-select"><option value="">-- Opsional --</option>@foreach($technicians as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
            @error('technician_id') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="st-vendor">Vendor (kosong = internal)</label>
            <div class="cbm-select-wrap"><select id="st-vendor" wire:model="vendor_id" class="cbm-form-select"><option value="">Internal</option>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
            @error('vendor_id') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:1rem;">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="st-wo">No. Work Order</label>
                <input id="st-wo" type="text" wire:model="work_order_no" class="cbm-form-input" maxlength="50">
                @error('work_order_no') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="st-eta">Estimasi selesai</label>
                <input id="st-eta" type="date" wire:model="estimated_completion_date" class="cbm-form-input">
                @error('estimated_completion_date') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="st-notes">Catatan progres</label>
            <textarea id="st-notes" wire:model="progress_notes" class="cbm-form-textarea" maxlength="1000"></textarea>
            @error('progress_notes') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>

    {{-- Selesaikan --}}
    <x-master.modal :show="$modal === 'complete'" :title="'Selesaikan '.$code" subtitle="Catat hasil perbaikan." submit="complete" close="closeModal" max-width="32rem" submit-label="Selesaikan">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="cp-result">Hasil *</label>
            <div class="cbm-select-wrap"><select id="cp-result" wire:model.live="result" class="cbm-form-select">@foreach($results as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
            @error('result') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="cp-find">Temuan *</label>
            <textarea id="cp-find" wire:model="findings" class="cbm-form-textarea" maxlength="2000"></textarea>
            @error('findings') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="cp-act">Tindakan perbaikan {{ $result === 'serviceable' ? '*' : '' }}</label>
            <textarea id="cp-act" wire:model="action_taken" class="cbm-form-textarea" maxlength="2000"></textarea>
            @error('action_taken') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:1rem;">
            <div class="cbm-form-group" style="margin-bottom:0;">
                <label class="cbm-form-label" for="cp-cert">No. sertifikat</label>
                <input id="cp-cert" type="text" wire:model="certificate_no" class="cbm-form-input" maxlength="100">
                @error('certificate_no') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group" style="margin-bottom:0;">
                <label class="cbm-form-label" for="cp-by">Diperbaiki oleh *</label>
                <input id="cp-by" type="text" wire:model="repaired_by_name" class="cbm-form-input" maxlength="100">
                @error('repaired_by_name') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </x-master.modal>

    {{-- Kembalikan ke stok --}}
    <x-master.modal :show="$modal === 'return'" :title="'Kembalikan '.$code.' ke stok'" subtitle="Jumlah barang ditambahkan ke stok di lokasi yang dipilih." submit="returnToStock" close="closeModal" max-width="26rem" submit-label="Kembalikan ke stok">
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label" for="rt-loc">Lokasi stok *</label>
            <div class="cbm-select-wrap"><select id="rt-loc" wire:model="return_location_id" class="cbm-form-select"><option value="">-- Pilih lokasi --</option>@foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div>
            @error('return_location_id') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
