<div>
    @php
        $tone = [
            'green' => ['#34d399', 'rgba(52,211,153,.14)'],
            'yellow' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'red' => ['#ef4444', 'rgba(239,68,68,.16)'],
            'none' => ['#94a3b8', 'rgba(148,163,184,.14)'],
        ];
        $statusOf = fn ($p) => \App\Services\Kpi\AchievementStatus::for($p, 100);
        $chip = fn ($p) => '<span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:'.$tone[$statusOf($p)][0].';background:'.$tone[$statusOf($p)][1].';">'.($p !== null ? $p.'%' : '-').'</span>';
    @endphp

    <x-master.page-header title="LGT Monitoring" subtitle="Pekerjaan saat Long Ground Time: terlaksana (Closed) dan batal (Cancel) untuk tim CBM dan AIEC. Minggu Senin–Minggu, sama dengan workbook LGT." accent="blue" eyebrow="Reporting" />

    <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:.4rem;">
            @foreach(['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan'] as $k => $l)
                <button type="button" wire:click="setKind('{{ $k }}')" class="cbm-tab {{ $kind === $k ? 'active' : '' }}">{{ $l }}</button>
            @endforeach
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <button type="button" wire:click="shift(-1)" class="mod-action-btn" aria-label="Periode sebelumnya">‹</button>
            <strong>{{ $period->label() }}@if($kind === 'week') · W{{ $period->from->isoWeek() }}@endif</strong>
            <button type="button" wire:click="shift(1)" class="mod-action-btn" aria-label="Periode berikutnya">›</button>
            <input type="date" wire:model.live="date" class="cbm-form-input" style="width:auto;" aria-label="Tanggal acuan">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.75rem;margin-bottom:1rem;">
        @foreach(['cbm' => 'CBM', 'aiec' => 'AIEC (Cleaning)'] as $team => $label)
            <div class="mod-card" style="padding:.9rem 1rem;">
                <div class="mod-subtitle">{{ $label }}</div>
                <div style="font-size:1.8rem;font-weight:700;">{!! $chip($totals[$team]['percent']) !!}</div>
                <div class="mod-subtitle">{{ $totals[$team]['done'] }} terlaksana · {{ $totals[$team]['cancel'] }} batal · {{ $totals[$team]['open'] }} open · dari {{ $totals[$team]['total'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th rowspan="2">STATION</th>
                        <th colspan="4" style="text-align:center;">CBM</th>
                        <th colspan="4" style="text-align:center;">AIEC</th>
                    </tr>
                    <tr>
                        <th style="text-align:right;">TOTAL</th><th style="text-align:right;">TERLAKSANA</th><th style="text-align:right;">BATAL</th><th style="text-align:right;">ACHV</th>
                        <th style="text-align:right;">TOTAL</th><th style="text-align:right;">TERLAKSANA</th><th style="text-align:right;">BATAL</th><th style="text-align:right;">ACHV</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $s)
                        <tr wire:key="lg-{{ $s['station'] }}">
                            <td><strong>{{ $s['station'] }}</strong></td>
                            @foreach(['cbm', 'aiec'] as $team)
                                <td style="text-align:right;">{{ $s[$team]['total'] }}</td>
                                <td style="text-align:right;">{{ $s[$team]['done'] }}</td>
                                <td style="text-align:right;">{{ $s[$team]['cancel'] }}</td>
                                <td style="text-align:right;">{!! $chip($s[$team]['percent']) !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="mod-empty"><div class="mod-empty-title">Belum ada data LGT pada periode ini</div><div class="mod-empty-sub">Impor dari Excel: php artisan kpi:import lgt "file.xlsx"</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reasons->isNotEmpty())
        <div class="mod-card" style="margin-top:1rem;padding:.9rem 1rem;">
            <div style="font-weight:600;margin-bottom:.5rem;">Alasan pembatalan CBM teratas</div>
            @foreach($reasons as $reason => $n)
                <div style="display:flex;justify-content:space-between;gap:1rem;padding:.2rem 0;border-bottom:1px solid rgba(148,163,184,.15);"><span>{{ $reason }}</span><strong>{{ $n }}</strong></div>
            @endforeach
        </div>
    @endif
</div>
