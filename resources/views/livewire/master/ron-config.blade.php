<div>
    <div class="mod-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Master Data</div>
            <div class="mod-title">RON Settings</div>
            <div class="mod-subtitle">Kelola konfigurasi Remain Over Night (RON) per stasiun.</div>
        </div>
        <div>
            <button type="button" 
                    onclick="Swal.fire({
                        title: 'Apakah Anda Yakin?',
                        text: 'Nilai RON akan di-reset menjadi 0!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Ya, Reset ke 0!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.resetToDefault()
                        }
                    })"
                    class="mod-btn" 
                    style="background-color: #ef4444; color: white; font-weight: 600; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Set Default
            </button>
        </div>
    </div>

    <div class="mod-card mod-card-accent-purple" style="padding: 24px;">
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>KH REGION</th>
                        <th>STA</th>
                        <th>JT</th>
                        <th>IW</th>
                        <th>ID</th>
                        <th>IU</th>
                        <th>SL</th>
                        <th>OD</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stations as $s)
                    <tr wire:key="station-{{ $s->id }}">
                        <td>{{ $s->order_no }}</td>
                        <td style="font-weight: bold;">{{ $s->kh_region }}</td>
                        <td style="font-weight: bold;">{{ $s->station_code }}</td>
                        <td>{{ $s->ron_jt }}</td>
                        <td>{{ $s->ron_iw }}</td>
                        <td>{{ $s->ron_id }}</td>
                        <td>{{ $s->ron_iu }}</td>
                        <td>{{ $s->ron_sl }}</td>
                        <td>{{ $s->ron_od }}</td>
                        <td>
                            <button type="button" wire:click.prevent="editRon({{ $s->id }})" style="padding: 4px 10px; font-size: 0.8rem; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Edit RON</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($isModalOpen)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
            <div class="mod-card" style="width: 500px; max-height: 90vh; overflow-y: auto; padding: 24px;">
                <div style="font-weight: bold; font-size: 1.2rem; margin-bottom: 15px;">Edit RON - {{ $station_code }} ({{ $kh_region }})</div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div><label>JT</label><input type="number" wire:model="ron_jt" class="mod-search-input" style="width: 100%;"></div>
                    <div><label>IW</label><input type="number" wire:model="ron_iw" class="mod-search-input" style="width: 100%;"></div>
                    <div><label>ID</label><input type="number" wire:model="ron_id" class="mod-search-input" style="width: 100%;"></div>
                    <div><label>IU</label><input type="number" wire:model="ron_iu" class="mod-search-input" style="width: 100%;"></div>
                    <div><label>SL</label><input type="number" wire:model="ron_sl" class="mod-search-input" style="width: 100%;"></div>
                    <div><label>OD</label><input type="number" wire:model="ron_od" class="mod-search-input" style="width: 100%;"></div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" wire:click.prevent="$set('isModalOpen', false)" class="mod-btn">Cancel</button>
                    <button type="button" wire:click.prevent="saveRon" class="mod-btn mod-btn-primary">Save RON</button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
