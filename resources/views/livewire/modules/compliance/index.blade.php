<div>
    @php
        $tone = [
            'green' => ['#34d399', 'rgba(52,211,153,.14)'],
            'yellow' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'red' => ['#ef4444', 'rgba(239,68,68,.16)'],
            'none' => ['#94a3b8', 'rgba(148,163,184,.14)'],
        ];
        $statusOf = fn ($p) => \App\Services\Kpi\AchievementStatus::for($p, 100);
        $docLabel = $documents;
        $isExtra = $unit === 'EXTRA';
        $unitLabel = $units[$unit]['label'];
        $t = $data['total'];
        $ts = $statusOf($t['percent']);
    @endphp

    <style>
        .cp-slot { display:inline-flex; align-items:center; gap:.15rem; margin:0 .3rem .3rem 0; }
        .cp-slot .cp-file { position:absolute; width:1px; height:1px; opacity:0; overflow:hidden; }
        .cp-slot label { cursor:pointer; margin:0; }
        .cp-slot .cp-x { border:0; background:transparent; color:#ef4444; cursor:pointer; padding:0 .25rem; font-size:.9rem; line-height:1; }
        .cp-miss { padding:.2rem .5rem; border-radius:.4rem; font-size:.75rem; color:#ef4444; background:rgba(239,68,68,.12); }
        .cp-cell { white-space:normal; min-width:11rem; }
    </style>

    <x-master.page-header title="Daily Briefing, Attendant List & 5R" subtitle="Bukti harian per unit, station, dan shift: foto briefing, daftar hadir, dan 5R. Hari ini belum dihitung, tetapi buktinya sudah bisa diunggah." accent="blue" eyebrow="Compliance">
        @if($canSync)
            <button type="button" wire:click="syncNow" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="syncNow">
                <span wire:loading.remove wire:target="syncNow">Sinkron sheet AIEC</span>
                <span wire:loading wire:target="syncNow"><span class="cbm-spinner"></span> Membaca sheet...</span>
            </button>
        @endif
    </x-master.page-header>

    <x-flash />

    {{-- Unit tabs --}}
    <div class="cbm-tabs" role="tablist" aria-label="Unit" style="padding-left:0;padding-right:0;margin-bottom:1rem;">
        @foreach($units as $key => $cfg)
            <button type="button" role="tab" aria-selected="{{ $unit === $key ? 'true' : 'false' }}" wire:click="setUnit('{{ $key }}')" class="cbm-tab {{ $unit === $key ? 'active' : '' }}">{{ $cfg['label'] }}</button>
        @endforeach
    </div>

    <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:.4rem;">
            @foreach(['7' => '7 hari', '30' => '30 hari', '90' => '90 hari', 'all' => 'Semua'] as $k => $l)
                <button type="button" wire:click="setRange('{{ $k }}')" class="cbm-tab {{ $range === (string) $k ? 'active' : '' }}">{{ $l }}</button>
            @endforeach
        </div>
        <div class="mod-subtitle">
            {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
            @if($unit === 'AIEC')
                @if($setting->last_synced_at) · sinkron sheet terakhir {{ $setting->last_synced_at->diffForHumans() }} @else · belum pernah disinkronkan @endif
                @if($setting->last_status === 'failed')<span style="color:#ef4444;"> · gagal: {{ $setting->last_message }}</span>@endif
            @elseif($unit === 'CBM' || $unit === 'PAINTING')
                · shift yang diharapkan mengikuti roster {{ $unitLabel }}
            @endif
        </div>
    </div>

    @if(! $isExtra)
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem;margin-bottom:1rem;">
            <div class="mod-card" style="padding:.9rem 1rem;">
                <div class="mod-subtitle">Lengkap (3 foto)</div>
                <div style="font-size:1.8rem;font-weight:700;color:{{ $tone[$ts][0] }};">{{ $t['percent'] !== null ? $t['percent'].'%' : '-' }}</div>
                <div class="mod-subtitle">{{ $t['complete'] }} dari {{ $t['expected'] }} laporan shift</div>
            </div>
            <div class="mod-card" style="padding:.9rem 1rem;">
                <div class="mod-subtitle">Masuk</div>
                <div style="font-size:1.8rem;font-weight:700;">{{ $t['submitted'] }}</div>
                <div class="mod-subtitle">belum masuk: {{ $t['expected'] - $t['submitted'] }}</div>
            </div>
        </div>
    @else
        <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;">
            <div class="mod-subtitle">Tambahan: bukti di luar rutinitas harian (tidak ada target)</div>
            <div style="font-size:1.8rem;font-weight:700;">{{ $t['submitted'] }} <span class="mod-subtitle">baris</span></div>
        </div>

        @if($canUpload)
            <div class="mod-card" style="padding:1rem;margin-bottom:1rem;">
                <div style="font-weight:600;margin-bottom:.6rem;">Tambah baris bukti</div>
                <form wire:submit="addExtra" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));gap:.6rem;align-items:end;">
                    <div class="cbm-form-group" style="margin:0;">
                        <label class="cbm-form-label" for="cx-sta">Station</label>
                        <input id="cx-sta" list="cx-stations" type="text" wire:model="extraStation" class="cbm-form-input" maxlength="4" placeholder="CGK" style="text-transform:uppercase;">
                        <datalist id="cx-stations">@foreach($stationOptions as $code)<option value="{{ $code }}">@endforeach</datalist>
                        @error('extraStation')<span class="mod-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="cbm-form-group" style="margin:0;">
                        <label class="cbm-form-label" for="cx-date">Tanggal</label>
                        <input id="cx-date" type="date" wire:model="extraDate" class="cbm-form-input" max="{{ now()->toDateString() }}">
                        @error('extraDate')<span class="mod-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="cbm-form-group" style="margin:0;">
                        <label class="cbm-form-label" for="cx-shift">Shift</label>
                        <select id="cx-shift" wire:model="extraShift" class="cbm-form-input">@foreach($shifts as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select>
                    </div>
                    <div class="cbm-form-group" style="margin:0;">
                        <label class="cbm-form-label" for="cx-note">Keterangan</label>
                        <input id="cx-note" type="text" wire:model="extraNote" class="cbm-form-input" maxlength="200" placeholder="Opsional">
                    </div>
                    <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="addExtra">Tambah</button>
                </form>
            </div>
        @endif
    @endif

    {{-- Station table --}}
    <div class="mod-card mod-card-accent-blue">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>STATION</th><th>SHIFT</th>
                        @if(! $isExtra)<th style="text-align:right;">DIHARAPKAN</th>@endif
                        <th style="text-align:right;">MASUK</th><th style="text-align:right;">LENGKAP</th>
                        @foreach($docLabel as $label)<th style="text-align:right;">{{ strtoupper($label) }}</th>@endforeach
                        @if(! $isExtra)<th style="text-align:right;">%</th>@endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['stations'] as $code => $row)
                        @php $s = $statusOf($row['percent']); @endphp
                        <tr wire:key="cs-{{ $unit }}-{{ $code }}" style="{{ $selected === $code ? 'background:rgba(59,130,246,.08);' : '' }}">
                            <td><strong>{{ $code }}</strong></td>
                            <td class="mod-subtitle">{{ $row['shifts'] ? implode(' + ', $row['shifts']) : ($isExtra ? '' : '—') }}</td>
                            @if(! $isExtra)<td style="text-align:right;">{{ $row['expected'] }}</td>@endif
                            <td style="text-align:right;">{{ $row['submitted'] }}</td>
                            <td style="text-align:right;">{{ $row['complete'] }}</td>
                            @foreach($docLabel as $key => $label)<td style="text-align:right;">{{ $row['docs'][$key] }}</td>@endforeach
                            @if(! $isExtra)
                                <td style="text-align:right;">
                                    <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $tone[$s][0] }};background:{{ $tone[$s][1] }};">{{ $row['percent'] !== null ? $row['percent'].'%' : '-' }}</span>
                                </td>
                            @endif
                            <td style="text-align:right;"><button type="button" wire:click="pick('{{ $code }}')" class="mod-action-btn">{{ $selected === $code ? 'Tutup' : ($canUpload ? 'Rincian & unggah' : 'Rincian') }}</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="9">
                            <div class="mod-empty">
                                <div class="mod-empty-title">Belum ada data</div>
                                <div class="mod-empty-sub">
                                    @if($isExtra) Belum ada bukti tambahan. Gunakan formulir di atas untuk menambah baris.
                                    @elseif($unit === 'AIEC') Jalankan sinkronisasi untuk menarik data dari sheet.
                                    @else Roster {{ $unitLabel }} belum memuat shift pada periode ini. Impor roster dulu.
                                    @endif
                                </div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detail of one station: every expected shift with its three evidence slots --}}
    @if($selected)
        @php
            $row = $data['stations'][$selected];
            $byKey = $entries->groupBy(fn ($e) => $e->work_date->toDateString().'|'.$e->shift)->map(fn ($g) => $g->first());
            $shiftCols = collect($shiftSlots)->pluck(1)->unique()->values()->all();
            $dates = collect($shiftSlots)->pluck(0)->unique()->sortDesc()->values()->all();
            $slotSet = collect($shiftSlots)->mapWithKeys(fn ($s) => [$s[0].'|'.$s[1] => true])->all();
        @endphp

        <div class="mod-card" style="margin-top:1rem;">
            <div style="padding:.9rem 1rem;font-weight:600;">Rincian {{ $selected }} · {{ $unitLabel }}
                @if($canUpload)<span class="mod-subtitle" style="font-weight:400;"> · pilih berkas pada tombol dokumen untuk mengunggah (foto atau PDF, maks. {{ config('compliance.upload.max_mb') }} MB)</span>@endif
            </div>

            @if(! $isExtra && ($row['missing'] || $row['incomplete']))
                <div style="padding:0 1rem 1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(16rem,1fr));gap:1rem;">
                    <div>
                        <div class="mod-subtitle" style="color:#ef4444;">Belum masuk ({{ count($row['missing']) }})</div>
                        <div style="font-size:.85rem;">
                            @forelse(array_slice($row['missing'], -15) as $m)
                                <span style="display:inline-block;margin:.15rem .25rem .15rem 0;padding:.1rem .45rem;border-radius:.4rem;background:rgba(239,68,68,.12);">{{ \Carbon\Carbon::parse($m['date'])->format('d M') }} {{ $m['shift'] }}</span>
                            @empty
                                <span class="mod-subtitle">Tidak ada</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="mod-subtitle" style="color:#f59e0b;">Masuk tapi kurang foto ({{ count($row['incomplete']) }})</div>
                        <div style="font-size:.85rem;">
                            @forelse(array_slice($row['incomplete'], -15) as $m)
                                <span style="display:inline-block;margin:.15rem .25rem .15rem 0;padding:.1rem .45rem;border-radius:.4rem;background:rgba(245,158,11,.14);">{{ \Carbon\Carbon::parse($m['date'])->format('d M') }} {{ $m['shift'] }}: {{ implode(', ', array_map(fn ($d) => $docLabel[$d] ?? $d, $m['lacking'])) }}</span>
                            @empty
                                <span class="mod-subtitle">Tidak ada</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <div class="mod-table-wrap">
                @if($isExtra)
                    <table class="mod-table">
                        <thead><tr><th>TANGGAL</th><th>SHIFT</th><th>KETERANGAN</th><th>BUKTI</th></tr></thead>
                        <tbody>
                            @forelse($entries as $e)
                                <tr wire:key="ce-{{ $e->id }}">
                                    <td style="white-space:nowrap;">{{ $e->work_date->format('D, d M Y') }}</td>
                                    <td>{{ $e->shift }}</td>
                                    <td class="mod-subtitle">{{ $e->note ?: '—' }}</td>
                                    <td class="cp-cell">
                                        @foreach($docLabel as $doc => $label)
                                            @include('livewire.modules.compliance.slot', ['url' => $e->{'url_'.$doc}, 'doc' => $doc, 'label' => $label, 'key' => 'e'.$e->id.'_'.$doc, 'entryId' => $e->id])
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="mod-empty"><div class="mod-empty-title">Belum ada baris</div><div class="mod-empty-sub">Tambahkan baris di atas, lalu unggah buktinya.</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="mod-table">
                        <thead><tr><th>TANGGAL</th>@foreach($shiftCols as $shift)<th>{{ strtoupper($shift) }}</th>@endforeach</tr></thead>
                        <tbody>
                            @forelse($dates as $date)
                                <tr wire:key="cd-{{ $unit }}-{{ $selected }}-{{ $date }}">
                                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}@if($date === now()->toDateString()) <span class="mod-subtitle">· hari ini</span>@endif</td>
                                    @foreach($shiftCols as $shift)
                                        @php $e = $byKey->get($date.'|'.$shift); @endphp
                                        <td class="cp-cell">
                                            @if(isset($slotSet[$date.'|'.$shift]))
                                                @foreach($docLabel as $doc => $label)
                                                    @include('livewire.modules.compliance.slot', ['url' => $e?->{'url_'.$doc}, 'doc' => $doc, 'label' => $label, 'key' => $date.'_'.$shift.'_'.$doc, 'entryId' => $e?->id])
                                                @endforeach
                                            @else
                                                <span class="mod-subtitle">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ 1 + max(1, count($shiftCols)) }}"><div class="mod-empty"><div class="mod-empty-title">Tidak ada shift pada periode ini</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</div>
