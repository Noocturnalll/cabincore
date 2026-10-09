<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />

    @php
        $tones = ['green' => '#34d399', 'yellow' => '#f59e0b', 'red' => '#ef4444', 'none' => '#94a3b8', 'blue' => '#60a5fa'];
        $bgs = ['green' => 'rgba(52,211,153,.15)', 'yellow' => 'rgba(245,158,11,.17)', 'red' => 'rgba(239,68,68,.16)', 'none' => 'rgba(148,163,184,.13)'];
        $st = fn ($v) => \App\Services\Kpi\AchievementStatus::for($v, 100);
        $chip = fn ($v) => '<span class="kd-chip" style="color:'.$tones[$st($v)].';background:'.$bgs[$st($v)].';">'.($v !== null ? rtrim(rtrim(number_format($v, 1), '0'), '.').'%' : '–').'</span>';
        $num = fn ($v, $d = 0) => $v === null ? '–' : number_format($v, $d);
        $arrow = fn ($k) => $sort === $k ? '<span class="arrow">'.($dir === 'asc' ? '▲' : '▼').'</span>' : '';
        $showAccuracy = in_array($team, ['ALL', 'CBM'], true);
        $teamLabel = $teamOptions[$team] ?? $team;
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="Dashboard KPI" :subtitle="'Man hours, document accuracy, LGT, dan compliance per station · '.$teamLabel" accent="blue" eyebrow="Reporting" />

    <div class="kd-bar">
        <div class="kd-group">
            <div class="kd-seg" role="tablist" aria-label="Periode">
                @foreach(['day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan'] as $k => $l)
                    <button type="button" role="tab" aria-selected="{{ $kind === $k ? 'true' : 'false' }}" wire:click="setKind('{{ $k }}')" class="{{ $kind === $k ? 'on' : '' }}">{{ $l }}</button>
                @endforeach
            </div>
            <div class="kd-nav">
                <button type="button" wire:click="move(-1)" aria-label="Periode sebelumnya">‹</button>
                <span class="kd-label">{{ $period->label() }}</span>
                <button type="button" wire:click="move(1)" aria-label="Periode berikutnya">›</button>
                <button type="button" wire:click="today" aria-label="Kembali ke periode ini" title="Hari ini" style="width:auto;padding:0 .7rem;font-size:.8rem;font-weight:700;">Kini</button>
            </div>
        </div>
        <div class="kd-group">
            @if($canPickStation)
                <select wire:model.live="station" aria-label="Station">
                    <option value="">Semua station</option>
                    @foreach($stationOptions as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </select>
            @endif
            @if($seesAll)
                <select wire:model.live="teamPick" aria-label="Tim">
                    @foreach($teamOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            @elseif($canPickStation)
                <span class="kd-sub">{{ $teamLabel }}</span>
            @else
                <span class="kd-sub">{{ $lockedStation ? 'Station '.$lockedStation : 'Semua station' }} · {{ $teamLabel }}</span>
            @endif
            <input type="date" wire:model.live="date" aria-label="Tanggal acuan">
        </div>
        @php
            $sections = array_filter([
                'sec-attention' => 'Perlu perhatian',
                'sec-tiles' => 'Ringkasan modul',
                'sec-charts' => collect($charts)->flatten(1)->isNotEmpty() ? 'Grafik' : null,
                'sec-ops' => count($pivot['rows']) && collect($pivot['blocks'])->except('cleaning')->isNotEmpty() ? 'Open · Closed per station' : null,
                'sec-cleaning' => isset($pivot['blocks']['cleaning']) && count($pivot['rows']) ? 'Cleaning per tipe' : null,
                'sec-days' => ($period->days() > 1 && count($pivot['days'])) ? 'Per hari' : null,
                'sec-kpi' => 'KPI man hours',
            ]);
        @endphp
        <nav class="kd-snav" aria-label="Bagian halaman"
             x-data="{ active: '', spy() { let cur = ''; document.querySelectorAll('[data-kd-sec]').forEach(el => { if (el.getBoundingClientRect().top <= 230) cur = el.id }); this.active = cur || this.active } }"
             x-init="spy()" x-on:scroll.window.passive="spy()">
            @foreach($sections as $id => $label)
                <button type="button" :class="{ on: active === '{{ $id }}' }" x-on:click="document.getElementById('{{ $id }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); active = '{{ $id }}'">{{ $label }}</button>
            @endforeach
        </nav>
    </div>

    {{-- What needs chasing today --}}
    @if(count($attention))
        <section class="kd-panel" id="sec-attention" data-kd-sec aria-label="Perlu perhatian" style="margin-bottom:1rem;">
            <h2><span>Perlu perhatian</span><span class="kd-sub">{{ count($attention) }} hal · klik untuk membuka</span></h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(17rem,1fr));gap:.5rem;padding:.75rem 1rem 1rem;">
                @foreach($attention as $a)
                    @if(\Illuminate\Support\Facades\Route::has($a['route']))
                        <a href="{{ route($a['route']) }}" wire:navigate wire:key="att-{{ $loop->index }}" style="display:flex;align-items:center;gap:.75rem;padding:.6rem .8rem;border:1px solid var(--cbm-divider);border-radius:.75rem;text-decoration:none;color:inherit;">
                            <span class="kd-chip" style="min-width:2.4rem;justify-content:center;color:{{ $a['tone'] === 'red' ? $tones['red'] : $tones['yellow'] }};background:{{ $a['tone'] === 'red' ? $bgs['red'] : $bgs['yellow'] }};">{{ number_format($a['count']) }}</span>
                            <span style="font-size:.82rem;line-height:1.25;">{{ $a['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @else
        <div class="kd-sub" id="sec-attention" data-kd-sec style="margin:.25rem 0 1rem;">Tidak ada yang perlu dikejar saat ini. ✓</div>
    @endif

    {{-- One tile per module in the menu --}}
    <div id="sec-tiles" data-kd-sec></div>
    @foreach($tileGroups as $gKey => $gLabel)
        @if(! empty($tiles[$gKey]))
            <div class="kd-sec">{{ $gLabel }}</div>
            <div class="kd-cards" style="margin-bottom:.25rem;">
                @foreach($tiles[$gKey] as $tile)
                    @php $toneColor = $tones[$tile['tone']] ?? $tones['none']; @endphp
                    @if(\Illuminate\Support\Facades\Route::has($tile['route']))
                        <a href="{{ route($tile['route']) }}" wire:navigate class="kd-card kd-tile" style="--tone: {{ $toneColor }}" wire:key="tile-{{ $tile['key'] }}">
                            <div class="t">{{ $tile['title'] }}</div>
                            <div class="v">{{ $tile['value'] }}</div>
                            <div class="s">{{ $tile['sub'] }}</div>
                            @if(($tile['delta'] ?? null) !== null)
                                <div class="s" style="font-weight:700;color:{{ $tile['delta'] >= 0 ? $tones['green'] : $tones['red'] }};">{{ $tile['delta'] >= 0 ? '▲' : '▼' }} {{ abs($tile['delta']) }} poin vs periode lalu</div>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    @endforeach

    {{-- Charts: percentages first --}}
    @if(collect($charts)->flatten(1)->isNotEmpty())
        <div class="kd-sec" id="sec-charts" data-kd-sec>Grafik · {{ $period->label() }}</div>
        @foreach(['production' => 'Produksi', 'cleaning' => 'Aircraft Cleaning (AIEC)', 'kpi' => 'KPI man hours'] as $group => $groupLabel)
            @if(! empty($charts[$group]))
                <div class="kd-sub" style="margin:.35rem 0 .45rem;font-weight:700;">{{ $groupLabel }}</div>
                <div class="kd-chart-grid">
                    @foreach($charts[$group] as $chartConfig)
                        <x-kd-chart :config="$chartConfig" />
                    @endforeach
                </div>
            @endif
        @endforeach
    @endif

    @php
        $cellOf = function (?array $c) use ($chip) {
            if (! $c || ! $c['total']) { return '<span class="kd-sub">–</span>'; }
            return '<div class="kd-cell">'.$c['closed'].' / '.$c['total'].' '.$chip($c['rate']).'<small>'.$c['open'].' open</small></div>';
        };
        $opsBlocks = collect($pivot['blocks'])->except('cleaning');
    @endphp

    {{-- Open / closed / rate per station: WO, DMI, NSRDI, unplanned, CML --}}
    @if($opsBlocks->isNotEmpty() && count($pivot['rows']))
        <section class="kd-panel" id="sec-ops" data-kd-sec aria-label="Open dan closed per station">
            <h2><span>Open · Closed · Rate per station</span><span class="kd-sub">WO, DMI, NSRDI sesuai DJA · {{ $period->label() }}</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th>Station</th>@foreach($opsBlocks as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($pivot['rows'] as $sta => $cells)
                            @if(collect($opsBlocks)->keys()->contains(fn ($b) => ($cells[$b]['total'] ?? 0) > 0))
                                <tr wire:key="op-{{ $sta }}"><td><strong>{{ $sta }}</strong></td>@foreach($opsBlocks as $b => $label)<td>{!! $cellOf($cells[$b] ?? null) !!}</td>@endforeach</tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot><tr><td>Total</td>@foreach($opsBlocks as $b => $label)<td>{!! $cellOf($pivot['total'][$b] ?? null) !!}</td>@endforeach</tr></tfoot>
                </table>
            </div>
        </section>
    @endif

    {{-- Aircraft Cleaning per type --}}
    @if(isset($pivot['blocks']['cleaning']) && count($pivot['rows']))
        <section class="kd-panel" id="sec-cleaning" data-kd-sec aria-label="Aircraft cleaning per station">
            <h2><span>Aircraft Cleaning per station</span><span class="kd-sub">closed / total per tipe · {{ $period->label() }}</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th>Station</th>@foreach($pivot['cleaning_types'] as $type)<th>{{ $type }}</th>@endforeach<th>Total</th></tr></thead>
                    <tbody>
                        @foreach($pivot['rows'] as $sta => $cells)
                            @if(($cells['cleaning']['total'] ?? 0) > 0)
                                <tr wire:key="cl-{{ $sta }}"><td><strong>{{ $sta }}</strong></td>@foreach($pivot['cleaning_types'] as $type)<td>{!! $cellOf($cells['cleaning:'.$type] ?? null) !!}</td>@endforeach<td>{!! $cellOf($cells['cleaning'] ?? null) !!}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot><tr><td>Total</td>@foreach($pivot['cleaning_types'] as $type)<td>{!! $cellOf($pivot['total']['cleaning:'.$type] ?? null) !!}</td>@endforeach<td>{!! $cellOf($pivot['total']['cleaning'] ?? null) !!}</td></tr></tfoot>
                </table>
            </div>
        </section>
    @endif

    {{-- Day by day inside the week / month --}}
    @if($period->days() > 1 && count($pivot['days']))
        <section class="kd-panel" id="sec-days" data-kd-sec aria-label="Per hari">
            <h2><span>Per hari</span><span class="kd-sub">closed rate dari semua station yang dipilih</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th>Tanggal</th>@foreach($pivot['blocks'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($pivot['days'] as $day => $cells)
                            <tr wire:key="dy-{{ $day }}"><td><strong>{{ \Carbon\Carbon::parse($day)->translatedFormat('D, d M') }}</strong></td>@foreach($pivot['blocks'] as $b => $label)<td>{!! $cellOf($cells[$b] ?? null) !!}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="kd-sec" id="sec-kpi" data-kd-sec>KPI man hours per station</div>

    {{-- Summary cards --}}
    <div class="kd-cards">
        <div class="kd-card" style="--tone: {{ $tones[$st($total['utilisation'])] }}">
            <div class="t">Utilisasi man hours</div>
            <div class="v">{{ $total['utilisation'] !== null ? $num($total['utilisation'], 1).'%' : '–' }}</div>
            <div class="s">{{ $num($total['used'], 1) }} dari {{ $num($total['capacity'], 1) }} jam</div>
        </div>
        <div class="kd-card" style="--tone: #60a5fa">
            <div class="t">MP rata-rata / hari</div>
            <div class="v">{{ $num($total['mp_avg'], 1) }}</div>
            <div class="s">dari roster, tim {{ $teamLabel }}</div>
        </div>
        @if($showAccuracy)
            <div class="kd-card" style="--tone: {{ $tones[$st($total['accuracy'])] }}">
                <div class="t">Document accuracy</div>
                <div class="v">{{ $total['accuracy'] !== null ? $num($total['accuracy'], 1).'%' : '–' }}</div>
                <div class="s">CML + NSRDI tercatat di eMRO</div>
            </div>
        @endif
        @if(in_array($team, ['ALL', 'CBM'], true))
            <div class="kd-card" style="--tone: {{ $tones[$st($total['lgt_cbm'])] }}">
                <div class="t">LGT CBM</div>
                <div class="v">{{ $total['lgt_cbm'] !== null ? $num($total['lgt_cbm'], 1).'%' : '–' }}</div>
                <div class="s">terlaksana dari rencana</div>
            </div>
        @endif
        @if(in_array($team, ['ALL', 'AIEC'], true))
            <div class="kd-card" style="--tone: {{ $tones[$st($total['lgt_aiec'])] }}">
                <div class="t">LGT AIEC</div>
                <div class="v">{{ $total['lgt_aiec'] !== null ? $num($total['lgt_aiec'], 1).'%' : '–' }}</div>
                <div class="s">terlaksana dari rencana</div>
            </div>
        @endif
        <div class="kd-card" style="--tone: {{ $tones[$st($total['compliance'])] }}">
            <div class="t">Compliance harian</div>
            <div class="v">{{ $total['compliance'] !== null ? $num($total['compliance'], 1).'%' : '–' }}</div>
            <div class="s">briefing · attlist · 5R</div>
        </div>
    </div>

    {{-- Pivot per station --}}
    <section class="kd-panel" aria-label="Pivot per station">
        <h2><span>Pivot per station</span><span class="kd-sub">klik judul kolom untuk mengurutkan</span></h2>
        <div class="kd-wrap">
            @if($rows->isEmpty())
                <div class="kd-empty">Belum ada data pada periode ini.<br><span class="kd-sub">Impor roster, LGT, accuracy, dan laporan leader agar angka terisi.</span></div>
            @else
                <table class="kd-table">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('station')">Station {!! $arrow('station') !!}</th>
                            <th wire:click="sortBy('mp_avg')" title="Rata-rata orang bekerja per hari">MP/hari {!! $arrow('mp_avg') !!}</th>
                            <th wire:click="sortBy('capacity')" title="MP × jam efektif per shift">Kapasitas (jam) {!! $arrow('capacity') !!}</th>
                            <th wire:click="sortBy('used')">Terpakai (jam) {!! $arrow('used') !!}</th>
                            <th wire:click="sortBy('utilisation')">Utilisasi {!! $arrow('utilisation') !!}</th>
                            @if($showAccuracy)<th wire:click="sortBy('accuracy')">Doc. accuracy {!! $arrow('accuracy') !!}</th>@endif
                            @if(in_array($team, ['ALL', 'CBM'], true))<th wire:click="sortBy('lgt_cbm')">LGT CBM {!! $arrow('lgt_cbm') !!}</th>@endif
                            @if(in_array($team, ['ALL', 'AIEC'], true))<th>LGT AIEC</th>@endif
                            <th wire:click="sortBy('compliance')">Compliance {!! $arrow('compliance') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            <tr wire:key="kd-{{ $r['station'] }}">
                                <td><strong>{{ $r['station'] }}</strong></td>
                                <td>{{ $num($r['mp_avg'], 1) }}</td>
                                <td>{{ $num($r['capacity'], 0) }}</td>
                                <td>{{ $num($r['used'], 1) }}</td>
                                <td>{!! $chip($r['utilisation']) !!}</td>
                                @if($showAccuracy)<td>{!! $chip($r['accuracy']) !!}</td>@endif
                                @if(in_array($team, ['ALL', 'CBM'], true))<td>{!! $chip($r['lgt_cbm']) !!}</td>@endif
                                @if(in_array($team, ['ALL', 'AIEC'], true))<td>{!! $chip($r['lgt_aiec']) !!}</td>@endif
                                <td>{!! $chip($r['compliance']) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td>{{ $num($total['mp_avg'], 1) }}</td>
                            <td>{{ $num($total['capacity'], 0) }}</td>
                            <td>{{ $num($total['used'], 1) }}</td>
                            <td>{!! $chip($total['utilisation']) !!}</td>
                            @if($showAccuracy)<td>{!! $chip($total['accuracy']) !!}</td>@endif
                            @if(in_array($team, ['ALL', 'CBM'], true))<td>{!! $chip($total['lgt_cbm']) !!}</td>@endif
                            @if(in_array($team, ['ALL', 'AIEC'], true))<td>{!! $chip($total['lgt_aiec']) !!}</td>@endif
                            <td>{!! $chip($total['compliance']) !!}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </section>

    {{-- Per team (only when every team is in view) --}}
    @if($team === 'ALL' && count($teamsData))
        <section class="kd-panel" aria-label="Ringkasan per tim">
            <h2><span>Ringkasan per tim (roster)</span><span class="kd-sub">kapasitas dari roster, semua station yang dipilih</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th>Tim</th><th>MP/hari</th><th>Kapasitas (jam)</th></tr></thead>
                    <tbody>
                        @foreach(['CBM', 'AIEC', 'PAINTING', 'IRREG', 'FINISHING'] as $t)
                            @if(isset($teamsData[$t]))
                                <tr wire:key="kt-{{ $t }}"><td><strong>{{ $teamOptions[$t] }}</strong></td><td>{{ $num($teamsData[$t]['mp_avg'], 1) }}</td><td>{{ $num($teamsData[$t]['hours'], 0) }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Where the hours come from, and how complete they are --}}
    @if(count($coverage))
        <section class="kd-panel" aria-label="Kelengkapan data jam">
            <h2><span>Kelengkapan data man hours</span><span class="kd-sub">angka terpakai hanya dari catatan yang sudah berjam</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th>Sumber</th><th>Catatan</th><th>Berjam</th><th>Lengkap</th></tr></thead>
                    <tbody>
                        @foreach($coverage as $label => $c)
                            @php $pct = $c['records'] ? round($c['timed'] / $c['records'] * 100) : null; @endphp
                            <tr wire:key="cv-{{ $label }}">
                                <td><strong>{{ $label }}</strong></td>
                                <td>{{ number_format($c['records']) }}</td>
                                <td>{{ number_format($c['timed']) }}</td>
                                <td><span class="kd-chip" style="color:{{ $pct !== null && $pct >= 90 ? $tones['green'] : ($pct >= 50 ? $tones['yellow'] : $tones['red']) }};background:{{ $pct !== null && $pct >= 90 ? $bgs['green'] : ($pct >= 50 ? $bgs['yellow'] : $bgs['red']) }};">{{ $pct !== null ? $pct.'%' : '–' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
