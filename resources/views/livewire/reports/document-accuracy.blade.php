<div>
    @php
        $tone = [
            'green' => ['#34d399', 'rgba(52,211,153,.14)'],
            'yellow' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'red' => ['#ef4444', 'rgba(239,68,68,.16)'],
            'none' => ['#94a3b8', 'rgba(148,163,184,.14)'],
        ];
        $statusOf = fn ($p) => \App\Services\Kpi\AchievementStatus::for($p, 100);
        $ts = $statusOf($total['percent']);
    @endphp

    <x-master.page-header title="KPI Document Accuracy" subtitle="Dokumen CML dan NSRDI yang dilaporkan station dibanding yang tercatat di eMRO. Akurasi = tercatat ÷ dilaporkan." accent="blue" eyebrow="Reporting" />

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

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem;margin-bottom:1rem;">
        <div class="mod-card" style="padding:.9rem 1rem;"><div class="mod-subtitle">Akurasi</div><div style="font-size:1.8rem;font-weight:700;color:{{ $tone[$ts][0] }};">{{ $total['percent'] !== null ? $total['percent'].'%' : '-' }}</div></div>
        <div class="mod-card" style="padding:.9rem 1rem;"><div class="mod-subtitle">Dilaporkan</div><div style="font-size:1.8rem;font-weight:700;">{{ number_format($total['reported']) }}</div></div>
        <div class="mod-card" style="padding:.9rem 1rem;"><div class="mod-subtitle">Tercatat</div><div style="font-size:1.8rem;font-weight:700;">{{ number_format($total['recorded']) }}</div></div>
        <div class="mod-card" style="padding:.9rem 1rem;"><div class="mod-subtitle">Terlewat</div><div style="font-size:1.8rem;font-weight:700;color:{{ $total['missed'] ? '#ef4444' : 'inherit' }};">{{ number_format($total['missed']) }}</div></div>
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>STATION</th><th style="text-align:right;">CML</th><th style="text-align:right;">NSRDI</th><th style="text-align:right;">DILAPORKAN</th><th style="text-align:right;">TERCATAT</th><th style="text-align:right;">TERLEWAT</th><th style="text-align:right;">AKURASI</th></tr></thead>
                <tbody>
                    @forelse($rows as $r)
                        @php $s = $statusOf($r['percent']); @endphp
                        <tr wire:key="da-{{ $r['station'] }}">
                            <td><strong>{{ $r['station'] }}</strong></td>
                            <td style="text-align:right;">{{ number_format($r['cml']) }}</td>
                            <td style="text-align:right;">{{ number_format($r['nsrdi']) }}</td>
                            <td style="text-align:right;">{{ number_format($r['reported']) }}</td>
                            <td style="text-align:right;">{{ number_format($r['recorded']) }}</td>
                            <td style="text-align:right;color:{{ $r['missed'] ? '#ef4444' : 'inherit' }};">{{ $r['missed'] }}</td>
                            <td style="text-align:right;"><span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $tone[$s][0] }};background:{{ $tone[$s][1] }};">{{ $r['percent'] !== null ? $r['percent'].'%' : '-' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="mod-empty"><div class="mod-empty-title">Belum ada data pada periode ini</div><div class="mod-empty-sub">Impor dari Excel: php artisan kpi:import accuracy "file.xlsx"</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($notes->isNotEmpty())
        <div class="mod-card" style="margin-top:1rem;">
            <div style="padding:.75rem 1rem;font-weight:600;">Dokumen terlewat pada periode ini</div>
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead><tr><th>TANGGAL</th><th>STATION</th><th style="text-align:right;">CML</th><th style="text-align:right;">NSRDI</th><th>CATATAN</th></tr></thead>
                    <tbody>
                        @foreach($notes as $n)
                            <tr wire:key="dn-{{ $n->id }}"><td>{{ $n->work_date->format('d M Y') }}</td><td>{{ $n->station }}</td><td style="text-align:right;">{{ $n->cml_missed }}</td><td style="text-align:right;">{{ $n->nsrdi_missed }}</td><td class="mod-subtitle">{{ $n->remarks ?? '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
