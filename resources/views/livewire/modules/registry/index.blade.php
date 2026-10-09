<div>
    @php
        $chip = [
            'ok' => ['#34d399', 'rgba(52,211,153,.12)'],
            'yellow' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'red' => ['#ef4444', 'rgba(239,68,68,.16)'],
            'expired' => ['#ef4444', 'rgba(239,68,68,.16)'],
        ];
        $label = fn ($state) => match ($state) { 'yellow' => 'Segera berakhir', 'red' => 'Kritis', 'expired' => 'Kedaluwarsa', default => '' };
        $fields = $cfg['fields'];
        $cols = $cfg['columns'];
        $colCount = count($cols) + 1 + ($hasDue ? 1 : 0) + ($canManage ? 1 : 0);
        $fmt = function ($key, $value) use ($fields) {
            if ($value === null || $value === '') return '-';
            if (($fields[$key][1] ?? '') === 'date') { try { return \Carbon\Carbon::parse($value)->format('d M Y'); } catch (\Throwable $e) { return $value; } }
            return $value;
        };
    @endphp

    <x-master.page-header :title="$cfg['label']" :subtitle="$cfg['subtitle']" accent="blue" :eyebrow="$groupLabel" :create-label="$canManage ? 'Tambah Data' : null" />

    <x-flash />

    @if($summary)
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem;margin-bottom:1rem;">
            <button type="button" wire:click="$toggle('onlyDue')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
                <div class="mod-subtitle">Mendekati habis ({{ $cfg['yellow'] }} bln)</div>
                <div style="font-size:1.4rem;font-weight:700;"><span style="color:#f59e0b;">{{ $summary['yellow'] }}</span> kuning · <span style="color:#ef4444;">{{ $summary['red'] }}</span> merah</div>
                <div class="mod-subtitle">{{ $onlyDue ? 'Klik untuk tampilkan semua' : 'Klik untuk filter' }}</div>
            </button>
        </div>
    @endif

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari..." aria-label="Cari">
                </div>
                @if($seesAll)
                    <div class="cbm-select-wrap">
                        <select wire:model.live="divisionFilter" class="cbm-form-select" aria-label="Filter divisi">
                            <option value="">Semua divisi</option>
                            @foreach($divisions as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                @endif
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($records->total()) }} data</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>DIVISI</th>
                        @foreach($cols as $c)<th>{{ strtoupper($fields[$c][0]) }}</th>@endforeach
                        @if($hasDue)<th>{{ strtoupper($fields[$cfg['due']][0]) }}</th>@endif
                        @if($canManage)<th style="text-align:right;">AKSI</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                        @php $state = $r->expiryStatus(); @endphp
                        <tr wire:key="rec-{{ $r->id }}">
                            <td>{{ $r->division?->name ?? '-' }}</td>
                            @foreach($cols as $c)
                                <td>{{ $fmt($c, $r->data[$c] ?? null) }}</td>
                            @endforeach
                            @if($hasDue)
                                <td>
                                    @if($r->due_date)
                                        <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $chip[$state][0] }};background:{{ $chip[$state][1] }};">{{ $r->due_date->format('d M Y') }}</span>
                                        @if($label($state))<div class="mod-subtitle" style="color:{{ $chip[$state][0] }};">{{ $label($state) }}</div>@endif
                                    @else
                                        <span class="mod-subtitle">-</span>
                                    @endif
                                </td>
                            @endif
                            @if($canManage)
                                <td style="text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                        <button type="button" wire:click="edit({{ $r->id }})" class="mod-action-btn">Edit</button>
                                        <button type="button" wire:click="delete({{ $r->id }})" wire:confirm="Hapus data ini?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $colCount }}">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ $search || $onlyDue || $divisionFilter ? 'Tidak ada data yang cocok' : 'Belum ada data' }}</div>
                                <div class="mod-empty-sub">{{ $canManage ? 'Klik "Tambah Data" untuk mulai.' : 'Data akan tampil setelah diinput.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="mod-pagination">{{ $records->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="($recordId ? 'Edit ' : 'Tambah ').$cfg['label']" submit="save" max-width="40rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.75rem 1rem;">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="rg-div">Divisi *</label>
                <div class="cbm-select-wrap">
                    <select id="rg-div" wire:model="division_id" class="cbm-form-select" @disabled(! $seesAll)>
                        <option value="">Pilih divisi</option>
                        @foreach($divisions as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                @error('division_id') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            @foreach($fields as $key => $def)
                @if($def[1] !== 'textarea')
                    <div class="cbm-form-group">
                        <label class="cbm-form-label" for="rg-{{ $key }}">{{ $def[0] }}{{ $def[2] ? ' *' : '' }}</label>
                        @if($def[1] === 'select')
                            <div class="cbm-select-wrap">
                                <select id="rg-{{ $key }}" wire:model="form.{{ $key }}" class="cbm-form-select">
                                    <option value="">-</option>
                                    @foreach($def[3] as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                                </select>
                            </div>
                        @else
                            <input id="rg-{{ $key }}" type="{{ $def[1] === 'number' ? 'number' : ($def[1] === 'date' ? 'date' : 'text') }}" wire:model="form.{{ $key }}" class="cbm-form-input" @if($def[1] === 'text') maxlength="255" autocomplete="off" @endif>
                        @endif
                        @error('form.'.$key) <span class="mod-field-error">{{ $message }}</span> @enderror
                    </div>
                @endif
            @endforeach
        </div>
        @foreach($fields as $key => $def)
            @if($def[1] === 'textarea')
                <div class="cbm-form-group" style="margin:.75rem 0 0;">
                    <label class="cbm-form-label" for="rg-{{ $key }}">{{ $def[0] }}{{ $def[2] ? ' *' : '' }}</label>
                    <textarea id="rg-{{ $key }}" wire:model="form.{{ $key }}" class="cbm-form-input" rows="3" maxlength="2000"></textarea>
                    @error('form.'.$key) <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            @endif
        @endforeach
    </x-master.modal>
</div>
