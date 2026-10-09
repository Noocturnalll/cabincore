<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-{{ $meta['accent'] }}">Aircraft Cleaning</div>
            <div class="mod-title">{{ $meta['title'] }}</div>
            <div class="mod-subtitle">{{ $meta['subtitle'] }}</div>
        </div>
        <div class="mod-actions">
            <a href="{{ route('modules.cleaning.sync') }}" wire:navigate class="mod-btn-outline" style="text-decoration:none;">
                <span style="display:inline-flex;align-items:center;gap:.4rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem;"><path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0015.059-4.035.75.75 0 00-.53-.918z" clip-rule="evenodd"/></svg>
                    Sync Sheets
                </span>
            </a>
            <button wire:click="create" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                Tambah Data
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-{{ $meta['accent'] === 'green' ? 'green' : ($meta['accent'] === 'purple' ? 'purple' : 'blue') }}">
        <div class="cbm-tabs">
            @foreach(['' => 'Semua', 'Open' => 'Open', 'Closed' => 'Closed'] as $value => $label)
                <button type="button" wire:click="$set('statusFilter', '{{ $value }}')" class="cbm-tab {{ $statusFilter === $value ? 'active' : '' }}">
                    {{ $label }} <span class="cbm-tab-count">{{ number_format($counts[$value]) }}</span>
                </button>
            @endforeach
        </div>

        <x-log-toolbar :logs="$cleanings" mode="date" :active="(bool) ($search || $dateFilter || $statusFilter)" placeholder="Cari registrasi, operator, station..." />

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>REGISTRASI</th>
                        <th>TANGGAL</th>
                        <th>STATION</th>
                        <th>{{ strtoupper($meta['teamLabel']) }}</th>
                        <th>OPERATOR</th>
                        <th>REMARKS</th>
                        <th>STATUS</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cleanings as $cln)
                        <tr wire:key="cln-{{ $cln->id }}">
                            <td><div class="mod-aircraft-name">{{ $cln->aircraft_registration }}</div></td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($cln->date)->format('d M Y') }}</td>
                            <td>{{ $cln->station ?: '-' }}</td>
                            <td>{{ $cln->shift ?: '-' }}</td>
                            <td>{{ $cln->operator ?: '-' }}</td>
                            <td><x-text-popup :text="$cln->remarks ?: '-'" title="Remarks" /></td>
                            <td>
                                @if(in_array($cln->status, ['Closed', 'Selesai'], true))
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Closed</span>
                                @else
                                    <span class="mod-badge-open"><span class="mod-badge-dot" style="background:#f87171;"></span>Open</span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button wire:click="edit({{ $cln->id }})" class="mod-btn-outline" style="padding:.25rem .625rem;font-size:.75rem;">Edit</button>
                                <button wire:click="deleteConfirm({{ $cln->id }})" class="mod-btn-outline" style="padding:.25rem .625rem;font-size:.75rem;color:#ef4444;border-color:rgba(239,68,68,.35);" aria-label="Hapus {{ $cln->aircraft_registration }}">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="mod-empty">
                                    <div class="mod-empty-title">{{ ($search || $dateFilter || $statusFilter) ? 'Tidak ada data yang cocok' : 'Belum ada data' }}</div>
                                    <div class="mod-empty-sub">{{ ($search || $dateFilter || $statusFilter) ? 'Ubah kata kunci, tanggal, atau reset filter.' : 'Klik "Tambah Data" untuk mencatat '.strtolower($meta['title']).' pertama.' }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cleanings->hasPages())
            <div class="mod-pagination">{{ $cleanings->links('pagination::tailwind') }}</div>
        @endif
    </div>

    {{-- ═══════ Form Modal ═══════ --}}
    @if($isModalOpen)
    <div class="cbm-modal-overlay" x-data x-on:keydown.escape.window="$wire.closeModal()" wire:click.self="closeModal" style="display:flex;">
        <div class="cbm-modal-panel" style="max-width:32rem;" @click.stop>
            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">{{ $cleaningId ? 'Edit' : 'Tambah' }} {{ $meta['title'] }}</div>
                    <div class="cbm-modal-subtitle">Kolom bertanda * wajib diisi</div>
                </div>
                <button class="cbm-modal-close" wire:click="closeModal" aria-label="Tutup">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit="save">
                <div class="cbm-modal-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:1rem;">
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-reg">Registrasi Pesawat *</label>
                            <input id="cln-reg" type="text" wire:model="aircraft_registration" class="cbm-form-input" placeholder="PK-..." autocomplete="off" style="text-transform:uppercase;">
                            @error('aircraft_registration') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-sta">Station *</label>
                            <div class="cbm-select-wrap">
                                <select id="cln-sta" wire:model="station" class="cbm-form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach($stationOptions as $sta)<option value="{{ $sta }}">{{ $sta }}</option>@endforeach
                                </select>
                            </div>
                            @error('station') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-date">Tanggal *</label>
                            <input id="cln-date" type="date" wire:model="date" class="cbm-form-input">
                            @error('date') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-start">Jam mulai</label>
                            <input id="cln-start" type="time" wire:model="start_time" class="cbm-form-input">
                            @error('start_time') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-end">Jam selesai</label>
                            <input id="cln-end" type="time" wire:model="end_time" class="cbm-form-input">
                            @error('end_time') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-shift">Shift *</label>
                            <div class="cbm-select-wrap">
                                <select id="cln-shift" wire:model="shift" class="cbm-form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach($shifts as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                                </select>
                            </div>
                            @error('shift') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-op">Operator / Tim</label>
                            <input id="cln-op" type="text" wire:model="operator" class="cbm-form-input" placeholder="Nama / tim">
                            @error('operator') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="cln-status">Status *</label>
                            <div class="cbm-select-wrap">
                                <select id="cln-status" wire:model.live="status" class="cbm-form-select {{ $status === 'Open' ? 'cbm-status-open' : 'cbm-status-closed' }}">
                                    <option value="Open">Open (dalam proses)</option>
                                    <option value="Closed">Closed (selesai)</option>
                                </select>
                            </div>
                            @error('status') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="cbm-form-group" style="margin-bottom:0;">
                        <label class="cbm-form-label" for="cln-remarks">Remarks</label>
                        <textarea id="cln-remarks" wire:model="remarks" class="cbm-form-textarea" placeholder="Catatan tambahan (opsional)"></textarea>
                        @error('remarks') <span style="color:#f87171;font-size:.75rem;font-weight:600;">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="cbm-modal-footer">
                    <button type="button" wire:click="closeModal" class="mod-btn-outline">Batal</button>
                    <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save"><span class="cbm-spinner"></span> Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ═══════ Delete confirmation ═══════ --}}
    @if($deleteId)
    <div class="cbm-modal-overlay" x-data x-on:keydown.escape.window="$wire.cancelDelete()" wire:click.self="cancelDelete" style="display:flex;">
        <div class="cbm-modal-panel" style="max-width:24rem;" @click.stop>
            <div class="cbm-modal-header">
                <div>
                    <div class="cbm-modal-title">Hapus data?</div>
                    <div class="cbm-modal-subtitle">Data yang dihapus tidak dapat dikembalikan.</div>
                </div>
            </div>
            <div class="cbm-modal-footer">
                <button type="button" wire:click="cancelDelete" class="mod-btn-outline">Batal</button>
                <button type="button" wire:click="delete" class="mod-btn-primary" style="background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px .875rem rgba(239,68,68,.4);" wire:loading.attr="disabled" wire:target="delete">
                    <span wire:loading.remove wire:target="delete">Ya, hapus</span>
                    <span wire:loading wire:target="delete"><span class="cbm-spinner"></span> Menghapus...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
