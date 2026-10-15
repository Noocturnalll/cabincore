<div>
    <x-master.page-header title="Pivot Lanjutan" subtitle="WG, operator, umur pekerjaan Open, alasan Open, planned vs unplanned, dan man-hour CML / DBI." accent="blue" eyebrow="Reporting" />

    <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;display:flex;gap:.5rem;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
        <select wire:model.live="kind" class="cbm-form-input" style="width:auto;" aria-label="Periode">
            <option value="day">Harian</option>
            <option value="week">Mingguan</option>
            <option value="month">Bulanan</option>
        </select>
        <button type="button" wire:click="shift(-1)" class="mod-action-btn" aria-label="Sebelumnya">‹</button>
        <strong>{{ $period->from->translatedFormat('d M Y') }}@if(! $period->from->isSameDay($period->to)) – {{ $period->to->translatedFormat('d M Y') }}@endif</strong>
        <button type="button" wire:click="shift(1)" class="mod-action-btn" aria-label="Berikutnya">›</button>
        <input type="date" wire:model.live="date" class="cbm-form-input" style="width:auto;" aria-label="Tanggal">
    </div>

    @php
        $mods = ['wo' => 'WO', 'dmi' => 'DMI', 'nsrdi' => 'NSRDI'];
        $pct = fn ($c, $t) => $t ? round($c / $t * 100) . '%' : '-';
    @endphp

    {{-- Man hours (CML + DBI) --}}
    <div class="mod-card mod-card-accent-blue" style="margin-bottom:1rem;">
        <div style="padding:1rem 1.25rem .25rem;"><strong>Man-hour CML &amp; DBI</strong>
            <span class="mod-subtitle"> · {{ number_format($data['manHours']['hours'], 1) }} jam dari {{ number_format($data['manHours']['timed']) }} dari {{ number_format($data['manHours']['jobs']) }} pekerjaan berjam
                @if($data['manHours']['avgPerJob'] !== null) · rata-rata {{ $data['manHours']['avgPerJob'] }} jam/pekerjaan @endif
                @if($data['manHours']['coverage'] !== null) · {{ $data['manHours']['coverage'] }}% punya jam mulai &amp; selesai @endif</span>
        </div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>STATION</th><th style="text-align:right;">CML JOB</th><th style="text-align:right;">CML JAM</th><th style="text-align:right;">DBI JOB</th><th style="text-align:right;">DBI JAM</th></tr></thead>
                <tbody>
                    @forelse($data['manHours']['perStation'] as $station => $row)
                        <tr wire:key="mh-{{ $station }}">
                            <td><strong>{{ $station }}</strong></td>
                            <td style="text-align:right;">{{ $row['CML']['jobs'] ?? 0 }}</td><td style="text-align:right;">{{ $row['CML']['hours'] ?? 0 }}</td>
                            <td style="text-align:right;">{{ $row['DBI']['jobs'] ?? 0 }}</td><td style="text-align:right;">{{ $row['DBI']['hours'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="mod-empty"><div class="mod-empty-title">Belum ada CML / DBI pada periode ini</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(count($data['manHours']['perShift']))
            <div style="padding:.75rem 1.25rem;" class="mod-subtitle">
                Per shift: @foreach($data['manHours']['perShift'] as $s) <strong>{{ $s['shift'] }}</strong> {{ $s['jobs'] }} job / {{ $s['hours'] }} jam @if(! $loop->last) · @endif @endforeach
            </div>
        @endif
    </div>

    {{-- Work group + operator --}}
    @foreach(['byWg' => 'Per Work Group (WG)', 'byAoc' => 'Per Operator / AOC'] as $key => $title)
        <div class="mod-card mod-card-accent-green" style="margin-bottom:1rem;">
            <div style="padding:1rem 1.25rem .25rem;"><strong>{{ $title }}</strong> <span class="mod-subtitle">· closed / total</span></div>
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead><tr><th>{{ $key === 'byWg' ? 'WG' : 'OPERATOR' }}</th>@foreach($mods as $m => $label)<th style="text-align:right;">{{ $label }}</th>@endforeach<th style="text-align:right;">% CLOSED</th></tr></thead>
                    <tbody>
                        @forelse($data[$key] as $name => $row)
                            @php
                                $c = collect($row)->sum('closed');
                                $t = collect($row)->sum('total');
                            @endphp
                            <tr wire:key="{{ $key }}-{{ $name }}">
                                <td><strong>{{ $name }}</strong></td>
                                @foreach($mods as $m => $label)
                                    <td style="text-align:right;">{{ isset($row[$m]) ? $row[$m]['closed'].' / '.$row[$m]['total'] : '-' }}</td>
                                @endforeach
                                <td style="text-align:right;"><strong>{{ $pct($c, $t) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="mod-empty"><div class="mod-empty-title">Belum ada data pada periode ini</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- WO planner-sheet columns --}}
    @foreach($data['bySheet'] as $label => $rows)
        <div class="mod-card mod-card-accent-green" style="margin-bottom:1rem;">
            <div style="padding:1rem 1.25rem .25rem;"><strong>WO per {{ $label }}</strong> <span class="mod-subtitle">· closed / total (15 teratas)</span></div>
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead><tr><th>{{ $label }}</th><th style="text-align:right;">CLOSED</th><th style="text-align:right;">TOTAL</th><th style="text-align:right;">% CLOSED</th></tr></thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr wire:key="sh-{{ $label }}-{{ $r['name'] }}"><td><strong>{{ $r['name'] }}</strong></td><td style="text-align:right;">{{ $r['closed'] }}</td><td style="text-align:right;">{{ $r['total'] }}</td><td style="text-align:right;">{{ $pct($r['closed'], $r['total']) }}</td></tr>
                        @empty
                            <tr><td colspan="4"><div class="mod-empty"><div class="mod-empty-title">Belum ada data</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- Aging of open work --}}
    <div class="mod-card mod-card-accent-yellow" style="margin-bottom:1rem;">
        <div style="padding:1rem 1.25rem .25rem;"><strong>Umur pekerjaan yang masih Open</strong> <span class="mod-subtitle">· sampai hari ini, tidak dibatasi periode</span></div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>UMUR</th>@foreach($mods as $label)<th style="text-align:right;">{{ $label }}</th>@endforeach<th style="text-align:right;">TOTAL</th></tr></thead>
                <tbody>
                    @foreach($data['aging'] as $bucket => $row)
                        <tr wire:key="age-{{ $bucket }}">
                            <td><strong>{{ $bucket }}</strong></td>
                            @foreach($mods as $m => $label)<td style="text-align:right;">{{ $row[$m] ?? 0 }}</td>@endforeach
                            <td style="text-align:right;"><strong>{{ array_sum($row) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Reasons --}}
    <div class="mod-card mod-card-accent-red" style="margin-bottom:1rem;">
        <div style="padding:1rem 1.25rem .25rem;"><strong>Alasan pekerjaan Open</strong> <span class="mod-subtitle">· 10 terbanyak (WO + DMI + NSRDI)</span></div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>CODE REASON</th><th style="text-align:right;">JUMLAH</th></tr></thead>
                <tbody>
                    @forelse($data['reasons'] as $reason => $n)
                        <tr wire:key="rs-{{ $reason }}"><td>{{ $reason }}</td><td style="text-align:right;"><strong>{{ $n }}</strong></td></tr>
                    @empty
                        <tr><td colspan="2"><div class="mod-empty"><div class="mod-empty-title">Tidak ada pekerjaan Open</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Planned vs unplanned --}}
    <div class="mod-card mod-card-accent-blue">
        <div style="padding:1rem 1.25rem .25rem;"><strong>Planned vs Unplanned per station</strong> <span class="mod-subtitle">· WO + DMI + NSRDI</span></div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>STATION</th><th style="text-align:right;">PLANNED</th><th style="text-align:right;">UNPLANNED</th><th style="text-align:right;">% UNPLANNED</th></tr></thead>
                <tbody>
                    @forelse($data['planned'] as $station => $row)
                        <tr wire:key="pl-{{ $station }}">
                            <td><strong>{{ $station }}</strong></td>
                            <td style="text-align:right;">{{ $row['planned'] }}</td>
                            <td style="text-align:right;">{{ $row['unplanned'] }}</td>
                            <td style="text-align:right;">{{ $pct($row['unplanned'], $row['planned'] + $row['unplanned']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="mod-empty"><div class="mod-empty-title">Belum ada data pada periode ini</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
