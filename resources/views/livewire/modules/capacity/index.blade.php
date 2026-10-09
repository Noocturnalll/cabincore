<div>
    <style>
        .cap-targets { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr)); gap: .875rem; padding: 1rem 1.25rem 0; }
        .cap-target { border: 1px solid var(--cbm-card-border); border-left-width: 4px; border-radius: .875rem; padding: .75rem 1rem; background: var(--cbm-card-bg); }
        .cap-target.ok { border-left-color: #10b981; background: rgba(16,185,129,.08); }
        .cap-target.warn { border-left-color: #f59e0b; background: rgba(245,158,11,.08); }
        .cap-target-head { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; }
        .cap-target-name { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--cbm-text-muted); }
        .cap-target-value { font-size: 1.5rem; font-weight: 800; color: var(--cbm-text); line-height: 1.1; }
        .cap-target-value small { font-size: .85rem; font-weight: 700; color: var(--cbm-text-muted); }
        .cap-target-note { font-size: .75rem; font-weight: 600; margin-top: .35rem; }
        .cap-target.ok .cap-target-note { color: #10b981; }
        .cap-target.warn .cap-target-note { color: #d97706; }
        .cap-bar { height: 5px; border-radius: 62.4375rem; background: rgba(148,163,184,.25); margin-top: .5rem; overflow: hidden; }
        .cap-bar > span { display: block; height: 100%; border-radius: inherit; }
        .cap-target.ok .cap-bar > span { background: #10b981; }
        .cap-target.warn .cap-bar > span { background: #f59e0b; }

        .capacity-table { white-space: nowrap; }
        .capacity-table th, .capacity-table td { border: 1px solid var(--cbm-divider); padding: .35rem .6rem; vertical-align: middle; text-align: center; }
        .capacity-table thead th { position: sticky; z-index: 2; background: var(--cbm-card-bg); box-shadow: none; text-align: center; }
        .capacity-table thead tr:first-child th { top: 0; }
        .capacity-table thead tr:nth-child(2) th { top: 2.1rem; }
        .capacity-table .grp-head { letter-spacing: .08em; }
        .capacity-table .grp-ron { background: rgba(59,130,246,.10); }
        .capacity-table .grp-nsrdi { background: rgba(168,85,247,.10); }
        .capacity-table td.kh { font-weight: 800; background: var(--cbm-nav-hover); }
        .capacity-table td.sta { font-weight: 800; }
        .capacity-table td.zero { color: var(--cbm-text-sub); }
        .capacity-table tr.kh-first td { border-top: 2px solid var(--cbm-divider); }
        .capacity-table tfoot td { font-weight: 800; background: var(--cbm-nav-hover); }
        .capacity-table tfoot tr.grand td { font-size: 1rem; }
        .cap-date-nav { display: inline-flex; align-items: flex-end; gap: .375rem; }
        .cap-date-nav .mod-btn-outline { padding: .5rem .75rem; }
    </style>

    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Operasional</div>
            <div class="mod-title">Capacity Management</div>
            <div class="mod-subtitle">Kapasitas teknisi kabin, A/C RON, dan pencapaian target NSRDI per station &middot; {{ $day->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</div>
        </div>
    </div>

    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="cap-date-nav">
                    <button type="button" class="mod-btn-outline" wire:click="shiftDay(-1)" aria-label="Hari sebelumnya">&lsaquo;</button>
                    <label class="mod-field mod-field-labelled"><span>Tanggal</span>
                        <input wire:model.live="activeDate" type="date" class="mod-search-input mod-input-plain">
                    </label>
                    <button type="button" class="mod-btn-outline" wire:click="shiftDay(1)" aria-label="Hari berikutnya">&rsaquo;</button>
                    <button type="button" class="mod-btn-outline" wire:click="goToActiveDate">Hari aktif</button>
                </div>
                <span wire:loading wire:target="activeDate, shiftDay, goToActiveDate" class="cbm-spinner" aria-label="Memuat"></span>
            </div>
            <div class="mod-meta">
                @if($hasReport)
                    <span class="mod-badge-closed" title="Laporan tanggal ini sudah diarsipkan"><span class="mod-badge-dot"></span>Data arsip (tersimpan)</span>
                @else
                    <span class="mod-badge-progress" title="Belum ada laporan tersimpan, memakai template station dari Master"><span class="mod-badge-dot"></span>Data live (template master)</span>
                @endif
                <span class="mod-record-count">{{ $stationCount }} station</span>
            </div>
        </div>

        {{-- Target NSRDI --}}
        <div class="cap-targets">
            @foreach(['JT', 'IU', 'ID'] as $aoc)
                @php
                    $current = $nsrdiTotals[$aoc] ?? 0;
                    $target = (int) ($targets[$aoc] ?? 0);
                    $reached = $target > 0 ? $current >= $target : false;
                    $pct = $target > 0 ? min(100, (int) round($current / $target * 100)) : 0;
                @endphp
                <div class="cap-target {{ $reached ? 'ok' : 'warn' }}">
                    <div class="cap-target-head">
                        <span class="cap-target-name">{{ $aoc }} &middot; target NSRDI closed</span>
                        <span class="cap-target-value">{{ $current }} <small>/ {{ $target }}</small></span>
                    </div>
                    <div class="cap-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $pct }}%;"></span></div>
                    <div class="cap-target-note">
                        @if($target <= 0)
                            Target belum diatur
                        @elseif($reached)
                            Target terpenuhi{{ $current > $target ? ' (+'.($current - $target).')' : '' }}
                        @else
                            Kurang {{ $target - $current }} lagi untuk mencapai target
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mod-table-wrap" style="margin-top:1rem;">
            @if($stationCount === 0)
                <div class="mod-empty">
                    <div class="mod-empty-title">Belum ada station</div>
                    <div class="mod-empty-sub">
                        Daftar station capacity belum diatur.
                        @if(auth()->user()?->hasRole(\App\Helpers\RoleHelper::SUPER_ADMIN))
                            <a href="{{ route('master.capacity-config') }}" wire:navigate style="color:var(--cbm-blue);font-weight:700;">Atur di Capacity Config</a>
                        @else
                            Hubungi Super Admin untuk mengaturnya.
                        @endif
                    </div>
                </div>
            @else
            <table class="mod-table capacity-table">
                <thead>
                    <tr>
                        <th rowspan="2" colspan="3">NO</th>
                        <th rowspan="2" colspan="2">STA</th>
                        <th colspan="3" class="grp-head">CABIN TECHNICIAN</th>
                        <th colspan="6" class="grp-head grp-ron">A/C RON</th>
                        <th colspan="5" class="grp-head grp-nsrdi">NSRDI CLOSED</th>
                    </tr>
                    <tr>
                        <th>TIME</th><th>DAY</th><th>NIGHT</th>
                        @foreach(['JT','IW','ID','IU','SL','OD'] as $k)<th class="grp-ron">{{ $k }}</th>@endforeach
                        @foreach(['JT','IW','ID','IU'] as $k)<th class="grp-nsrdi">{{ $k }}</th>@endforeach
                        <th class="grp-nsrdi">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($capacityData as $kh => $rows)
                        @foreach($rows as $index => $row)
                            <tr class="{{ $index === 0 ? 'kh-first' : '' }}" wire:key="cap-{{ $kh }}-{{ $row['sta'] }}-{{ $index }}">
                                @if($index === 0)
                                    <td rowspan="{{ count($rows) }}" class="kh">{{ $kh }}</td>
                                @endif
                                <td>{{ $row['no'] }}</td>
                                <td>{{ $row['group'] }}</td>
                                <td class="sta">{{ $row['sta'] }}</td>
                                <td class="sta">{{ $row['code_store'] }}</td>
                                <td>{{ $row['time'] }}</td>
                                <td class="{{ $row['day'] ? '' : 'zero' }}">{{ $row['day'] ?: '-' }}</td>
                                <td class="{{ $row['night'] ? '' : 'zero' }}">{{ $row['night'] ?: '-' }}</td>
                                @foreach(['JT','IW','ID','IU','SL','OD'] as $k)
                                    <td class="{{ $row['ron'][$k] ? '' : 'zero' }}">{{ $row['ron'][$k] ?: '-' }}</td>
                                @endforeach
                                @foreach(['JT','IW','ID','IU'] as $k)
                                    <td class="{{ $row['nsrdi'][$k] ? '' : 'zero' }}">{{ $row['nsrdi'][$k] ?: '-' }}</td>
                                @endforeach
                                <td style="font-weight:800;" class="{{ $row['nsrdi']['TOTAL'] ? '' : 'zero' }}">{{ $row['nsrdi']['TOTAL'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" style="text-align:right;">TOTAL</td>
                        <td>{{ $totals['day'] }}</td>
                        <td>{{ $totals['night'] }}</td>
                        @foreach(['JT','IW','ID','IU','SL','OD'] as $k)<td>{{ $totals['ron'][$k] }}</td>@endforeach
                        @foreach(['JT','IW','ID','IU','TOTAL'] as $k)<td>{{ $totals['nsrdi'][$k] }}</td>@endforeach
                    </tr>
                    <tr class="grand">
                        <td colspan="6" style="text-align:right;">GRAND TOTAL</td>
                        <td colspan="2">{{ $totals['day'] + $totals['night'] }}</td>
                        <td colspan="6">{{ array_sum($totals['ron']) }}</td>
                        <td colspan="5">{{ $totals['nsrdi']['TOTAL'] }}</td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>

        @if($unlistedTotal > 0)
            <div class="mod-hint mod-hint-warn" style="margin:1rem 1.25rem;">
                {{ $unlistedTotal }} NSRDI closed berasal dari station yang tidak ada di daftar capacity
                ({{ implode(', ', array_map(fn ($s, $v) => $s.': '.$v['TOTAL'], array_keys($unlisted), $unlisted)) }}).
                Angka ini masuk ke kartu target di atas, tetapi tidak ke tabel.
            </div>
        @endif
    </div>
</div>
