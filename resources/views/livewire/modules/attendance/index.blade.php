<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />
    <style>
        .kd-flag { display:inline-block; margin:.1rem .2rem .1rem 0; padding:.12rem .5rem; border-radius:999px; font-size:.7rem; font-weight:700; white-space:nowrap; }
        .kd-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.25rem; margin-bottom:1rem; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .kd-tabs::-webkit-scrollbar { display:none; }
        .kd-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.4rem .85rem; font-size:.82rem; font-weight:600; cursor:pointer; transition:background .15s,color .15s; }
        .kd-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
        .kd-table tbody tr.kd-click { cursor:pointer; }
        .kd-days { display:grid; grid-template-columns:repeat(auto-fill,minmax(5.4rem,1fr)); gap:.4rem; padding:1rem; }
        .kd-day { border-radius:.65rem; padding:.4rem .5rem; font-size:.75rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); }
        .kd-day b { display:block; font-size:.8rem; }
        .kd-upload { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; padding:.85rem 1rem; }
    </style>

    @php
        $tones = ['green' => '#34d399', 'yellow' => '#f59e0b', 'red' => '#ef4444', 'none' => '#94a3b8', 'blue' => '#60a5fa'];
        $bgs = ['green' => 'rgba(52,211,153,.15)', 'yellow' => 'rgba(245,158,11,.17)', 'red' => 'rgba(239,68,68,.16)', 'none' => 'rgba(148,163,184,.13)', 'blue' => 'rgba(96,165,250,.16)'];
        $flagTone = ['rajin' => 'green', 'sering_terlambat' => 'yellow', 'banyak_sakit' => 'red', 'banyak_cuti' => 'yellow', 'alpa' => 'red'];
        $st = fn ($v) => \App\Services\Kpi\AchievementStatus::for($v, 100);
        $chip = fn ($v) => '<span class="kd-chip" style="color:'.$tones[$st($v)].';background:'.$bgs[$st($v)].';">'.($v !== null ? rtrim(rtrim(number_format($v, 1), '0'), '.').'%' : '–').'</span>';
        $arrow = fn ($k) => $sort === $k ? '<span class="arrow">'.($dir === 'asc' ? '▲' : '▼').'</span>' : '';
        $periodLabel = $kind === 'year' ? $from->format('Y') : ($kind === 'quarter' ? 'Q'.$from->quarter.' '.$from->format('Y') : $from->translatedFormat('F Y'));
        $dayTone = ['hadir' => 'green', 'sakit' => 'red', 'cuti' => 'yellow', 'izin' => 'blue', 'alpa' => 'red', 'libur' => 'none', 'training' => 'blue'];
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="Presensi & Disiplin" subtitle="Siapa yang rajin, tepat waktu, sering terlambat, terlalu banyak sakit atau cuti. Ambang batas diatur di Master Sistem › Aturan Disiplin." accent="blue" eyebrow="Development & GA" />

    <x-flash />

    <div class="kd-bar">
        <div class="kd-group">
            <div class="kd-seg" role="tablist" aria-label="Periode">
                @foreach(['month' => 'Bulan', 'quarter' => 'Kuartal', 'year' => 'Tahun'] as $k => $l)
                    <button type="button" role="tab" aria-selected="{{ $kind === $k ? 'true' : 'false' }}" wire:click="setKind('{{ $k }}')" class="{{ $kind === $k ? 'on' : '' }}">{{ $l }}</button>
                @endforeach
            </div>
            <div class="kd-nav">
                <button type="button" wire:click="move(-1)" aria-label="Periode sebelumnya">‹</button>
                <span class="kd-label">{{ $periodLabel }}</span>
                <button type="button" wire:click="move(1)" aria-label="Periode berikutnya">›</button>
            </div>
        </div>
        <div class="kd-group">
            @if($seesAll)
                <select wire:model.live="station" aria-label="Station">
                    <option value="">Semua station</option>
                    @foreach($stationOptions as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </select>
            @endif
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari nama / ID" aria-label="Cari karyawan" style="min-width:9rem;">
        </div>
    </div>

    @if($canImport)
        <div class="kd-panel">
            <form wire:submit="importFile" class="kd-upload">
                <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" aria-label="File presensi">
                <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="importFile,file" @disabled(! $file)>
                    <span wire:loading.remove wire:target="importFile">Impor presensi</span>
                    <span wire:loading wire:target="importFile"><span class="cbm-spinner"></span> Membaca...</span>
                </button>
                <span class="kd-sub">Daftar (ID, tanggal, status, jam masuk/pulang) atau matriks (baris karyawan, kolom tanggal). Keterlambatan dihitung dari shift di roster.</span>
                @error('file') <span class="mod-field-error" style="width:100%;">{{ $message }}</span> @enderror
            </form>
        </div>
    @endif

    <div class="kd-cards">
        @php $allAssumed = $summary['people'] > 0 && $summary['assumed'] === $summary['people']; @endphp
        <div class="kd-card" style="--tone: {{ $allAssumed ? $tones['none'] : $tones[$st($summary['on_time'])] }}">
            <div class="t">Hadir tepat waktu</div>
            <div class="v">{{ $summary['on_time'] !== null && ! $allAssumed ? $summary['on_time'].'%' : '–' }}</div>
            <div class="s">{{ $allAssumed ? 'belum ada data presensi' : 'rata-rata '.$summary['people'].' karyawan' }}</div>
        </div>
        <div class="kd-card" style="--tone: {{ $tones['green'] }}"><div class="t">Rajin</div><div class="v">{{ $summary['flags']['rajin'] }}</div><div class="s">hadir tepat waktu & tanpa catatan</div></div>
        <div class="kd-card" style="--tone: {{ $tones['yellow'] }}"><div class="t">Sering terlambat</div><div class="v">{{ $summary['flags']['sering_terlambat'] }}</div><div class="s">{{ $summary['late'] }} kali terlambat</div></div>
        <div class="kd-card" style="--tone: {{ $tones['red'] }}"><div class="t">Terlalu banyak sakit</div><div class="v">{{ $summary['flags']['banyak_sakit'] }}</div><div class="s">{{ $summary['sick'] }} hari sakit</div></div>
        <div class="kd-card" style="--tone: {{ $tones['yellow'] }}"><div class="t">Terlalu banyak cuti</div><div class="v">{{ $summary['flags']['banyak_cuti'] }}</div><div class="s">{{ $summary['leave'] }} hari cuti</div></div>
    </div>

    @if($summary['assumed'] > 0)
        <div class="kd-sub" style="margin:-.25rem 0 1rem;color:#f59e0b;">{{ $summary['assumed'] }} karyawan belum punya data presensi pada periode ini: dianggap hadir sesuai roster, hanya sakit dan cuti dari roster yang terhitung. Impor presensi agar keterlambatan terbaca.</div>
    @endif

    @if($byStation->count() > 1)
        <section class="kd-panel" aria-label="Pivot per station">
            <h2><span>Per station</span><span class="kd-sub">semua karyawan dalam cakupan Anda</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th style="cursor:default;">Station</th><th style="cursor:default;">Karyawan</th><th style="cursor:default;">Tepat waktu</th><th style="cursor:default;">Rajin</th><th style="cursor:default;">Perlu perhatian</th><th style="cursor:default;">Terlambat</th><th style="cursor:default;">Sakit</th><th style="cursor:default;">Cuti</th><th style="cursor:default;">Alpa</th></tr></thead>
                    <tbody>
                        @foreach($byStation as $b)
                            <tr wire:key="bs-{{ $b['station'] }}">
                                <td><strong>{{ $b['station'] }}</strong></td><td>{{ $b['people'] }}</td><td>{!! $chip($b['on_time']) !!}</td><td>{{ $b['diligent'] }}</td>
                                <td style="{{ $b['flagged'] ? 'color:#f59e0b;font-weight:700;' : '' }}">{{ $b['flagged'] }}</td><td>{{ $b['late'] }}</td><td>{{ $b['sick'] }}</td><td>{{ $b['leave'] }}</td><td>{{ $b['absent'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="kd-tabs" role="tablist" aria-label="Filter">
        <button type="button" wire:click="$set('flag','')" class="kd-tab {{ $flag === '' ? 'on' : '' }}">Semua ({{ $summary['people'] }})</button>
        @foreach($flags as $key => $label)
            <button type="button" wire:click="$set('flag','{{ $key }}')" class="kd-tab {{ $flag === $key ? 'on' : '' }}">{{ $label }} ({{ $summary['flags'][$key] }})</button>
        @endforeach
    </div>

    <section class="kd-panel" aria-label="Daftar karyawan">
        <h2><span>{{ $rows->count() }} karyawan</span><span class="kd-sub">klik baris untuk profil</span></h2>
        <div class="kd-wrap">
            @if($rows->isEmpty())
                <div class="kd-empty">Belum ada data roster atau presensi pada periode ini.<br><span class="kd-sub">Impor roster (php artisan roster:import) dan presensi.</span></div>
            @else
                <table class="kd-table">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('name')">Karyawan {!! $arrow('name') !!}</th>
                            <th wire:click="sortBy('station')">Sta {!! $arrow('station') !!}</th>
                            <th wire:click="sortBy('working')">Hari kerja {!! $arrow('working') !!}</th>
                            <th wire:click="sortBy('on_time')">Tepat waktu {!! $arrow('on_time') !!}</th>
                            <th wire:click="sortBy('late')">Terlambat {!! $arrow('late') !!}</th>
                            <th wire:click="sortBy('sick')">Sakit {!! $arrow('sick') !!}</th>
                            <th wire:click="sortBy('leave')">Cuti {!! $arrow('leave') !!}</th>
                            <th wire:click="sortBy('absent')">Alpa {!! $arrow('absent') !!}</th>
                            <th wire:click="sortBy('man_hours')" title="Man hours pada pekerjaan yang tercatat (kru laporan)">Man hours {!! $arrow('man_hours') !!}</th>
                            <th style="text-align:left;cursor:default;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows->take(300) as $r)
                            <tr wire:key="at-{{ $r['nik'] }}" wire:click="pick('{{ $r['nik'] }}')" class="kd-click" style="{{ $selected === $r['nik'] ? 'background:rgba(59,130,246,.08);' : '' }}">
                                <td><strong>{{ $r['name'] }}</strong><div class="kd-sub">{{ $r['nik'] }} · {{ $r['team'] }}</div></td>
                                <td>{{ $r['station'] }}</td>
                                <td>{{ $r['working'] }}</td>
                                <td>{!! $chip($r['on_time']) !!}</td>
                                <td>{{ $r['late'] }}@if($r['late_minutes']) <span class="kd-sub">({{ $r['late_minutes'] }} mnt)</span>@endif</td>
                                <td>{{ $r['sick'] }}</td>
                                <td>{{ $r['leave'] }}</td>
                                <td style="{{ $r['absent'] ? 'color:#ef4444;font-weight:700;' : '' }}">{{ $r['absent'] }}</td>
                                <td>{{ $r['man_hours'] ? number_format($r['man_hours'], 1) : '–' }}</td>
                                <td style="text-align:left;white-space:normal;min-width:11rem;">
                                    @foreach($r['flags'] as $f)
                                        <span class="kd-flag" style="color:{{ $tones[$flagTone[$f]] }};background:{{ $bgs[$flagTone[$f]] }};">{{ $flags[$f] }}</span>
                                    @endforeach
                                    @if($r['assumed'])<span class="kd-sub">dari roster</span>@endif
                                    @if($r['unrecorded'])<span class="kd-sub">{{ $r['unrecorded'] }} hari tak tercatat</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($rows->count() > 300)<div class="kd-sub" style="padding:.6rem 1rem;">Menampilkan 300 teratas dari {{ $rows->count() }}. Persempit dengan pencarian atau filter.</div>@endif
            @endif
        </div>
    </section>

    @if($profile)
        <section class="kd-panel" aria-label="Profil karyawan">
            <h2>
                <span>{{ $profile['name'] }} <span class="kd-sub">· {{ $profile['nik'] }}{{ $profile['job_title'] ? ' · '.$profile['job_title'] : '' }}</span></span>
                <button type="button" wire:click="pick(null)" class="mod-action-btn">Tutup</button>
            </h2>
            <div class="kd-cards" style="padding:1rem 1rem 0;">
                <div class="kd-card" style="--tone: {{ $tones['blue'] }}"><div class="t">Man hours dikerjakan</div><div class="v">{{ number_format($profile['hours'], 1) }}</div><div class="s">{{ $profile['jobs'] }} pekerjaan (dari kru laporan)</div></div>
                <div class="kd-card" style="--tone: {{ $tones['none'] }}"><div class="t">Asset dipegang</div><div class="v">{{ $profile['assets']->sum('qty') }}</div><div class="s">{{ $profile['assets']->count() }} jenis</div></div>
            </div>

            @if($profile['assets']->isNotEmpty())
                <div style="padding:0 1rem;">
                    @foreach($profile['assets'] as $a)
                        <span class="kd-flag" style="color:{{ $tones[$a->isOverdue() ? 'red' : 'blue'] }};background:{{ $bgs[$a->isOverdue() ? 'red' : 'blue'] }};">{{ $a->asset?->name }} ×{{ $a->qty }}{{ $a->isOverdue() ? ' · lewat jatuh tempo' : '' }}</span>
                    @endforeach
                </div>
            @endif

            @if($profile['records']->isNotEmpty())
                <div class="kd-days">
                    @foreach($profile['records'] as $rec)
                        @php $tone = $dayTone[$rec->status_group] ?? 'none'; @endphp
                        <div class="kd-day" style="border-color:{{ $tones[$tone] }}55;">
                            <b>{{ $rec->work_date->format('d M') }}</b>
                            <span style="color:{{ $tones[$tone] }};font-weight:700;">{{ $rec->status_code }}</span>
                            @if($rec->clock_in)<span class="kd-sub"> {{ $rec->clock_in }}{{ $rec->clock_out ? '–'.$rec->clock_out : '' }}</span>@endif
                            @if($rec->late_minutes)<div style="color:#f59e0b;">+{{ $rec->late_minutes }} mnt</div>@endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="kd-empty">Belum ada data presensi untuk orang ini pada periode ini.</div>
            @endif
        </section>
    @endif
</div>
