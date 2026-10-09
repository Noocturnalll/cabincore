<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />
    <style>
        .np-table { border-collapse:separate; border-spacing:0; width:100%; font-size:.82rem; }
        .np-table th, .np-table td { padding:.45rem .5rem; text-align:center; border-bottom:1px solid var(--cbm-divider); white-space:nowrap; font-variant-numeric:tabular-nums; }
        .np-table thead th { background:var(--cbm-card-bg); color:var(--cbm-text-muted); font-size:.68rem; letter-spacing:.05em; text-transform:uppercase; position:sticky; top:0; z-index:2; }
        .np-table .day { background:rgba(148,163,184,.12); border-left:2px solid var(--cbm-divider); color:var(--cbm-text); font-size:.74rem; }
        .np-table .l { text-align:left; position:sticky; left:0; z-index:1; background:var(--cbm-card-bg); }
        .np-table thead .l { z-index:3; }
        .np-table .sta { font-weight:800; }
        .np-cell { border-radius:.35rem; padding:.2rem .55rem; font-weight:700; display:inline-block; min-width:1.9rem; }
        .np-ok { background:rgba(52,211,153,.2); color:#34d399; }
        .np-short { background:rgba(251,146,60,.22); color:#fb923c; }
        .np-wait { background:rgba(56,189,248,.16); color:#38bdf8; }
        .np-zero { color:var(--cbm-text-muted); opacity:.45; }
        .np-total td { font-weight:800; background:rgba(59,130,246,.12); }
        .np-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.35rem; margin-bottom:.9rem; scrollbar-width:none; }
        .np-tabs::-webkit-scrollbar { display:none; }
        .np-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.45rem .95rem; font-size:.85rem; font-weight:700; cursor:pointer; }
        .np-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
        .np-legend { display:flex; gap:1rem; flex-wrap:wrap; font-size:.75rem; color:var(--cbm-text-muted); margin:.25rem 0 1rem; }
    </style>

    @php
        // one cell: deployed / closed, coloured like the workbook
        $cell = function ($deploy, $closed, $pending) {
            if ($pending) { return '<span class="np-cell np-wait">'.$deploy.'</span>'; }
            if ($deploy === 0) { return '<span class="np-zero">·</span>'; }
            return '<span class="np-cell '.($closed >= $deploy ? 'np-ok' : 'np-short').'">'.$deploy.'</span>';
        };
        $cellClosed = function ($deploy, $closed, $pending) {
            if ($pending) { return '<span class="np-cell np-wait">'.$closed.'</span>'; }
            if ($deploy === 0) { return '<span class="np-zero">·</span>'; }
            return '<span class="np-cell '.($closed >= $deploy ? 'np-ok' : 'np-short').'">'.$closed.'</span>';
        };
        $totalCell = fn ($deploy, $closed) => '<span class="np-cell '.($deploy > 0 && $closed < $deploy ? 'np-short' : 'np-ok').'">'.$closed.'</span>';
        $fmt = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
        $days = $data['days'];
        $pending = $data['pending'];
        $label = $start->translatedFormat('j').' – '.$end->translatedFormat('j F Y');
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="NSRDI per AOC" :subtitle="'Deploy dan closed NSRDI per AOC, station, dan hari DJA. Periode '.strtoupper($label).'. Target per hari dari Master Sistem.'" accent="blue" eyebrow="Reporting" />

    <div class="kd-bar" style="position:static;">
        <div class="kd-group">
            <div class="kd-nav">
                <button type="button" wire:click="move(-1)" aria-label="Minggu sebelumnya">‹</button>
                <span class="kd-label">{{ $label }}</span>
                <button type="button" wire:click="move(1)" aria-label="Minggu berikutnya">›</button>
                <button type="button" wire:click="thisWeek" style="width:auto;padding:0 .7rem;font-size:.8rem;font-weight:700;">Kini</button>
            </div>
        </div>
        <div class="kd-group"><input type="date" wire:model.live="date" aria-label="Tanggal acuan"></div>
    </div>

    <div class="np-tabs" role="tablist" aria-label="AOC">
        <button type="button" role="tab" wire:click="setTab('ALL')" class="np-tab {{ $tab === 'ALL' ? 'on' : '' }}">Semua AOC</button>
        @foreach($tabs as $code => $a)
            <button type="button" role="tab" wire:click="setTab('{{ $code }}')" class="np-tab {{ $tab === $code ? 'on' : '' }}">{{ $code }}</button>
        @endforeach
    </div>

    <div class="np-legend">
        <span><span class="np-cell np-ok">n</span> semua closed</span>
        <span><span class="np-cell np-short">n</span> ada yang belum closed</span>
        <span><span class="np-cell np-wait">n</span> hari belum selesai</span>
    </div>

    @if($tab === 'ALL')
        {{-- Overview: every AOC, CBM and PAINTING --}}
        <section class="kd-panel" aria-label="Ringkasan semua AOC">
            <h2><span>NSRDI AOC · CBM | Periode {{ $label }}</span></h2>
            <div class="kd-wrap">
                <table class="np-table">
                    <thead>
                        <tr>
                            <th class="l" rowspan="2">AOC</th><th rowspan="2">Kapasitas / hari</th><th rowspan="2">Defer</th>
                            @foreach($days as $i => $d)<th class="day" colspan="2">{{ \Carbon\Carbon::parse($d)->format('j M') }}</th>@endforeach
                            <th rowspan="2">Total deploy</th><th rowspan="2">Total closed</th>
                        </tr>
                        <tr>@foreach($days as $d)<th class="day">Deploy</th><th>Closed</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach($data['aocs'] as $code => $a)
                            @if($a['totals']['deploy'] > 0 || $a['capacity'] > 0)
                                @foreach(['CBM', 'PAINTING'] as $cat)
                                    @php $t = $a['totals_by_category'][$cat]; @endphp
                                    @if($cat === 'CBM' || $t['deploy'] > 0)
                                        <tr wire:key="ov-{{ $code }}-{{ $cat }}">
                                            @if($cat === 'CBM')
                                                <td class="l sta" rowspan="{{ $a['totals_by_category']['PAINTING']['deploy'] > 0 ? 2 : 1 }}">{{ $code }}<div class="kd-sub" style="font-weight:400;">{{ $aocNames[$code] ?? '' }}</div></td>
                                                <td rowspan="{{ $a['totals_by_category']['PAINTING']['deploy'] > 0 ? 2 : 1 }}">{{ $fmt($a['capacity']) }} <span class="kd-sub">({{ $fmt($a['capacity'] * 7) }})</span></td>
                                            @endif
                                            <td>{{ $cat }}</td>
                                            @foreach($t['days'] as $i => [$dep, $clo])
                                                <td class="day">{!! $cell($dep, $clo, $pending[$i]) !!}</td><td>{!! $cellClosed($dep, $clo, $pending[$i]) !!}</td>
                                            @endforeach
                                            <td>{{ $t['deploy'] }}</td><td>{!! $totalCell($t['deploy'], $t['closed']) !!}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="np-total">
                            <td class="l">Grand total</td><td>{{ $fmt($data['overview']['capacity']) }}</td><td></td>
                            @foreach($data['overview']['days'] as $i => [$dep, $clo])<td class="day">{{ $dep }}</td><td>{{ $clo }}</td>@endforeach
                            <td>{{ $data['overview']['deploy'] }}</td><td>{{ $data['overview']['closed'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @else
        @php $a = $data['aocs'][$tab] ?? null; @endphp
        @if($a)
            <section class="kd-panel" aria-label="NSRDI {{ $tab }}">
                <h2><span>NSRDI {{ $tab }} · act per station CBM | Periode {{ $label }}</span><span class="kd-sub">{{ $aocNames[$tab] ?? '' }}</span></h2>
                <div class="kd-wrap">
                    @if(empty($a['stations']))
                        <div class="kd-empty">Belum ada NSRDI deploy atau target untuk AOC ini pada minggu ini.</div>
                    @else
                        <table class="np-table">
                            <thead>
                                <tr>
                                    <th class="l" rowspan="2">Station</th><th rowspan="2">Kapasitas / hari</th><th rowspan="2">Defer</th>
                                    @foreach($days as $d)<th class="day" colspan="2">{{ \Carbon\Carbon::parse($d)->format('j M') }}</th>@endforeach
                                    <th rowspan="2">Total deploy</th><th rowspan="2">Total closed</th>
                                </tr>
                                <tr>@foreach($days as $d)<th class="day">Deploy</th><th>Closed</th>@endforeach</tr>
                            </thead>
                            <tbody>
                                @foreach($a['stations'] as $station => $row)
                                    @php $cats = $row['categories']; $n = count($cats); @endphp
                                    @foreach($cats as $cat => $c)
                                        <tr wire:key="np-{{ $tab }}-{{ $station }}-{{ $cat }}">
                                            @if($loop->first)
                                                <td class="l sta" rowspan="{{ $n }}">{{ $station }}</td>
                                                <td rowspan="{{ $n }}">{{ $fmt($row['capacity']) }}</td>
                                            @endif
                                            <td>{{ $cat }}</td>
                                            @foreach($c['days'] as $i => [$dep, $clo])
                                                <td class="day">{!! $cell($dep, $clo, $pending[$i]) !!}</td><td>{!! $cellClosed($dep, $clo, $pending[$i]) !!}</td>
                                            @endforeach
                                            <td>{{ $c['deploy'] }}</td><td>{!! $totalCell($c['deploy'], $c['closed']) !!}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="np-total">
                                    <td class="l">Grand total</td><td>{{ $fmt($a['capacity']) }}</td><td></td>
                                    @foreach($a['totals']['days'] as $i => [$dep, $clo])<td class="day">{{ $dep }}</td><td>{{ $clo }}</td>@endforeach
                                    <td>{{ $a['totals']['deploy'] }}</td><td>{{ $a['totals']['closed'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                </div>
            </section>
        @endif
    @endif
</div>
