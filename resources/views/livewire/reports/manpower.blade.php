<div>
    <x-master.page-header title="Manpower Harian" subtitle="Jumlah MP yang bekerja per station, tim, dan shift, dari roster yang diimpor. Tidak termasuk OFF, cuti, sakit, dan training." accent="blue" eyebrow="Reporting" />

    <div class="mod-card" style="padding:.9rem 1rem;margin-bottom:1rem;display:flex;gap:.5rem;align-items:center;justify-content:flex-end;">
        <button type="button" wire:click="shift(-1)" class="mod-action-btn" aria-label="Hari sebelumnya">‹</button>
        <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d M Y') }}</strong>
        <button type="button" wire:click="shift(1)" class="mod-action-btn" aria-label="Hari berikutnya">›</button>
        <input type="date" wire:model.live="date" class="cbm-form-input" style="width:auto;" aria-label="Tanggal">
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th rowspan="2">STATION</th>
                        @foreach($teams as $team)<th colspan="3" style="text-align:center;">{{ $team }}</th>@endforeach
                        <th rowspan="2" style="text-align:right;">TOTAL</th><th rowspan="2" style="text-align:right;">OFF/CUTI</th>
                    </tr>
                    <tr>
                        @foreach($teams as $team)
                            <th style="text-align:right;">PAGI</th><th style="text-align:right;">SIANG</th><th style="text-align:right;">MALAM</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $s)
                        <tr wire:key="mp-{{ $s['station'] }}">
                            <td><strong>{{ $s['station'] }}</strong></td>
                            @foreach($teams as $team)
                                @foreach(['PAGI', 'SIANG', 'MALAM'] as $shift)
                                    <td style="text-align:right;{{ $s[$team][$shift] ? '' : 'opacity:.35;' }}">{{ $s[$team][$shift] }}</td>
                                @endforeach
                            @endforeach
                            <td style="text-align:right;"><strong>{{ $s['total'] }}</strong></td>
                            <td style="text-align:right;" class="mod-subtitle">{{ $s['off'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 3 + count($teams) * 3 }}"><div class="mod-empty"><div class="mod-empty-title">Roster belum diimpor untuk tanggal ini</div><div class="mod-empty-sub">php artisan roster:import "ROSTER ... .xlsx"</div></div></td></tr>
                    @endforelse
                </tbody>
                @if($stations->isNotEmpty())
                    <tfoot>
                        <tr style="font-weight:700;">
                            <td>TOTAL</td>
                            @foreach($teams as $team)
                                @foreach(['PAGI', 'SIANG', 'MALAM'] as $shift)<td style="text-align:right;">{{ $totals[$team][$shift] }}</td>@endforeach
                            @endforeach
                            <td style="text-align:right;">{{ $totals['total'] }}</td><td style="text-align:right;">{{ $totals['off'] }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
