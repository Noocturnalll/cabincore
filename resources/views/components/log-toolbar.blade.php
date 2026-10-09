@props([
    'logs' => null,               // LengthAwarePaginator (null = filters only)
    'mode' => 'date',             // 'date' = single date filter, 'range' = from/to + station
    'active' => false,            // any filter applied -> show reset button
    'placeholder' => 'Cari registrasi, status...',
])

<div class="mod-toolbar">
    <div class="mod-filters">
        <div class="mod-field mod-field-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="{{ $placeholder }}" aria-label="Cari">
            <button type="button" wire:click="clearSearch" class="mod-clear-btn" aria-label="Hapus pencarian" x-data x-show="$wire.search" x-cloak>&times;</button>
        </div>

        @if($mode === 'range')
            <label class="mod-field mod-field-labelled"><span>Dari</span>
                <input wire:model.live="dateStart" type="date" class="mod-search-input mod-input-plain">
            </label>
            <label class="mod-field mod-field-labelled"><span>Sampai</span>
                <input wire:model.live="dateEnd" type="date" class="mod-search-input mod-input-plain">
            </label>
            <label class="mod-field mod-field-labelled"><span>Station</span>
                <select wire:model.live="filterStation" class="mod-search-input mod-input-plain">
                    <option value="">Semua</option>
                    @foreach(\App\Models\Airport::orderBy('kode')->pluck('kode') as $kode)
                        <option value="{{ $kode }}">{{ $kode }}</option>
                    @endforeach
                </select>
            </label>
        @elseif($mode === 'none')
            {{-- search only --}}
        @else
            <label class="mod-field mod-field-labelled"><span>Tanggal</span>
                <input wire:model.live="dateFilter" type="date" class="mod-search-input mod-input-plain">
            </label>
        @endif

        @if($active)
            <button type="button" wire:click="clearFilters" class="mod-btn-outline mod-btn-sm">Reset filter</button>
        @endif
        <span wire:loading wire:target="search, dateFilter, dateStart, dateEnd, filterStation, perPage, setTab" class="cbm-spinner" aria-label="Memuat"></span>
    </div>

    @if($logs)
    <div class="mod-meta">
        <span class="mod-record-count">
            @if($logs->total() > 0)
                {{ number_format($logs->firstItem()) }}–{{ number_format($logs->lastItem()) }} dari {{ number_format($logs->total()) }} data
            @else
                0 data
            @endif
        </span>
        <label class="mod-perpage"><span>Per halaman</span>
            <select wire:model.live="perPage" class="mod-search-input mod-input-plain">
                @foreach([25, 50, 100, 200] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
            </select>
        </label>
    </div>
    @endif
</div>
