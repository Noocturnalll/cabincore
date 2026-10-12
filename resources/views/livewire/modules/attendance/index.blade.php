<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />
    <style>
        .kd-flag { display:inline-block; margin:.1rem .2rem .1rem 0; padding:.12rem .5rem; border-radius:999px; font-size:.7rem; font-weight:700; white-space:nowrap; }
        .kd-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.25rem; margin-bottom:1rem; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .kd-tabs::-webkit-scrollbar { display:none; }
        .kd-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.4rem .85rem; font-size:.82rem; font-weight:600; cursor:pointer; transition:background .15s,color .15s; }
        .kd-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
        .kd-main-tabs { display:flex; gap:.5rem; overflow-x:auto; padding-bottom:.4rem; margin-bottom:1.1rem; border-bottom:1px solid var(--cbm-divider); -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .kd-main-tabs::-webkit-scrollbar { display:none; }
        .kd-main-tab { flex:0 0 auto; border:0; background:transparent; color:var(--cbm-text-muted); padding:.5rem .95rem; border-radius:.75rem; font-size:.88rem; font-weight:700; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:.45rem; }
        .kd-main-tab:hover { background:var(--cbm-nav-hover); color:var(--cbm-text); }
        .kd-main-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); }
        .kd-badge { display:inline-block; font-size:.72rem; padding:.1rem .45rem; border-radius:999px; font-weight:800; background:rgba(0,0,0,.15); }
        .kd-main-tab.on .kd-badge { background:rgba(255,255,255,.2); color:inherit; }
        .kd-table tbody tr.kd-click { cursor:pointer; }
        .kd-days { display:grid; grid-template-columns:repeat(auto-fill,minmax(5.4rem,1fr)); gap:.4rem; padding:1rem; }
        .kd-day { border-radius:.65rem; padding:.4rem .5rem; font-size:.75rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); }
        .kd-day b { display:block; font-size:.8rem; }
        .kd-upload { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; padding:.85rem 1rem; }
        .kd-date-tag { display:inline-block; font-size:.72rem; padding:.12rem .4rem; border-radius:.4rem; background:var(--cbm-input-bg); border:1px solid var(--cbm-input-border); color:var(--cbm-text); margin:.1rem .15rem .1rem 0; }
        .kd-act-btn { display:inline-flex; align-items:center; gap:.35rem; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text); padding:.45rem .85rem; border-radius:.65rem; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .15s; text-decoration:none; }
        .kd-act-btn:hover { background:var(--cbm-nav-hover); border-color:var(--cbm-blue); color:var(--cbm-text); }
        .kd-act-btn-primary { background:var(--cbm-blue); color:#fff; border-color:var(--cbm-blue); }
        .kd-act-btn-primary:hover { opacity:.92; }
        .kd-cell-alert { color:#ef4444; font-weight:700; }
    </style>

    @php
        $tones = ['green' => '#34d399', 'yellow' => '#f59e0b', 'red' => '#ef4444', 'none' => '#94a3b8', 'blue' => '#60a5fa'];
        $bgs = ['green' => 'rgba(52,211,153,.15)', 'yellow' => 'rgba(245,158,11,.17)', 'red' => 'rgba(239,68,68,.16)', 'none' => 'rgba(148,163,184,.13)', 'blue' => 'rgba(96,165,250,.16)'];
        $flagTone = ['rajin' => 'green', 'sering_terlambat' => 'yellow', 'banyak_sakit' => 'red', 'banyak_cuti' => 'yellow', 'alpa' => 'red'];
        $st = fn ($v) => \App\Services\Kpi\AchievementStatus::for($v, 100);
        $chip = fn ($v) => '<span class="kd-chip" style="color:'.$tones[$st($v)].';background:'.$bgs[$st($v)].';">'.($v !== null ? rtrim(rtrim(number_format($v, 1), '0'), '.').'%' : '–').'</span>';
        $arrow = fn ($k) => $sort === $k ? '<span class="arrow">'.($dir === 'asc' ? '▲' : '▼').'</span>' : '';
        $periodLabel = match ($kind) {
            'week' => 'Minggu '.$from->isoWeek().' ('.$from->format('d M').' - '.$to->format('d M Y').')',
            'year' => $from->format('Y'),
            'quarter' => 'Q'.$from->quarter.' '.$from->format('Y'),
            default => $from->translatedFormat('F Y'),
        };
        $dayTone = ['hadir' => 'green', 'sakit' => 'red', 'cuti' => 'yellow', 'izin' => 'blue', 'alpa' => 'red', 'libur' => 'none', 'training' => 'blue'];
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="Presensi & Disiplin" subtitle="Rekap kehadiran staf, kalkulasi mingguan & bulanan, pemantauan sakit, cuti, alpa, dan daftar staf resign." accent="blue" eyebrow="Development & GA" />

    <x-flash />

    <div class="kd-bar">
        <div class="kd-group">
            <div class="kd-seg" role="tablist" aria-label="Periode">
                @foreach(['week' => 'Minggu', 'month' => 'Bulan', 'quarter' => 'Kuartal', 'year' => 'Tahun'] as $k => $l)
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
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari nama / ID..." aria-label="Cari karyawan" style="min-width:9rem;">

            <button type="button" wire:click="exportExcel" class="kd-act-btn" title="Unduh rekapitulasi lengkap dengan sheet Pivot Excel" style="background: #107c41; color: #fff; border-color: #107c41;">
                <span>📊</span> Ekspor Excel (Pivot)
            </button>
            <button type="button" wire:click="exportCsv" class="kd-act-btn" title="Unduh data dalam format CSV">
                <span>📥</span> Ekspor CSV
            </button>

            @if($canManage)
                <button type="button" wire:click="openRecordModal" class="kd-act-btn" title="Catat sakit, cuti, izin, atau alpa manual">
                    <span>+</span> Catat Presensi
                </button>
                <button type="button" wire:click="openResignModal" class="kd-act-btn" title="Catat staf yang resign">
                    <span>🚪</span> Catat Resign
                </button>
            @endif
        </div>
    </div>

    {{-- Main Subnavigation Tabs --}}
    <nav class="kd-main-tabs" role="tablist" aria-label="Navigasi Presensi & Disiplin">
        <button type="button" role="tab" aria-selected="{{ $tab === 'overview' ? 'true' : 'false' }}" wire:click="setTab('overview')" class="kd-main-tab {{ $tab === 'overview' ? 'on' : '' }}">
            <span>📊</span> Rekap Presensi <span class="kd-badge">{{ $summary['people'] }}</span>
        </button>
        <button type="button" role="tab" aria-selected="{{ $tab === 'sakit' ? 'true' : 'false' }}" wire:click="setTab('sakit')" class="kd-main-tab {{ $tab === 'sakit' ? 'on' : '' }}">
            <span>🏥</span> Staff Sakit <span class="kd-badge">{{ count($sakitList) }}</span>
        </button>
        <button type="button" role="tab" aria-selected="{{ $tab === 'cuti' ? 'true' : 'false' }}" wire:click="setTab('cuti')" class="kd-main-tab {{ $tab === 'cuti' ? 'on' : '' }}">
            <span>🏖️</span> Staff Cuti <span class="kd-badge">{{ count($cutiList) }}</span>
        </button>
        <button type="button" role="tab" aria-selected="{{ $tab === 'alpa' ? 'true' : 'false' }}" wire:click="setTab('alpa')" class="kd-main-tab {{ $tab === 'alpa' ? 'on' : '' }}">
            <span>⚠️</span> Staff Alpa <span class="kd-badge">{{ count($alpaList) }}</span>
        </button>
        <button type="button" role="tab" aria-selected="{{ $tab === 'resign' ? 'true' : 'false' }}" wire:click="setTab('resign')" class="kd-main-tab {{ $tab === 'resign' ? 'on' : '' }}">
            <span>🚪</span> Staff Resign <span class="kd-badge">{{ count($resignList) }}</span>
        </button>
    </nav>

    {{-- TAB 1: REKAP PRESENSI & DISIPLIN --}}
    @if($tab === 'overview')
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
            <div class="kd-card" style="--tone: {{ $tones['green'] }}">
                <div class="t">Rajin</div>
                <div class="v">{{ $summary['flags']['rajin'] }}</div>
                <div class="s">hadir tepat waktu & tanpa catatan</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['yellow'] }}">
                <div class="t">Sering terlambat</div>
                <div class="v">{{ $summary['flags']['sering_terlambat'] }}</div>
                <div class="s">{{ $summary['late'] }} kali terlambat</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Terlalu banyak sakit</div>
                <div class="v">{{ $summary['flags']['banyak_sakit'] }}</div>
                <div class="s">{{ $summary['sick'] }} hari ({{ $summary['sick_people'] }} staff)</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['yellow'] }}">
                <div class="t">Terlalu banyak cuti</div>
                <div class="v">{{ $summary['flags']['banyak_cuti'] }}</div>
                <div class="s">{{ $summary['leave'] }} hari ({{ $summary['leave_people'] }} staff)</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Alpa / Mangkir</div>
                <div class="v">{{ $summary['absent'] }}</div>
                <div class="s">{{ $summary['absent_people'] }} staff tercatat alpa</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['none'] }}">
                <div class="t">Resign di periode ini</div>
                <div class="v">{{ $summary['period_resign'] }}</div>
                <div class="s">total {{ $summary['total_resigned'] }} staff terdata resign</div>
            </div>
        </div>

        @if($summary['assumed'] > 0)
            <div class="kd-sub" style="margin:-.25rem 0 1rem;color:#f59e0b;">
                {{ $summary['assumed'] }} karyawan belum punya data presensi pada periode ini: dianggap hadir sesuai roster, hanya sakit dan cuti dari roster yang terhitung. Impor presensi agar keterlambatan terbaca.
            </div>
        @endif

        {{-- Kalkulasi Mingguan / Harian Breakdown --}}
        @if(!empty($weeklyBreakdown))
            <section class="kd-panel" aria-label="Kalkulasi & Tren Mingguan">
                <h2>
                    <span>Kalkulasi & Tren {{ $kind === 'week' ? 'Harian (Day-by-Day)' : ($kind === 'month' ? 'Mingguan (Weekly Breakdown)' : 'Bulanan') }}</span>
                    <span class="kd-sub">Perincian kehadiran, ketepatan waktu, dan man hours</span>
                </h2>
                <div class="kd-wrap">
                    <table class="kd-table">
                        <thead>
                            <tr>
                                <th style="cursor:default;">Periode</th>
                                <th style="cursor:default;">Hari Kerja</th>
                                <th style="cursor:default;">Tingkat Hadir</th>
                                <th style="cursor:default;">Tepat Waktu</th>
                                <th style="cursor:default;">Terlambat</th>
                                <th style="cursor:default;">Sakit</th>
                                <th style="cursor:default;">Cuti</th>
                                <th style="cursor:default;">Alpa</th>
                                <th style="cursor:default;">Man Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($weeklyBreakdown as $wb)
                                <tr wire:key="wb-{{ $wb['key'] }}">
                                    <td><strong>{{ $wb['label'] }}</strong></td>
                                    <td>{{ $wb['working'] }}</td>
                                    <td>{!! $chip($wb['attendance_rate']) !!}</td>
                                    <td>{!! $chip($wb['on_time_rate']) !!}</td>
                                    <td>{{ $wb['late'] }}</td>
                                    <td>{{ $wb['sick'] }}</td>
                                    <td>{{ $wb['leave'] }}</td>
                                    <td style="{{ $wb['absent'] ? 'color:#ef4444;font-weight:700;' : '' }}">{{ $wb['absent'] }}</td>
                                    <td>{{ $wb['man_hours'] ? number_format($wb['man_hours'], 1) : '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
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

    {{-- TAB 2: STAFF SAKIT --}}
    @elseif($tab === 'sakit')
        <div class="kd-cards">
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Total Hari Sakit</div>
                <div class="v">{{ $summary['sick'] }}</div>
                <div class="s">hari sakit tercatat di periode ini</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['yellow'] }}">
                <div class="t">Staff Mengalami Sakit</div>
                <div class="v">{{ count($sakitList) }}</div>
                <div class="s">karyawan yang tercatat izin sakit</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Melebihi Batas Disiplin</div>
                <div class="v">{{ $sakitList->where('exceeded_limit', true)->count() }}</div>
                <div class="s">batas maksimal {{ $limits['sick'] }} hari</div>
            </div>
        </div>

        <section class="kd-panel" aria-label="Daftar Staff Sakit">
            <h2>
                <span>Daftar Staff Sakit ({{ count($sakitList) }} orang)</span>
                @if($canManage)
                    <button type="button" wire:click="openRecordModal(null, 'sakit')" class="kd-act-btn">
                        <span>+</span> Catat Surat Sakit
                    </button>
                @endif
            </h2>
            <div class="kd-wrap">
                @if($sakitList->isEmpty())
                    <div class="kd-empty">Tidak ada data staf sakit pada periode ini.</div>
                @else
                    <table class="kd-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Karyawan</th>
                                <th style="text-align:left;">Station & Tim</th>
                                <th>Total Hari</th>
                                <th style="text-align:left;">Tanggal Sakit</th>
                                <th style="text-align:left;">Keterangan / Notes</th>
                                <th style="text-align:center;">Status Batas</th>
                                @if($canManage)<th style="text-align:right;">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sakitList as $s)
                                <tr wire:key="sakit-{{ $s['nik'] }}">
                                    <td style="text-align:left;">
                                        <strong>{{ $s['name'] }}</strong>
                                        <div class="kd-sub">{{ $s['nik'] }}</div>
                                    </td>
                                    <td style="text-align:left;">{{ $s['station'] }} · {{ $s['team'] }}</td>
                                    <td style="font-weight:700;color:{{ $s['exceeded_limit'] ? '#ef4444' : 'inherit' }};">
                                        {{ $s['count'] }} hari
                                    </td>
                                    <td style="text-align:left;max-width:20rem;white-space:normal;">
                                        @foreach($s['dates'] as $dt)
                                            <span class="kd-date-tag">{{ \Carbon\Carbon::parse($dt)->format('d M') }}</span>
                                        @endforeach
                                    </td>
                                    <td style="text-align:left;white-space:normal;max-width:16rem;">
                                        @if(!empty($s['notes']))
                                            {{ implode(', ', $s['notes']) }}
                                        @else
                                            <span class="kd-sub">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($s['exceeded_limit'])
                                            <span class="kd-flag" style="color:{{ $tones['red'] }};background:{{ $bgs['red'] }};">
                                                Melebihi Batas (>{{ $limits['sick'] }} hari)
                                            </span>
                                        @else
                                            <span class="kd-flag" style="color:{{ $tones['green'] }};background:{{ $bgs['green'] }};">
                                                Wajar (≤{{ $limits['sick'] }} hari)
                                            </span>
                                        @endif
                                    </td>
                                    @if($canManage)
                                        <td style="text-align:right;">
                                            <button type="button" wire:click="openRecordModal('{{ $s['nik'] }}', 'sakit')" class="kd-act-btn" style="padding:.25rem .55rem;font-size:.75rem;">
                                                + Tambah
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

    {{-- TAB 3: STAFF CUTI --}}
    @elseif($tab === 'cuti')
        <div class="kd-cards">
            <div class="kd-card" style="--tone: {{ $tones['yellow'] }}">
                <div class="t">Total Hari Cuti</div>
                <div class="v">{{ $summary['leave'] }}</div>
                <div class="s">hari cuti tercatat di periode ini</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['blue'] }}">
                <div class="t">Staff Mengambil Cuti</div>
                <div class="v">{{ count($cutiList) }}</div>
                <div class="s">karyawan yang mengambil hak cuti</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['yellow'] }}">
                <div class="t">Melebihi Batas Disiplin</div>
                <div class="v">{{ $cutiList->where('exceeded_limit', true)->count() }}</div>
                <div class="s">batas maksimal {{ $limits['leave'] }} hari</div>
            </div>
        </div>

        <section class="kd-panel" aria-label="Daftar Staff Cuti">
            <h2>
                <span>Daftar Staff Cuti ({{ count($cutiList) }} orang)</span>
                @if($canManage)
                    <button type="button" wire:click="openRecordModal(null, 'cuti')" class="kd-act-btn">
                        <span>+</span> Catat Cuti
                    </button>
                @endif
            </h2>
            <div class="kd-wrap">
                @if($cutiList->isEmpty())
                    <div class="kd-empty">Tidak ada data staf cuti pada periode ini.</div>
                @else
                    <table class="kd-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Karyawan</th>
                                <th style="text-align:left;">Station & Tim</th>
                                <th>Total Hari</th>
                                <th style="text-align:left;">Tanggal Cuti</th>
                                <th style="text-align:left;">Keterangan / Notes</th>
                                <th style="text-align:center;">Status Batas</th>
                                @if($canManage)<th style="text-align:right;">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cutiList as $c)
                                <tr wire:key="cuti-{{ $c['nik'] }}">
                                    <td style="text-align:left;">
                                        <strong>{{ $c['name'] }}</strong>
                                        <div class="kd-sub">{{ $c['nik'] }}</div>
                                    </td>
                                    <td style="text-align:left;">{{ $c['station'] }} · {{ $c['team'] }}</td>
                                    <td style="font-weight:700;color:{{ $c['exceeded_limit'] ? '#f59e0b' : 'inherit' }};">
                                        {{ $c['count'] }} hari
                                    </td>
                                    <td style="text-align:left;max-width:20rem;white-space:normal;">
                                        @foreach($c['dates'] as $dt)
                                            <span class="kd-date-tag">{{ \Carbon\Carbon::parse($dt)->format('d M') }}</span>
                                        @endforeach
                                    </td>
                                    <td style="text-align:left;white-space:normal;max-width:16rem;">
                                        @if(!empty($c['notes']))
                                            {{ implode(', ', $c['notes']) }}
                                        @else
                                            <span class="kd-sub">Cuti Tahunan</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($c['exceeded_limit'])
                                            <span class="kd-flag" style="color:{{ $tones['yellow'] }};background:{{ $bgs['yellow'] }};">
                                                Melebihi Batas (>{{ $limits['leave'] }} hari)
                                            </span>
                                        @else
                                            <span class="kd-flag" style="color:{{ $tones['green'] }};background:{{ $bgs['green'] }};">
                                                Dalam Kuota
                                            </span>
                                        @endif
                                    </td>
                                    @if($canManage)
                                        <td style="text-align:right;">
                                            <button type="button" wire:click="openRecordModal('{{ $c['nik'] }}', 'cuti')" class="kd-act-btn" style="padding:.25rem .55rem;font-size:.75rem;">
                                                + Tambah
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

    {{-- TAB 4: STAFF ALPA --}}
    @elseif($tab === 'alpa')
        <div class="kd-cards">
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Total Hari Alpa / Mangkir</div>
                <div class="v">{{ $summary['absent'] }}</div>
                <div class="s">hari alpa tercatat di periode ini</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Staff Tercatat Alpa</div>
                <div class="v">{{ count($alpaList) }}</div>
                <div class="s">karyawan tanpa keterangan hadir</div>
            </div>
        </div>

        <section class="kd-panel" aria-label="Daftar Staff Alpa">
            <h2>
                <span>Daftar Staff Alpa / Mangkir ({{ count($alpaList) }} orang)</span>
                @if($canManage)
                    <button type="button" wire:click="openRecordModal(null, 'alpa')" class="kd-act-btn">
                        <span>+</span> Catat Alpa
                    </button>
                @endif
            </h2>
            <div class="kd-wrap">
                @if($alpaList->isEmpty())
                    <div class="kd-empty">Bagus! Tidak ada staf yang tercatat alpa pada periode ini.</div>
                @else
                    <table class="kd-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Karyawan</th>
                                <th style="text-align:left;">Station & Tim</th>
                                <th>Total Hari Alpa</th>
                                <th style="text-align:left;">Tanggal Alpa</th>
                                <th style="text-align:left;">Keterangan / Notes</th>
                                <th style="text-align:center;">Status Disiplin</th>
                                @if($canManage)<th style="text-align:right;">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alpaList as $a)
                                <tr wire:key="alpa-{{ $a['nik'] }}">
                                    <td style="text-align:left;">
                                        <strong>{{ $a['name'] }}</strong>
                                        <div class="kd-sub">{{ $a['nik'] }}</div>
                                    </td>
                                    <td style="text-align:left;">{{ $a['station'] }} · {{ $a['team'] }}</td>
                                    <td style="font-weight:700;color:#ef4444;">
                                        {{ $a['count'] }} hari
                                    </td>
                                    <td style="text-align:left;max-width:20rem;white-space:normal;">
                                        @foreach($a['dates'] as $dt)
                                            <span class="kd-date-tag" style="border-color:#ef4444;color:#ef4444;">{{ \Carbon\Carbon::parse($dt)->format('d M') }}</span>
                                        @endforeach
                                    </td>
                                    <td style="text-align:left;white-space:normal;max-width:16rem;">
                                        @if(!empty($a['notes']))
                                            {{ implode(', ', $a['notes']) }}
                                        @else
                                            <span class="kd-sub">Tidak ada keterangan</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="kd-flag" style="color:{{ $tones['red'] }};background:{{ $bgs['red'] }};">
                                            ⚠️ Perlu Tindakan / SP
                                        </span>
                                    </td>
                                    @if($canManage)
                                        <td style="text-align:right;">
                                            <button type="button" wire:click="openRecordModal('{{ $a['nik'] }}', 'sakit')" class="kd-act-btn" style="padding:.25rem .55rem;font-size:.75rem;" title="Ubah status jika ada surat susulan">
                                                Klarifikasi
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

    {{-- TAB 5: STAFF RESIGN --}}
    @elseif($tab === 'resign')
        <div class="kd-cards">
            <div class="kd-card" style="--tone: {{ $tones['red'] }}">
                <div class="t">Resign di Periode Ini</div>
                <div class="v">{{ $summary['period_resign'] }}</div>
                <div class="s">staf efektif berhenti kerja pada rentang tanggal ini</div>
            </div>
            <div class="kd-card" style="--tone: {{ $tones['none'] }}">
                <div class="t">Total Terdata Resign</div>
                <div class="v">{{ count($resignList) }}</div>
                <div class="s">seluruh riwayat karyawan resign</div>
            </div>
        </div>

        <section class="kd-panel" aria-label="Daftar Staff Resign">
            <h2>
                <span>Daftar Staff Resign ({{ count($resignList) }} orang)</span>
                @if($canManage)
                    <button type="button" wire:click="openResignModal" class="kd-act-btn">
                        <span>🚪</span> Catat Staff Resign
                    </button>
                @endif
            </h2>
            <div class="kd-wrap">
                @if($resignList->isEmpty())
                    <div class="kd-empty">Belum ada catatan staf resign.</div>
                @else
                    <table class="kd-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Karyawan</th>
                                <th style="text-align:left;">Station</th>
                                <th style="text-align:left;">Jabatan & Divisi</th>
                                <th style="text-align:left;">Tanggal Masuk</th>
                                <th style="text-align:left;">Tanggal Resign</th>
                                <th style="text-align:left;">Masa Kerja</th>
                                <th style="text-align:left;">Alasan Resign</th>
                                <th style="text-align:left;">Catatan</th>
                                @if($canManage)<th style="text-align:right;">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resignList as $r)
                                <tr wire:key="resign-{{ $r['id'] }}" style="{{ $r['is_period_resign'] ? 'background:rgba(239,68,68,.06);' : '' }}">
                                    <td style="text-align:left;">
                                        <strong>{{ $r['name'] }}</strong>
                                        <div class="kd-sub">{{ $r['nik'] }}</div>
                                    </td>
                                    <td style="text-align:left;">{{ $r['station'] }}</td>
                                    <td style="text-align:left;">
                                        {{ $r['job_title'] }}
                                        <div class="kd-sub">{{ $r['department'] }}</div>
                                    </td>
                                    <td style="text-align:left;">{{ $r['join_date'] ?? '–' }}</td>
                                    <td style="text-align:left;">
                                        <strong>{{ $r['resign_date'] ?? '–' }}</strong>
                                        @if($r['is_period_resign'])
                                            <span class="kd-flag" style="color:{{ $tones['red'] }};background:{{ $bgs['red'] }};">Periode ini</span>
                                        @endif
                                    </td>
                                    <td style="text-align:left;">{{ $r['tenure'] }}</td>
                                    <td style="text-align:left;white-space:normal;max-width:14rem;">{{ $r['resign_reason'] }}</td>
                                    <td style="text-align:left;white-space:normal;max-width:14rem;">
                                        {{ $r['notes'] ?: '–' }}
                                    </td>
                                    @if($canManage)
                                        <td style="text-align:right;white-space:nowrap;">
                                            <button type="button" wire:click="openResignModal({{ $r['id'] }})" class="kd-act-btn" style="padding:.25rem .55rem;font-size:.75rem;">
                                                Edit
                                            </button>
                                            <button type="button" wire:click="revertResign({{ $r['id'] }})" wire:confirm="Kembalikan status karyawan ini menjadi Aktif?" class="kd-act-btn" style="padding:.25rem .55rem;font-size:.75rem;color:#34d399;">
                                                Aktifkan
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    @endif

    {{-- Detail Profile Drawer / Card --}}
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
                            @if($rec->notes)<div class="kd-sub" style="font-size:.68rem;">{{ $rec->notes }}</div>@endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="kd-empty">Belum ada data presensi untuk orang ini pada periode ini.</div>
            @endif
        </section>
    @endif

    {{-- MODAL: CATAT PRESENSI / IZIN / SAKIT / CUTI / ALPA --}}
    <x-master.modal :show="$showRecordModal" title="Catat Presensi / Izin / Sakit / Cuti" close="closeRecordModal" submit="saveRecord" max-width="34rem">
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rc-nik">Pilih Karyawan *</label>
                <div class="cbm-select-wrap">
                    <select id="rc-nik" wire:model="recordNik" class="cbm-form-select">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($employeesList as $emp)
                            <option value="{{ $emp->nik }}">{{ $emp->name }} ({{ $emp->nik }} - {{ $emp->station }})</option>
                        @endforeach
                    </select>
                </div>
                @error('recordNik') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rc-status">Status Presensi *</label>
                <div class="cbm-select-wrap">
                    <select id="rc-status" wire:model="recordStatus" class="cbm-form-select">
                        <option value="sakit">Sakit (S)</option>
                        <option value="cuti">Cuti (C)</option>
                        <option value="izin">Izin (I)</option>
                        <option value="alpa">Alpa / Mangkir (A)</option>
                        <option value="hadir">Hadir Manual (H)</option>
                    </select>
                </div>
                @error('recordStatus') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="rc-dfrom">Tanggal Mulai *</label>
                    <input id="rc-dfrom" type="date" wire:model="recordDateFrom" class="cbm-form-input">
                    @error('recordDateFrom') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="rc-dto">Tanggal Selesai *</label>
                    <input id="rc-dto" type="date" wire:model="recordDateTo" class="cbm-form-input">
                    @error('recordDateTo') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rc-notes">Keterangan / Notes</label>
                <textarea id="rc-notes" wire:model="recordNotes" class="cbm-form-input" rows="2" placeholder="Contoh: Surat Dokter RS Siloam, Cuti Tahunan, keperluan keluarga..." maxlength="500"></textarea>
                @error('recordNotes') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </x-master.modal>

    {{-- MODAL: CATAT STAFF RESIGN --}}
    <x-master.modal :show="$showResignModal" title="Catat Staff Resign" close="closeResignModal" submit="saveResign" max-width="34rem">
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            @if(!$resignEmployeeId)
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="rs-nik">Pilih Karyawan *</label>
                    <div class="cbm-select-wrap">
                        <select id="rs-nik" wire:model="resignNik" class="cbm-form-select">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($employeesList->where('status', '!=', 'Resign') as $emp)
                                <option value="{{ $emp->nik }}">{{ $emp->name }} ({{ $emp->nik }} - {{ $emp->station }})</option>
                            @endforeach
                        </select>
                    </div>
                    @error('resignNik') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rs-date">Tanggal Efektif Resign *</label>
                <input id="rs-date" type="date" wire:model="resignDate" class="cbm-form-input">
                @error('resignDate') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rs-reason">Alasan Resign *</label>
                <input id="rs-reason" type="text" wire:model="resignReason" class="cbm-form-input" list="common-reasons" placeholder="Alasan pengunduran diri...">
                <datalist id="common-reasons">
                    <option value="Mendapat pekerjaan lain">
                    <option value="Pindah domisili">
                    <option value="Melanjutkan studi / pendidikan">
                    <option value="Alasan pribadi / keluarga">
                    <option value="Kondisi kesehatan">
                    <option value="Kontrak kerja berakhir">
                    <option value="Pensiun">
                </datalist>
                @error('resignReason') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rs-notes">Catatan Tambahan</label>
                <textarea id="rs-notes" wire:model="resignNotes" class="cbm-form-input" rows="2" placeholder="Catatan serah terima tugas / aset / info tambahan..." maxlength="1000"></textarea>
                @error('resignNotes') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </x-master.modal>
</div>
