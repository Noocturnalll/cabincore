<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Aircraft Rotation</div>
            <div class="mod-title">Aircraft Rotation</div>
            <div class="mod-subtitle">Manajemen rotasi pesawat dan jadwal penerbangan.</div>
        </div>
        <div class="mod-actions">
        </div>
    </div>

    @if (session()->has('message'))
        <div style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(34, 197, 94, 0.2);">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            {{ session('error') }}
        </div>
    @endif

    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar" style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div class="mod-search-wrap" style="display: flex; gap: 10px; align-items: center;">
                <input type="date" class="mod-search-input">
            </div>
            
            <form action="{{ route('import.aircraft-rotation') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('span').innerText='Mengimport...';">
                @csrf
                <input type="file" name="importFile" class="mod-search-input" accept=".xlsx,.xls,.csv" style="padding-top: 5px;" required>
                <button type="submit" class="mod-btn" style="background: var(--cbm-accent-purple); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <span>Import Excel</span>
                </button>
            </form>
        </div>
        <div style="padding: 1.5rem; overflow-x: auto;">
            @if($rotations->count() > 0)
                <table class="capacity-table" style="width: 100%; border-collapse: collapse; text-align: center;">
                    <thead>
                        <tr style="background-color: rgba(0,0,0,0.2); border-bottom: 2px solid rgba(255,255,255,0.1);">
                            <th style="padding: 10px; border-right: 1px solid rgba(255,255,255,0.1);">Registration</th>
                            <th style="padding: 10px; border-right: 1px solid rgba(255,255,255,0.1);">Status</th>
                            <th style="padding: 10px; border-right: 1px solid rgba(255,255,255,0.1);">Flights Count</th>
                            <th style="padding: 10px;">Rotations (Legs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rotations as $rotation)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 10px; border-right: 1px solid rgba(255,255,255,0.1); font-weight: bold;">{{ $rotation->registration }}</td>
                                <td style="padding: 10px; border-right: 1px solid rgba(255,255,255,0.1);">
                                    <span style="background: {{ $rotation->status == 'OK' ? 'rgba(34, 197, 94, 0.2)' : 'rgba(239, 68, 68, 0.2)' }}; color: {{ $rotation->status == 'OK' ? '#4ade80' : '#f87171' }}; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;">
                                        {{ $rotation->status }}
                                    </span>
                                </td>
                                <td style="padding: 10px; border-right: 1px solid rgba(255,255,255,0.1);">{{ $rotation->declared_flights }}</td>
                                <td style="padding: 10px; text-align: left;">
                                    @if($rotation->legs->count() > 0)
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            @foreach($rotation->legs as $leg)
                                                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                                    <strong style="color: var(--cbm-accent-purple);">{{ $leg->flight_raw }}</strong> 
                                                    ({{ $leg->origin }} - {{ $leg->destination }}) 
                                                    <span style="color: #a1a1aa;">{{ $leg->dep_local }} - {{ $leg->arr_local }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #a1a1aa; font-size: 0.85rem;">No flights found in parsed data.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="color: var(--cbm-text-sub); text-align: center; padding: 2rem 0;">
                    Belum ada data rotasi pesawat. Silakan upload file Excel.
                </div>
            @endif
        </div>
    </div>
</div>
