<div style="display: flex; flex-direction: column; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed var(--cbm-card-border);">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 0.75rem;">
        <div style="font-weight: 700; color: var(--cbm-text); display: flex; align-items: center; gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
            Advanced Filters
        </div>
        <button wire:click="resetFilters" style="background: transparent; border: none; color: #ef4444; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1rem; height: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            Reset Filters
        </button>
    </div>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <!-- Global Search -->
        <div style="flex: 1; min-width: 250px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: 0.25rem; text-transform: uppercase;">Global Search (Ctrl+F)</label>
            <div style="position: relative; display: flex; align-items: center;">
                <svg style="position: absolute; left: 10px; width: 16px; height: 16px; color: var(--cbm-text-muted);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="text" placeholder="Cari data apapun..." style="padding-left: 32px; width: 100%;">
            </div>
        </div>
        
        <!-- Date Range -->
        <div style="display: flex; gap: 0.5rem;">
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: 0.25rem; text-transform: uppercase;">Date From</label>
                <input wire:model.live="dateStart" type="date" class="mod-search-input">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: 0.25rem; text-transform: uppercase;">Date To</label>
                <input wire:model.live="dateEnd" type="date" class="mod-search-input">
            </div>
        </div>

        <!-- Station -->
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: 0.25rem; text-transform: uppercase;">Station</label>
            <select wire:model.live="filterStation" class="mod-search-input" style="min-width: 120px;">
                <option value="">All Stations</option>
                @foreach(\App\Models\Airport::orderBy('iata_code')->get() as $airport)
                    <option value="{{ $airport->iata_code }}">{{ $airport->iata_code }}</option>
                @endforeach
            </select>
        </div>

        <!-- Sort By -->
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); margin-bottom: 0.25rem; text-transform: uppercase;">Sort By</label>
            <div style="display: flex; gap: 0.25rem;">
                <select wire:model.live="sortField" class="mod-search-input" style="min-width: 140px; border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0;">
                    <option value="date">Date</option>
                    <option value="aircraft_registration">Registration</option>
                    <option value="station">Station</option>
                </select>
                <select wire:model.live="sortDirection" class="mod-search-input" style="border-top-left-radius: 0; border-bottom-left-radius: 0; min-width: 80px;">
                    <option value="desc">Terbaru</option>
                    <option value="asc">Terlama</option>
                </select>
            </div>
        </div>
    </div>
</div>
