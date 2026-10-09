<div>
    <x-master.page-header title="Capacity Settings" subtitle="Daftar station untuk tabel Capacity dan target NSRDI per maskapai." accent="purple" eyebrow="Master Data" />

    <x-flash />

    <div class="cbm-seg" role="tablist" aria-label="Pengaturan capacity">
        <button type="button" role="tab" wire:click="setTab('stations')" class="cbm-seg-btn {{ $activeTab === 'stations' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'stations' ? 'true' : 'false' }}">Station <span class="cbm-tab-count">{{ $stations->count() }}</span></button>
        <button type="button" role="tab" wire:click="setTab('targets')" class="cbm-seg-btn {{ $activeTab === 'targets' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'targets' ? 'true' : 'false' }}">Target NSRDI</button>
    </div>

    @if($activeTab === 'stations')
        <div class="mod-card mod-card-accent-purple">
            <div class="mod-toolbar">
                <div class="mod-meta"><span class="mod-record-count">{{ $stations->count() }} station &middot; urut berdasarkan Order No</span></div>
                <div class="mod-meta">
                    <button type="button" wire:click="createStation" class="mod-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                        Tambah Station
                    </button>
                </div>
            </div>

            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr><th>NO</th><th>KH REGION</th><th>GROUP</th><th>STA</th><th>CODE STORE</th><th>HOURS</th><th style="text-align:center;">DAY</th><th style="text-align:center;">NIGHT</th><th style="text-align:right;">AKSI</th></tr>
                    </thead>
                    <tbody>
                        @forelse($stations as $s)
                            <tr wire:key="station-{{ $s->id }}">
                                <td>{{ $s->order_no }}</td>
                                <td style="font-weight:800;">{{ $s->kh_region }}</td>
                                <td>{{ $s->group_type }}</td>
                                <td><div class="mod-aircraft-name">{{ $s->station_code }}</div></td>
                                <td>{{ $s->code_store }}</td>
                                <td>{{ $s->working_hours }}</td>
                                <td style="text-align:center;">{{ $s->tech_day ?? '-' }}</td>
                                <td style="text-align:center;">{{ $s->tech_night ?? '-' }}</td>
                                <td style="text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                        <button type="button" wire:click="editStation({{ $s->id }})" class="mod-action-btn">Edit</button>
                                        <button type="button" wire:click="deleteStation({{ $s->id }})" wire:confirm="Hapus station {{ $s->station_code }} dari daftar capacity?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><div class="mod-empty"><div class="mod-empty-title">Belum ada station</div><div class="mod-empty-sub">Klik "Tambah Station" untuk mulai.</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-master.modal :show="$isModalOpen" :title="$station_id ? 'Edit Station' : 'Tambah Station'" subtitle="Muncul di tabel Capacity Management." submit="saveStation" close="closeModal" max-width="40rem">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:1rem;">
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="cs-order">Order No</label>
                    <input id="cs-order" type="number" min="0" wire:model="order_no" class="cbm-form-input">
                    @error('order_no') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="cs-kh">KH Region *</label>
                    <input id="cs-kh" type="text" wire:model="kh_region" class="cbm-form-input" placeholder="KH-1">
                    @error('kh_region') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="cs-group">Group</label>
                    <input id="cs-group" type="text" wire:model="group_type" class="cbm-form-input" placeholder="A">
                    @error('group_type') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="cs-sta">Station *</label>
                    <div class="cbm-select-wrap">
                        <select id="cs-sta" wire:model="station_code" class="cbm-form-select">
                            <option value="">-- Pilih station --</option>
                            @if($station_code && ! $airports->contains('kode', $station_code))
                                <option value="{{ $station_code }}">{{ $station_code }} (tidak ada di master bandara)</option>
                            @endif
                            @foreach($airports as $airport)<option value="{{ $airport->kode }}">{{ $airport->kode }} - {{ $airport->nama }}</option>@endforeach
                        </select>
                    </div>
                    @error('station_code') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="cs-store">Code Store</label>
                    <input id="cs-store" type="text" wire:model="code_store" class="cbm-form-input" placeholder="K1, K22">
                    @error('code_store') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="cs-hours">Working Hours</label>
                    <input id="cs-hours" type="text" wire:model="working_hours" class="cbm-form-input" placeholder="19.00-07.00">
                    @error('working_hours') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group" style="margin-bottom:0;">
                    <label class="cbm-form-label" for="cs-day">Teknisi Day</label>
                    <input id="cs-day" type="number" min="0" wire:model="tech_day" class="cbm-form-input">
                    @error('tech_day') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group" style="margin-bottom:0;">
                    <label class="cbm-form-label" for="cs-night">Teknisi Night</label>
                    <input id="cs-night" type="number" min="0" wire:model="tech_night" class="cbm-form-input">
                    @error('tech_night') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-master.modal>
    @else
        <div class="mod-card mod-card-accent-purple" style="max-width:36rem;padding:1.5rem;">
            <h3 style="font-size:1rem;font-weight:800;color:var(--cbm-text);margin-bottom:.25rem;">Target NSRDI closed per maskapai</h3>
            <p style="font-size:.8125rem;color:var(--cbm-text-muted);margin-bottom:1.25rem;line-height:1.6;">Jumlah NSRDI yang harus ditutup per hari. Dipakai oleh kartu target di halaman Capacity Management.</p>
            <form wire:submit="updateTargets">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));gap:1rem;">
                    @foreach(['jt' => 'JT (Lion)', 'iu' => 'IU (Super Air Jet)', 'id' => 'ID (Batik)'] as $key => $label)
                        <div class="cbm-form-group">
                            <label class="cbm-form-label" for="tg-{{ $key }}">{{ $label }}</label>
                            <input id="tg-{{ $key }}" type="number" min="0" wire:model="target_{{ $key }}" class="cbm-form-input">
                            @error('target_'.$key) <span class="mod-field-error">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="updateTargets">
                    <span wire:loading.remove wire:target="updateTargets">Simpan Target</span>
                    <span wire:loading wire:target="updateTargets"><span class="cbm-spinner"></span> Menyimpan...</span>
                </button>
            </form>
        </div>
    @endif
</div>
