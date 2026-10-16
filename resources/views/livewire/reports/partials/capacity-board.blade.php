@php
    $stateStyle = [
        'over' => ['#ef4444', 'Melebihi kapasitas'],
        'good' => ['#34d399', 'Sesuai target'],
        'room' => ['#f59e0b', 'Masih ada ruang'],
        'low' => ['#94a3b8', 'Rendah'],
        'none' => ['#64748b', 'Belum ada data'],
    ];
    $bar = function (?float $pct, string $state) use ($stateStyle) {
        $w = $pct === null ? 0 : min(100, max(0, $pct));
        return '<div style="background:rgba(148,163,184,.18);border-radius:999px;height:.6rem;overflow:hidden;"><div style="width:'.$w.'%;height:100%;background:'.$stateStyle[$state][0].';border-radius:999px;"></div></div>';
    };
@endphp

<section class="mod-card mod-card-accent-blue" style="margin-bottom:1rem;" aria-label="Kapasitas per divisi">
    <div style="padding:1rem 1.25rem .25rem;">
        <strong>Kapasitas per Divisi</strong>
        <span class="mod-subtitle"> · kapasitas (MP × jam efektif) adalah target; batang = jam yang terpakai dari kapasitas</span>
    </div>
    <div style="padding:.5rem 1.25rem 1rem; display:grid; gap:.9rem; grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));">
        @foreach($capacityBoard['divisions'] as $d)
            @php [$color, $word] = $stateStyle[$d['state']]; @endphp
            <div wire:key="cap-{{ $d['team'] }}" style="border:1px solid rgba(148,163,184,.2);border-radius:.85rem;padding:.9rem 1rem;">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.5rem;">
                    <strong>{{ $d['label'] }}</strong>
                    <span style="color:{{ $color }};font-weight:800;">{{ $d['pct'] !== null ? number_format($d['pct'], 0).'%' : '–' }}</span>
                </div>
                <div style="margin:.55rem 0;">{!! $bar($d['pct'], $d['state']) !!}</div>
                <div class="mod-subtitle" style="font-size:.8rem;line-height:1.5;">
                    Kapasitas <strong>{{ number_format($d['capacity'], 0) }}</strong> jam
                    @if($d['mp_avg'] !== null) · {{ number_format($d['mp_avg'], 1) }} MP/hari @endif<br>
                    @if($d['used'] !== null)
                        Terpakai <strong>{{ number_format($d['used'], 0) }}</strong> jam ·
                        {{ $d['gap'] >= 0 ? 'sisa '.number_format($d['gap'], 0) : 'lebih '.number_format(abs($d['gap']), 0) }} jam
                    @else
                        Jam terpakai belum tercatat untuk divisi ini
                    @endif
                </div>
                <div style="margin-top:.4rem;font-size:.75rem;color:{{ $color }};font-weight:700;">{{ $word }}</div>
            </div>
        @endforeach
    </div>
    <div class="mod-subtitle" style="padding:0 1.25rem 1rem;font-size:.75rem;">
        Hijau 80–100% sesuai target · kuning 50–80% masih ada ruang · merah di atas 100% melebihi kapasitas · abu-abu di bawah 50% atau belum ada data.
    </div>
</section>

<section class="mod-card mod-card-accent-green" style="margin-bottom:1rem;" aria-label="Capability AIEC per station">
    <div style="padding:1rem 1.25rem .25rem;">
        <strong>Capability AIEC per Station</strong>
        <span class="mod-subtitle"> · batas pesawat per hari (DCI / DCE) dibanding rata-rata dan puncak di periode ini</span>
    </div>
    <div class="mod-table-wrap">
        <table class="mod-table">
            <thead>
                <tr>
                    <th>STATION</th><th>MP PLAN</th>
                    <th style="text-align:right;">DCI BATAS</th><th style="text-align:right;">DCI RATA2</th><th style="text-align:right;">DCI PUNCAK</th>
                    <th style="text-align:right;">DCE BATAS</th><th style="text-align:right;">DCE RATA2</th><th style="text-align:right;">DCE PUNCAK</th>
                    <th style="min-width:8rem;">TERPAKAI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($capacityBoard['aiec'] as $a)
                    @php
                        [$color, $word] = $stateStyle[$a['state']];
                        $pctWorst = max($a['DCI']['pct'] ?? 0, $a['DCE']['pct'] ?? 0);
                    @endphp
                    <tr wire:key="aiec-{{ $a['station'] }}">
                        <td><strong>{{ $a['station'] }}</strong><div class="mod-subtitle" style="font-size:.72rem;">{{ $a['name'] }}</div></td>
                        <td>{{ $a['mp_plan'] !== null ? number_format($a['mp_plan'], 1) : '–' }}</td>
                        @foreach(['DCI', 'DCE'] as $t)
                            <td style="text-align:right;">{{ $a[$t]['cap'] }}</td>
                            <td style="text-align:right;">{{ $a[$t]['avg'] }}</td>
                            <td style="text-align:right;{{ $a[$t]['over'] ? 'color:#ef4444;font-weight:800;' : '' }}">{{ $a[$t]['peak'] }}</td>
                        @endforeach
                        <td>
                            {!! $bar($pctWorst, $a['state']) !!}
                            <div style="font-size:.72rem;color:{{ $color }};font-weight:700;">{{ number_format($pctWorst, 0) }}% · {{ $word }}</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="mod-empty"><div class="mod-empty-title">Tidak ada station AIEC pada cakupan ini</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
