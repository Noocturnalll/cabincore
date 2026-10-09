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
    @endphp

    <x-master.page-header title="Daily Briefing, Attendant List & 5R" subtitle="Bukti harian per station dan shift (foto briefing, daftar hadir, dan 5R) yang masuk dari form. Hari ini belum dihitung." accent="blue" eyebrow="Compliance">
        @if($canSync)
            <button type="button" wire:click="syncNow" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="syncNow">
                <span wire:loading.remove wire:target="syncNow">Sinkron sekarang</span>
                <span wire:loading wire:target="syncNow"><span class="cbm-spinner"></span> Membaca sheet...</span>
            </button>
        @endif
    </x-master.page-header>

    <x-flash />

    <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:.4rem;">
            @foreach(['7' => '7 hari', '30' => '30 hari', '90' => '90 hari', 'all' => 'Semua'] as $k => $l)
                <button type="button" wire:click="setRange('{{ $k }}')" class="cbm-tab {{ $range === (string) $k ? 'active' : '' }}">{{ $l }}</button>
            @endforeach
        </div>
        <div class="mod-subtitle">
            {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
            @if($setting->last_synced_at) · sinkron terakhir {{ $setting->last_synced_at->diffForHumans() }} @else · belum pernah disinkronkan @endif
            @if($setting->last_status === 'failed')<span style="color:#ef4444;"> · gagal: {{ $setting->last_message }}</span>@endif
        </div>
    </div>

    @php $t = $data['total']; $ts = $statusOf($t['percent']); @endphp
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

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>STATION</th><th>SHIFT</th>
                        <th style="text-align:right;">DIHARAPKAN</th><th style="text-align:right;">MASUK</th><th style="text-align:right;">LENGKAP</th>
                        @foreach($docLabel as $label)<th style="text-align:right;">{{ strtoupper($label) }}</th>@endforeach
                        <th style="text-align:right;">%</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['stations'] as $code => $row)
                        @php $s = $statusOf($row['percent']); @endphp
                        <tr wire:key="cs-{{ $code }}" style="{{ $selected === $code ? 'background:rgba(59,130,246,.08);' : '' }}">
                            <td><strong>{{ $code }}</strong></td>
                            <td class="mod-subtitle">{{ implode(' + ', config("compliance.stations.$code.shifts")) }}</td>
                            <td style="text-align:right;">{{ $row['expected'] }}</td>
                            <td style="text-align:right;">{{ $row['submitted'] }}</td>
                            <td style="text-align:right;">{{ $row['complete'] }}</td>
                            @foreach($docLabel as $key => $label)<td style="text-align:right;">{{ $row['docs'][$key] }}</td>@endforeach
                            <td style="text-align:right;">
                                <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $tone[$s][0] }};background:{{ $tone[$s][1] }};">{{ $row['percent'] !== null ? $row['percent'].'%' : '-' }}</span>
                            </td>
                            <td style="text-align:right;"><button type="button" wire:click="pick('{{ $code }}')" class="mod-action-btn">{{ $selected === $code ? 'Tutup' : 'Rincian' }}</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($selected)
        @php $row = $data['stations'][$selected]; @endphp
        <div class="mod-card" style="margin-top:1rem;">
            <div style="padding:.9rem 1rem;font-weight:600;">Rincian {{ $selected }}</div>

            @if($row['missing'] || $row['incomplete'])
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
                                <span style="display:inline-block;margin:.15rem .25rem .15rem 0;padding:.1rem .45rem;border-radius:.4rem;background:rgba(245,158,11,.14);">{{ \Carbon\Carbon::parse($m['date'])->format('d M') }} {{ $m['shift'] }} · tanpa {{ implode(', ', array_map(fn ($k) => $docLabel[$k], $m['lacking'])) }}</span>
                            @empty
                                <span class="mod-subtitle">Tidak ada</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead><tr><th>TANGGAL</th>@foreach($shiftsOf as $shift)<th>{{ strtoupper($shift) }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse($days as $date => $list)
                            <tr wire:key="cd-{{ $date }}">
                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</td>
                                @foreach($shiftsOf as $shift)
                                    @php $e = $list->firstWhere('shift', $shift); @endphp
                                    <td>
                                        @if($e)
                                            @foreach($docLabel as $key => $label)
                                                @if($e->{'url_'.$key})
                                                    <a href="{{ $e->{'url_'.$key} }}" target="_blank" rel="noopener noreferrer" class="mod-action-btn" style="margin-right:.25rem;">{{ $label }}</a>
                                                @else
                                                    <span style="margin-right:.25rem;padding:.2rem .5rem;border-radius:.4rem;font-size:.75rem;color:#ef4444;background:rgba(239,68,68,.12);">{{ $label }} ✕</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="mod-subtitle">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ 1 + count($shiftsOf) }}"><div class="mod-empty"><div class="mod-empty-title">Belum ada data</div><div class="mod-empty-sub">Jalankan sinkronisasi untuk menarik data dari sheet.</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
