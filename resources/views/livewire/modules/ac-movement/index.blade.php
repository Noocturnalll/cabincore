<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Operasional</div>
            <div class="mod-title">AC Movement &amp; RON</div>
            <div class="mod-subtitle">Pantau pergerakan pesawat di Terminal dan daftar RON. Data diperbarui otomatis dari Google Sheets.</div>
        </div>
        <div class="mod-actions">
            <button type="button" class="mod-btn-outline" wire:click="$refresh" wire:loading.attr="disabled" wire:target="$refresh">
                <span style="display:inline-flex;align-items:center;gap:.4rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem;height:1rem;" wire:loading.class="animate-spin" wire:target="$refresh"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    Refresh
                </span>
            </button>
        </div>
    </div>

    @if($syncSetting->spreadsheet_id)
        @if($freshness['failed'])
            <div class="cbm-flash cbm-flash-error" role="alert" style="margin-bottom:1rem;">
                <strong>Sync terakhir gagal</strong> ({{ $freshness['minutes'] !== null ? $freshness['minutes'].' menit lalu' : 'belum pernah berhasil' }}): {{ $syncSetting->last_message }}
                Data di bawah adalah data terakhir yang berhasil ditarik.
            </div>
        @elseif($freshness['stale'])
            <div class="mod-hint mod-hint-warn" role="alert">
                Data belum diperbarui {{ $freshness['minutes'] !== null ? $freshness['minutes'].' menit' : 'sama sekali' }}. Sync otomatis berjalan tiap 5 menit;
                pastikan scheduler server aktif (<code>php artisan schedule:run</code> tiap menit) atau sinkronkan manual di tab Sinkronisasi.
            </div>
        @endif
    @endif

    {{-- Main tabs --}}
    <div class="cbm-seg" role="tablist" aria-label="Tampilan">
        <button type="button" role="tab" wire:click="setMainTab('table')" class="cbm-seg-btn {{ $mainTab === 'table' ? 'active' : '' }}" aria-selected="{{ $mainTab === 'table' ? 'true' : 'false' }}">Data Tabel</button>
        <button type="button" role="tab" wire:click="setMainTab('sync')" class="cbm-seg-btn {{ $mainTab === 'sync' ? 'active' : '' }}" aria-selected="{{ $mainTab === 'sync' ? 'true' : 'false' }}">Sinkronisasi</button>
    </div>

    @if($mainTab === 'sync')
        {{-- Sync card --}}
        <div class="mod-card mod-card-accent-blue" style="max-width:40rem;margin:0 auto;padding:1.5rem;text-align:center;">
            <div style="width:3rem;height:3rem;border-radius:.75rem;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg style="width:1.5rem;height:1.5rem;color:var(--cbm-blue);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
            </div>
            <h3 style="font-size:1rem;font-weight:700;color:var(--cbm-text);margin-bottom:.25rem;">Google Sheets Sync</h3>
            <p style="font-size:.8125rem;color:var(--cbm-text-muted);margin-bottom:1.25rem;line-height:1.5;">
                Tarik data pergerakan pesawat dari sheet Terminal 1, Terminal 2, AC RON, dan AC STBY.
            </p>

            <form wire:submit="syncNow" style="display:flex;flex-direction:column;gap:.5rem;">
                <input type="text" wire:model="sheetUrl" placeholder="https://docs.google.com/spreadsheets/d/..." class="mod-search-input mod-input-plain" style="width:100%;text-align:left;" aria-label="Link Google Sheet">
                @error('sheetUrl') <span style="color:#f87171;font-size:.75rem;font-weight:600;text-align:left;">{{ $message }}</span> @enderror
                <button type="submit" class="mod-btn-primary" style="width:100%;justify-content:center;" wire:loading.attr="disabled" wire:target="syncNow">
                    <span wire:loading.remove wire:target="syncNow">Mulai Sinkronisasi</span>
                    <span wire:loading wire:target="syncNow"><span class="cbm-spinner"></span> Menyinkronkan...</span>
                </button>
            </form>

            @include('livewire.modules.partials.sync-status', [
                'syncSetting' => $syncSetting,
                'hint' => 'Link sheet tersimpan permanen dan auto-sync tiap 5 menit. Ganti link di atas jika sheet berubah.',
            ])
        </div>
    @else
        <div class="mod-card mod-card-accent-blue" wire:poll.60s>
            <div class="cbm-tabs">
                @foreach(['terminal1' => 'Terminal 1', 'terminal2' => 'Terminal 2', 'ron' => 'AC RON', 'standby' => 'AC STBY'] as $key => $label)
                    <button type="button" wire:click="setActiveTab('{{ $key }}')" class="cbm-tab {{ $activeTab === $key ? 'active' : '' }}">
                        {{ $label }} <span class="cbm-tab-count">{{ $counts[$key] }}</span>
                    </button>
                @endforeach
            </div>

            @php
                $shown = match ($activeTab) {
                    'terminal2' => $terminal2,
                    'ron' => $acRon,
                    'standby' => $acStandby,
                    default => $terminal1,
                };
            @endphp

            <div class="mod-toolbar">
                <div class="mod-filters">
                    <div class="mod-field mod-field-search">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari registrasi, flight, stand..." aria-label="Cari">
                        <button type="button" wire:click="clearSearch" class="mod-clear-btn" aria-label="Hapus pencarian" x-data x-show="$wire.search" x-cloak>&times;</button>
                    </div>
                    <span wire:loading wire:target="search, setActiveTab" class="cbm-spinner" aria-label="Memuat"></span>
                </div>
                <div class="mod-meta">
                    <span class="mod-record-count">{{ $shown->count() }}{{ $search !== '' ? ' dari '.$counts[$activeTab] : '' }} data</span>
                    @if($syncSetting->last_synced_at)
                        <span class="mod-record-count" title="Sinkronisasi terakhir">Sync {{ $syncSetting->last_synced_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>

            <div class="mod-table-wrap">
                @if($activeTab === 'terminal1' || $activeTab === 'terminal2')
                    <table class="mod-table" style="white-space:nowrap;">
                        <thead>
                            <tr>
                                <th>NO</th><th>DATE</th><th>REG</th><th>FLT IN</th><th>STA</th><th>ETA</th>
                                <th>PLAN P/S</th><th>FLT OUT</th><th>STD</th><th>ATD</th><th>NSRDI OPEN</th><th>DESCRIPTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shown as $row)
                                @php $nsrdis = $openNsrdis->get($row->registration, collect()); @endphp
                                <tr wire:key="mv-{{ $activeTab }}-{{ $row->id }}">
                                    <td>{{ $row->no_seq }}</td>
                                    <td>{{ $row->flight_date?->format('d M Y') }}</td>
                                    <td>
                                        @php
                                            $stretcher = $this->findStretcherMatch($row->registration, $row->flight_no_in, $row->flight_no_out);
                                        @endphp
                                        <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                                            <span class="mod-aircraft-name">{{ $row->registration }}</span>
                                            @if($stretcher)
                                                <button type="button"
                                                        wire:click="showStretcherDetail({{ $stretcher->id }})"
                                                        class="mod-stretcher-btn {{ strtolower($stretcher->status) }}"
                                                        title="Stretcher terdeteksi ({{ $stretcher->status }})! Klik untuk detail tiket & informasi medis">
                                                    <span class="stretcher-pulse-dot"></span>
                                                    <span class="stretcher-icon">🩺</span>
                                                    <span>{{ $stretcher->status === 'REQUESTED' ? 'Req Stretcher' : ($stretcher->status === 'IN_TRANSIT' ? 'Stretcher Onboard' : 'Stretcher') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $row->flight_no_in }}</td>
                                    <td>{{ $row->sta }}</td>
                                    <td>{{ $row->eta }}</td>
                                    <td>{{ $row->plan_ps }}</td>
                                    <td>{{ $row->flight_no_out }}</td>
                                    <td>{{ $row->std }}</td>
                                    <td>{{ $row->atd }}</td>
                                    <td>
                                        @forelse($nsrdis as $nsrdi)
                                            <span class="mod-badge-open" style="margin:0 .25rem .25rem 0;">{{ $nsrdi->nsrdi_number ?: '-' }}</span>
                                        @empty
                                            <span style="color:var(--cbm-text-muted);">-</span>
                                        @endforelse
                                    </td>
                                    <td style="white-space:normal;min-width:14rem;max-width:26rem;">
                                        @forelse($nsrdis as $nsrdi)
                                            <div style="margin-bottom:.25rem;">{{ $nsrdi->description ?: '-' }}</div>
                                        @empty
                                            <span style="color:var(--cbm-text-muted);">-</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="12">@include('livewire.modules.ac-movement.partials.empty', ['label' => 'data terminal'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @elseif($activeTab === 'ron')
                    <table class="mod-table" style="white-space:nowrap;">
                        <thead>
                            <tr><th>NO</th><th>DATE</th><th>REG</th><th>EX FLT</th><th>STA/ATA</th><th>STAND</th><th>FLT NO</th><th>ROUTE</th><th>STD</th><th>REMARKS</th></tr>
                        </thead>
                        <tbody>
                            @forelse($shown as $row)
                                <tr wire:key="ron-{{ $row->id }}">
                                    <td>{{ $row->no_seq }}</td>
                                    <td>{{ $row->ron_date?->format('d M Y') }}</td>
                                    <td>
                                        @php
                                            $stretcher = $this->findStretcherMatch($row->reg_flt, $row->ex_flt, $row->flt_no);
                                        @endphp
                                        <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                                            <span class="mod-aircraft-name">{{ $row->reg_flt }}</span>
                                            @if($stretcher)
                                                <button type="button"
                                                        wire:click="showStretcherDetail({{ $stretcher->id }})"
                                                        class="mod-stretcher-btn {{ strtolower($stretcher->status) }}"
                                                        title="Stretcher terdeteksi ({{ $stretcher->status }})! Klik untuk detail tiket & informasi medis">
                                                    <span class="stretcher-pulse-dot"></span>
                                                    <span class="stretcher-icon">🩺</span>
                                                    <span>{{ $stretcher->status === 'REQUESTED' ? 'Req Stretcher' : ($stretcher->status === 'IN_TRANSIT' ? 'Stretcher Onboard' : 'Stretcher') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $row->ex_flt }}</td>
                                    <td>{{ $row->sta_ata }}</td>
                                    <td>{{ $row->stand }}</td>
                                    <td>{{ $row->flt_no }}</td>
                                    <td>{{ $row->route }}</td>
                                    <td>{{ $row->std }}</td>
                                    <td style="white-space:normal;min-width:12rem;">{{ $row->remarks }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10">@include('livewire.modules.ac-movement.partials.empty', ['label' => 'AC RON'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="mod-table" style="white-space:nowrap;">
                        <thead>
                            <tr><th>NO</th><th>AIRLINE</th><th>REG</th><th>STAND</th><th>PLAN RTS</th><th>REMARKS</th></tr>
                        </thead>
                        <tbody>
                            @forelse($shown as $row)
                                <tr wire:key="sb-{{ $row->id }}">
                                    <td>{{ $row->no_seq }}</td>
                                    <td>{{ $row->airline_category }}</td>
                                    <td>
                                        @php
                                            $stretcher = $this->findStretcherMatch($row->reg_flt);
                                        @endphp
                                        <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                                            <span class="mod-aircraft-name">{{ $row->reg_flt }}</span>
                                            @if($stretcher)
                                                <button type="button"
                                                        wire:click="showStretcherDetail({{ $stretcher->id }})"
                                                        class="mod-stretcher-btn {{ strtolower($stretcher->status) }}"
                                                        title="Stretcher terdeteksi ({{ $stretcher->status }})! Klik untuk detail tiket & informasi medis">
                                                    <span class="stretcher-pulse-dot"></span>
                                                    <span class="stretcher-icon">🩺</span>
                                                    <span>{{ $stretcher->status === 'REQUESTED' ? 'Req Stretcher' : ($stretcher->status === 'IN_TRANSIT' ? 'Stretcher Onboard' : 'Stretcher') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $row->parking }}</td>
                                    <td>{{ $row->plan_rts }}</td>
                                    <td style="white-space:normal;min-width:12rem;">{{ $row->remarks }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">@include('livewire.modules.ac-movement.partials.empty', ['label' => 'AC STBY'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif

    {{-- Modal Detail Stretcher Tracker --}}
    @if($isStretcherModalOpen && $selectedStretcher)
    <div class="cbm-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; display:flex; align-items:center; justify-content:center; padding:1rem;" wire:keydown.escape="closeStretcherModal">
        <div class="cbm-modal-content" style="background:var(--cbm-card-bg); width:100%; max-width:34rem; border-radius:0.875rem; overflow:hidden; box-shadow:0 1.25rem 1.875rem -0.5rem rgba(0,0,0,0.3); border:1px solid var(--cbm-card-border);">
            <div style="padding:1.15rem 1.5rem; border-bottom:1px solid var(--cbm-card-border); display:flex; justify-content:space-between; align-items:center; background:rgba(139,92,246,0.06);">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="font-size:1.25rem;">🩺</span>
                    <div>
                        <h3 style="margin:0; font-weight:700; font-size:1.05rem; color:var(--cbm-text);">Detail Permintaan Stretcher</h3>
                        <div style="font-size:0.75rem; color:var(--cbm-text-muted);">Terdeteksi di AC Movement: <strong>{{ $selectedStretcher->aircraft_registration }}</strong> ({{ $selectedStretcher->flight_number }})</div>
                    </div>
                </div>
                <button type="button" wire:click="closeStretcherModal" style="background:transparent; border:none; color:var(--cbm-text-muted); cursor:pointer; font-size:1.25rem; line-height:1;" aria-label="Tutup">&times;</button>
            </div>

            <div style="padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:1rem;">
                {{-- Status Banner --}}
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 0.9rem; border-radius:0.5rem; 
                    @if($selectedStretcher->status === 'REQUESTED') background:rgba(139,92,246,0.12); border:1px solid rgba(139,92,246,0.3); color:#8b5cf6;
                    @elseif($selectedStretcher->status === 'IN_TRANSIT') background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.3); color:#3b82f6;
                    @elseif($selectedStretcher->status === 'COMPLETED') background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#10b981;
                    @else background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#ef4444; @endif">
                    <div style="display:flex; align-items:center; gap:0.4rem; font-weight:700; font-size:0.82rem;">
                        <span class="stretcher-pulse-dot" style="background:currentColor;"></span>
                        Status Tiket: {{ $selectedStretcher->status === 'REQUESTED' ? 'REQ STRETCHER (Dalam Permintaan)' : ($selectedStretcher->status === 'IN_TRANSIT' ? 'IN TRANSIT (Onboard / Terbang)' : $selectedStretcher->status) }}
                    </div>
                    <span style="font-size:0.75rem; opacity:0.85;">{{ $selectedStretcher->date?->format('d M Y') }}</span>
                </div>

                {{-- Flight & Aircraft Info Grid --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.82rem;">
                    <div style="background:var(--cbm-input-bg); padding:0.6rem 0.8rem; border-radius:0.5rem; border:1px solid var(--cbm-card-border);">
                        <div style="font-size:0.7rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:600;">A/C Registration</div>
                        <div style="font-weight:700; font-size:0.95rem; color:var(--cbm-blue); margin-top:0.15rem;">{{ $selectedStretcher->aircraft_registration }}</div>
                    </div>
                    <div style="background:var(--cbm-input-bg); padding:0.6rem 0.8rem; border-radius:0.5rem; border:1px solid var(--cbm-card-border);">
                        <div style="font-size:0.7rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:600;">Nomor Penerbangan</div>
                        <div style="font-weight:700; font-size:0.95rem; color:var(--cbm-text); margin-top:0.15rem;">{{ $selectedStretcher->flight_number }}</div>
                    </div>
                    <div style="background:var(--cbm-input-bg); padding:0.6rem 0.8rem; border-radius:0.5rem; border:1px solid var(--cbm-card-border);">
                        <div style="font-size:0.7rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:600;">Rute Penerbangan</div>
                        <div style="font-weight:700; color:var(--cbm-text); margin-top:0.15rem;">
                            {{ $selectedStretcher->origin_station }} &rarr; {{ $selectedStretcher->destination_station }}
                        </div>
                    </div>
                    <div style="background:var(--cbm-input-bg); padding:0.6rem 0.8rem; border-radius:0.5rem; border:1px solid var(--cbm-card-border);">
                        <div style="font-size:0.7rem; color:var(--cbm-text-muted); text-transform:uppercase; font-weight:600;">Nama Pasien / Penumpang</div>
                        <div style="font-weight:700; color:var(--cbm-text); margin-top:0.15rem;">{{ $selectedStretcher->passenger_name }}</div>
                    </div>
                </div>

                {{-- Medical Notes --}}
                <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.25); border-left:4px solid #ef4444; border-radius:0.5rem; padding:0.75rem 0.9rem;">
                    <div style="font-size:0.72rem; font-weight:700; color:#ef4444; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.25rem; display:flex; align-items:center; gap:0.35rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:0.875rem;height:0.875rem;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                        Kondisi / Catatan Medis Khusus
                    </div>
                    <div style="font-size:0.82rem; color:var(--cbm-text); line-height:1.45;">
                        {{ $selectedStretcher->medical_notes ?: 'Tidak ada catatan medis khusus.' }}
                    </div>
                </div>

                {{-- Unit Stretcher Detail --}}
                @if($selectedStretcher->stretcher)
                <div style="background:var(--cbm-input-bg); border:1px solid var(--cbm-card-border); border-radius:0.5rem; padding:0.75rem 0.9rem; font-size:0.8rem;">
                    <div style="font-size:0.7rem; color:var(--cbm-text-muted); font-weight:700; text-transform:uppercase; margin-bottom:0.4rem;">Unit Stretcher Terpasang</div>
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                        <div>
                            <span style="font-weight:700; color:var(--cbm-text);">{{ $selectedStretcher->stretcher->code }}</span>
                            <span style="color:var(--cbm-text-muted);">({{ $selectedStretcher->stretcher->name }})</span>
                        </div>
                        <div style="display:flex; gap:0.5rem;">
                            <span style="background:var(--cbm-card-bg); border:1px solid var(--cbm-card-border); padding:0.1rem 0.4rem; border-radius:0.3rem;">Type: {{ $selectedStretcher->stretcher->aircraft_type ?? 'B737' }}</span>
                            <span style="background:var(--cbm-card-bg); border:1px solid var(--cbm-card-border); padding:0.1rem 0.4rem; border-radius:0.3rem;">Kondisi: {{ $selectedStretcher->stretcher->condition ?? 'GOOD' }}</span>
                            <span style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.25); padding:0.1rem 0.4rem; border-radius:0.3rem;">Posisi: {{ $selectedStretcher->stretcher->current_station }}</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Operational PIC & Notes --}}
                <div style="font-size:0.78rem; color:var(--cbm-text-muted); display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                    <div>PIC / Teknisi: <strong style="color:var(--cbm-text);">{{ $selectedStretcher->pic_name ?: '-' }}</strong></div>
                    <div>Seat / Posisi: <strong style="color:var(--cbm-text);">{{ $selectedStretcher->notes ?: '-' }}</strong></div>
                </div>
            </div>

            <div style="padding:1rem 1.5rem; border-top:1px solid var(--cbm-card-border); background:var(--cbm-input-bg); display:flex; justify-content:space-between; align-items:center;">
                <a href="{{ route('modules.stretcher.index') }}" class="mod-btn-outline" style="display:inline-flex; align-items:center; gap:0.35rem; font-size:0.78rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:0.875rem;height:0.875rem;"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v11.5A2.25 2.25 0 004.25 18h11.5A2.25 2.25 0 0018 15.75V4.25A2.25 2.25 0 0015.75 2H4.25zm4.03 6.28a.75.75 0 00-1.06-1.06L4.97 9.47a.75.75 0 000 1.06l2.25 2.25a.75.75 0 001.06-1.06L7.06 10.5h4.44a2 2 0 012 2v1.25a.75.75 0 001.5 0v-1.25a3.5 3.5 0 00-3.5-3.5H7.06l1.22-1.22z" clip-rule="evenodd" /></svg>
                    Buka Stretcher Tracker
                </a>
                <button type="button" wire:click="closeStretcherModal" class="mod-btn-primary" style="padding:0.45rem 1.15rem; font-size:0.82rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Stretcher Badge CSS --}}
    <style>
        .mod-stretcher-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
            line-height: 1;
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            text-decoration: none;
        }
        .mod-stretcher-btn.requested {
            background: rgba(139, 92, 246, 0.16);
            color: #a78bfa;
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 0 10px rgba(139, 92, 246, 0.25);
        }
        .mod-stretcher-btn.requested:hover {
            background: rgba(139, 92, 246, 0.3);
            border-color: #8b5cf6;
            color: #c4b5fd;
            transform: translateY(-1px);
        }
        .cbm-light .mod-stretcher-btn.requested {
            background: rgba(124, 58, 237, 0.12);
            color: #6d28d9;
            border-color: rgba(124, 58, 237, 0.4);
        }
        .mod-stretcher-btn.in_transit {
            background: rgba(59, 130, 246, 0.16);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.4);
        }
        .mod-stretcher-btn.in_transit:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }
        .cbm-light .mod-stretcher-btn.in_transit {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            border-color: rgba(37, 99, 235, 0.4);
        }
        .mod-stretcher-btn.completed {
            background: rgba(16, 185, 129, 0.14);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.35);
        }
        .cbm-light .mod-stretcher-btn.completed {
            background: rgba(5, 150, 105, 0.12);
            color: #047857;
            border-color: rgba(5, 150, 105, 0.35);
        }
        .stretcher-pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            animation: cbm-pulse 1.8s ease-in-out infinite;
        }
    </style>
</div>
