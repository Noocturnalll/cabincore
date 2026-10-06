<div>
    <style>
        .capacity-table th, .capacity-table td {
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            padding: 0.25rem 0.5rem !important; /* Compact padding for 19 columns */
            vertical-align: middle;
            text-align: center !important;
        }
        .cbm-light .capacity-table th, .cbm-light .capacity-table td {
            border: 1px solid #cbd5e1 !important;
        }
        .capacity-table tfoot tr {
            background-color: rgba(255, 255, 255, 0.03); /* Subtle dark mode footer bg */
        }
        .cbm-light .capacity-table tfoot tr {
            background-color: #f1f5f9;
        }
    </style>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Capacity Management</div>
            <div class="mod-title">Capacity Management</div>
            <div class="mod-subtitle">Manajemen kapasitas stasiun dan personel kabin.</div>
        </div>
        <div class="mod-actions">
        </div>
    </div>

    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="mod-search-wrap" style="display: flex; gap: 10px; align-items: center;">
                <input wire:model.live="activeDate" type="date" class="mod-search-input">
                @if($hasReport)
                    <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; border: 1px solid rgba(16,185,129,0.2);">Data Arsip (Tersimpan)</span>
                @else
                    <span style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; border: 1px solid rgba(245,158,11,0.2);">Data Live</span>
                @endif
            </div>
        </div>

        @if(isset($nsrdiTotals) && isset($targets))
        <div style="display:flex; gap:10px; margin: 15px; margin-bottom: 0;">
            @foreach(['JT', 'IU', 'ID'] as $aoc)
                @php
                    $current = $nsrdiTotals[$aoc] ?? 0;
                    $target = $targets[$aoc];
                    // "Tembus target" usually means >= target for positive goals, 
                    // or <= target if it's a defect limit. We use >= here based on the phrasing "tembus".
                    $isAchievement = $current >= $target;
                @endphp
                <div class="mod-card" style="padding: 10px 15px; border-left: 4px solid {{ $isAchievement ? '#10b981' : '#f59e0b' }}; background: {{ $isAchievement ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)' }}; flex: 1;">
                    <div style="font-weight: 700; font-size: 1.1rem; color: {{ $isAchievement ? '#10b981' : '#f59e0b' }};">
                        {{ $aoc }} Target NSRDI: {{ $current }} / {{ $target }}
                    </div>
                    <div style="font-size: 0.85rem; margin-top: 4px; color: var(--cbm-text-muted);">
                        @if($isAchievement)
                            🎉 Achievement: Target terpenuhi!
                        @else
                            ⚠️ Warning: Belum tembus target {{ $target }}!
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <div class="mod-table-wrap" style="margin: 15px 0 0 0; padding: 0;">
            <table class="mod-table capacity-table" style="white-space: nowrap; text-align: center;">
                <thead>
                    <tr>
                        <th rowspan="2" colspan="3" style="vertical-align: middle;">NO</th>
                        <th rowspan="2" colspan="2" style="vertical-align: middle;">STA</th>
                        <th colspan="3" style="text-align: center; letter-spacing: 1px;">CABIN TECHNICIAN</th>
                        <th colspan="6" style="text-align: center; letter-spacing: 1px;">A/C RON</th>
                        <th colspan="5" style="text-align: center; letter-spacing: 1px;">NSRDI</th>
                    </tr>
                    <tr>
                        <th>TIME</th>
                        <th>DAY</th>
                        <th>NIGHT</th>
                        <th>JT</th>
                        <th>IW</th>
                        <th>ID</th>
                        <th>IU</th>
                        <th>SL</th>
                        <th>OD</th>
                        <th>JT</th>
                        <th>IW</th>
                        <th>ID</th>
                        <th>IU</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totals = [
                            'day' => 0, 'night' => 0,
                            'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0],
                            'nsrdi' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'TOTAL' => 0],
                        ];
                    @endphp
                    @foreach($capacityData as $kh => $rows)
                        @foreach($rows as $index => $row)
                            @php
                                $totals['day'] += $row['day'] ?? 0;
                                $totals['night'] += $row['night'] ?? 0;
                                foreach($row['ron'] as $k => $v) $totals['ron'][$k] += $v;
                                foreach(['JT','IW','ID','IU'] as $k) {
                                    $totals['nsrdi'][$k] += $row['nsrdi'][$k] ?? 0;
                                }
                                $totals['nsrdi']['TOTAL'] += $row['nsrdi']['TOTAL'] ?? 0;
                            @endphp
                            <tr>
                                @if($index === 0)
                                    <td rowspan="{{ count($rows) }}" style="font-weight: 700; vertical-align: middle;">{{ $kh }}</td>
                                @endif
                                <td>{{ $row['no'] }}</td>
                                <td>{{ $row['group'] }}</td>
                                <td style="font-weight: 700;">{{ $row['sta'] }}</td>
                                <td style="font-weight: 700;">{{ $row['code_store'] }}</td>
                                <td>{{ $row['time'] }}</td>
                                <td>{{ $row['day'] ?: '-' }}</td>
                                <td>{{ $row['night'] ?: '-' }}</td>
                                
                                <!-- RON -->
                                @foreach(['JT','IW','ID','IU','SL','OD'] as $k)
                                    <td>{{ $row['ron'][$k] ?: '-' }}</td>
                                @endforeach

                                <!-- NSRDI -->
                                @foreach(['JT','IW','ID','IU'] as $k)
                                    <td>{{ $row['nsrdi'][$k] ?: '-' }}</td>
                                @endforeach
                                <td style="font-weight: 700;">{{ $row['nsrdi']['TOTAL'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" style="text-align: right; padding-right: 15px; font-weight: 800;">TOTAL</td>
                        <td style="font-weight: 800;">{{ $totals['day'] }}</td>
                        <td style="font-weight: 800;">{{ $totals['night'] }}</td>
                        @foreach(['JT','IW','ID','IU','SL','OD'] as $k)
                            <td style="font-weight: 800;">{{ $totals['ron'][$k] }}</td>
                        @endforeach
                        
                        <!-- NSRDI Totals -->
                        <td style="font-weight: 800;">{{ $totals['nsrdi']['JT'] }}</td>
                        <td style="font-weight: 800;">{{ $totals['nsrdi']['IW'] }}</td>
                        <td style="font-weight: 800;">{{ $totals['nsrdi']['ID'] }}</td>
                        <td style="font-weight: 800;">{{ $totals['nsrdi']['IU'] }}</td>
                        <td style="font-weight: 800;">{{ $totals['nsrdi']['TOTAL'] }}</td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right; padding-right: 15px; font-weight: 800; border-top: none;">GRAND TOTAL</td>
                        <td colspan="2" style="font-weight: 800; color: var(--cbm-text);">{{ $totals['day'] + $totals['night'] }}</td>
                        <td colspan="6" style="font-weight: 800; color: var(--cbm-text);">{{ array_sum($totals['ron']) }}</td>
                        <td colspan="5" style="font-weight: 800; color: var(--cbm-text); font-size: 1.1rem; text-align: center;">{{ $totals['nsrdi']['TOTAL'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
