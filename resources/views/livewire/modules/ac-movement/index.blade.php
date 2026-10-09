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
                                    <td><div class="mod-aircraft-name">{{ $row->registration }}</div></td>
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
                                    <td><div class="mod-aircraft-name">{{ $row->reg_flt }}</div></td>
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
                                    <td><div class="mod-aircraft-name">{{ $row->reg_flt }}</div></td>
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
</div>
