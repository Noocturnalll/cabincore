<div>
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Master Data</div>
            <div class="mod-title">Capacity Settings</div>
            <div class="mod-subtitle">Kelola konfigurasi target NSRDI dan stasiun untuk tabel Capacity.</div>
        </div>
    </div>

    <div class="mod-card mod-card-accent-purple" style="padding: 24px;">
        <div class="mod-tabs" style="display: flex; gap: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; padding-bottom: 10px;">
            <button wire:click="setTab('stations')" style="background:none; border:none; color: {{ $activeTab == 'stations' ? '#8b5cf6' : 'var(--cbm-text)' }}; font-weight: {{ $activeTab == 'stations' ? '700' : '400' }}; font-size: 1rem; cursor: pointer;">Stations</button>
            <button wire:click="setTab('targets')" style="background:none; border:none; color: {{ $activeTab == 'targets' ? '#8b5cf6' : 'var(--cbm-text)' }}; font-weight: {{ $activeTab == 'targets' ? '700' : '400' }}; font-size: 1rem; cursor: pointer;">NSRDI Targets</button>
        </div>

        @if($activeTab == 'stations')
            <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
                <button wire:click="createStation" class="mod-btn mod-btn-primary">
                    <i class="fas fa-plus"></i> Add Station
                </button>
            </div>
            
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>KH REGION</th>
                            <th>GROUP</th>
                            <th>STA</th>
                            <th>CODE STORE</th>
                            <th>HOURS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stations as $s)
                        <tr wire:key="station-{{ $s->id }}">
                            <td>{{ $s->order_no }}</td>
                            <td style="font-weight: bold;">{{ $s->kh_region }}</td>
                            <td>{{ $s->group_type }}</td>
                            <td style="font-weight: bold;">{{ $s->station_code }}</td>
                            <td>{{ $s->code_store }}</td>
                            <td>{{ $s->working_hours }}</td>
                            <td style="display: flex; gap: 5px;">
                                <button type="button" wire:click.prevent="editStation({{ $s->id }})" style="padding: 4px 10px; font-size: 0.8rem; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Edit</button>
                                <button type="button" wire:click.prevent="deleteStation({{ $s->id }})" style="padding: 4px 10px; font-size: 0.8rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="confirm('Are you sure you want to delete this station?') || event.stopImmediatePropagation()">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($isModalOpen)
            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                <div class="mod-card" style="width: 800px; max-height: 90vh; overflow-y: auto; padding: 24px;">
                    <div style="font-weight: bold; font-size: 1.2rem; margin-bottom: 15px;">{{ $station_id ? 'Edit Station' : 'Add Station' }}</div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label>Order No</label>
                            <input type="number" wire:model="order_no" class="mod-search-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>KH Region (e.g. KH-1)</label>
                            <input type="text" wire:model="kh_region" class="mod-search-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>Group Type</label>
                            <input type="text" wire:model="group_type" class="mod-search-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>Station Code (e.g. CGK)</label>
                            <select wire:model="station_code" class="mod-search-input" style="width: 100%;">
                                <option value="">-- Pilih Station --</option>
                                @foreach($airports as $airport)
                                    <option value="{{ $airport->kode }}">{{ $airport->kode }} - {{ $airport->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Code Store</label>
                            <input type="text" wire:model="code_store" class="mod-search-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>Working Hours</label>
                            <input type="text" wire:model="working_hours" class="mod-search-input" style="width: 100%;">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" wire:click.prevent="$set('isModalOpen', false)" class="mod-btn">Cancel</button>
                        <button type="button" wire:click.prevent="saveStation" class="mod-btn mod-btn-primary">Save Station</button>
                    </div>
                </div>
            </div>
            @endif

        @elseif($activeTab == 'targets')
            <div style="max-width: 500px;">
                @if (session()->has('message'))
                    <div style="padding: 10px; background: rgba(16,185,129,0.1); color: #10b981; border-left: 4px solid #10b981; margin-bottom: 15px;">
                        {{ session('message') }}
                    </div>
                @endif
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Target Tembus NSRDI - JT</label>
                    <input type="number" wire:model="target_jt" class="mod-search-input" style="width: 100%;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Target Tembus NSRDI - IU</label>
                    <input type="number" wire:model="target_iu" class="mod-search-input" style="width: 100%;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Target Tembus NSRDI - ID</label>
                    <input type="number" wire:model="target_id" class="mod-search-input" style="width: 100%;">
                </div>
                <button wire:click="updateTargets" class="mod-btn mod-btn-primary">Save Targets</button>
            </div>
        @endif
    </div>
</div>
