<div>
    @php
        $chip = [
            'green' => ['#34d399', 'rgba(52,211,153,.14)', 'Tercapai'],
            'yellow' => ['#f59e0b', 'rgba(245,158,11,.16)', 'Kurang ≤10%'],
            'red' => ['#ef4444', 'rgba(239,68,68,.16)', 'Kurang >10%'],
            'none' => ['#94a3b8', 'rgba(148,163,184,.14)', 'Belum ada data'],
        ];
        $st = $manHours['status'];
    @endphp

    <x-master.page-header title="KPI & Man Hours" subtitle="Output per modul dan pemakaian man hours terhadap kapasitas. Minggu berjalan Kamis–Rabu." accent="blue" eyebrow="Reporting" />

    <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:.4rem;">
            @foreach(['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan'] as $k => $l)
                <button type="button" wire:click="setKind('{{ $k }}')" class="cbm-tab {{ $kind === $k ? 'active' : '' }}">{{ $l }}</button>
            @endforeach
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <button type="button" wire:click="shift(-1)" class="mod-action-btn" aria-label="Periode sebelumnya">‹</button>
            <strong>{{ $period->label() }}</strong>
            <button type="button" wire:click="shift(1)" class="mod-action-btn" aria-label="Periode berikutnya">›</button>
            <input type="date" wire:model.live="date" class="cbm-form-input" style="width:auto;" aria-label="Tanggal acuan">
        </div>
    </div>

    <div class="mod-card mod-card-accent-blue" style="margin-bottom:1rem;">
        <div style="padding:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:1rem;">
            <div>
                <div class="mod-subtitle">Kapasitas man hours</div>
                <div style="font-size:1.6rem;font-weight:700;">{{ number_format($manHours['capacity']['hours'], 1) }} jam</div>
                <div class="mod-subtitle">
                    @if($manHours['capacity']['source'] === 'roster')
                        Roster: rata-rata {{ $manHours['capacity']['technicians'] }} MP/hari × {{ $manHours['capacity']['days'] }} hari, jam efektif Pagi/Siang {{ config('kpi.effective_hours_by_shift.PAGI') }} · Malam {{ config('kpi.effective_hours_by_shift.MALAM') }}
                    @else
                        Master Capacity: {{ $manHours['capacity']['technicians'] }} teknisi × {{ rtrim(rtrim(number_format($manHours['capacity']['hours_per_tech_day'], 1), '0'), '.') }} jam efektif × {{ $manHours['capacity']['days'] }} hari (roster belum diimpor untuk periode ini)
                    @endif
                </div>
            </div>
            <div>
                <div class="mod-subtitle">Terpakai (tercatat)</div>
                <div style="font-size:1.6rem;font-weight:700;">{{ number_format($manHours['total_used'], 1) }} jam</div>
            </div>
            <div>
                <div class="mod-subtitle">Utilisasi</div>
                <div style="font-size:1.6rem;font-weight:700;">{{ $manHours['utilisation'] !== null ? $manHours['utilisation'].'%' : '-' }}</div>
                <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $chip[$st][0] }};background:{{ $chip[$st][1] }};">{{ $chip[$st][2] }}</span>
            </div>
        </div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>MODUL</th><th style="text-align:right;">MAN HOURS</th><th style="text-align:right;">DATA</th><th>SUMBER JAM</th></tr></thead>
                <tbody>
                    @foreach($manHours['used'] as $key => $m)
                        <tr>
                            <td>{{ $m['label'] }}</td>
                            <td style="text-align:right;">{{ $m['hours'] === null ? '-' : number_format($m['hours'], 1) }}</td>
                            <td style="text-align:right;">{{ $m['timed'] }} / {{ $m['records'] }} berjam</td>
                            <td class="mod-subtitle">
                                @if($key === 'cleaning') Jam mulai → selesai
                                @else Laporan leader (MP × jam) atau kolom MAN HOUR
                                @endif
                                @if($m['records'] > $m['timed'])<span style="color:#f59e0b;"> · {{ $m['records'] - $m['timed'] }} data belum ada jam</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>MODUL</th><th style="text-align:right;">TOTAL</th><th style="text-align:right;">CLOSED</th><th style="text-align:right;">OPEN</th><th style="text-align:right;">% CLOSED</th></tr></thead>
                <tbody>
                    @foreach($output as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td style="text-align:right;">{{ $row['total'] }}</td>
                            <td style="text-align:right;">{{ $row['closed'] }}</td>
                            <td style="text-align:right;">{{ $row['open'] }}</td>
                            <td style="text-align:right;">{{ $row['rate'] !== null ? $row['rate'].'%' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
