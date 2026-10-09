<div>
    <x-master.page-header :title="$def['label']" :subtitle="$def['subtitle'] ?? null" accent="blue" eyebrow="Data Master" :create-label="$canManage ? 'Tambah' : null" />

    <x-flash />

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.250ms="search" class="mod-search-input" type="search" placeholder="Cari kode atau nama..." aria-label="Cari">
                </div>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ $entries->count() }} data</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>KODE</th><th>NAMA</th>
                        @foreach($def['fields'] as [$label])<th>{{ strtoupper($label) }}</th>@endforeach
                        <th>STATUS</th>
                        @if($canManage)<th style="text-align:right;">AKSI</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                        <tr wire:key="me-{{ $e->id }}" style="{{ $e->is_active ? '' : 'opacity:.55;' }}">
                            <td><strong>{{ $e->code }}</strong></td>
                            <td>{{ $e->label }}</td>
                            @foreach($def['fields'] as $key => $f)<td>{{ $e->attrs[$key] ?? '-' }}</td>@endforeach
                            <td>
                                @if($e->is_active)<span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else<span class="mod-badge-inactive">Nonaktif</span>@endif
                            </td>
                            @if($canManage)
                                <td style="text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap;">
                                        <button type="button" wire:click="edit({{ $e->id }})" class="mod-action-btn">Edit</button>
                                        <button type="button" wire:click="toggle({{ $e->id }})" class="mod-action-btn">{{ $e->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        <button type="button" wire:click="delete({{ $e->id }})" wire:confirm="Hapus {{ $e->code }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 3 + count($def['fields']) + ($canManage ? 1 : 0) }}">
                            <div class="mod-empty">
                                <div class="mod-empty-title">Belum ada data</div>
                                <div class="mod-empty-sub">{{ $canManage ? 'Klik "Tambah" untuk mulai.' : 'Hubungi admin untuk mengisi data master ini.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-master.modal :show="$isOpen" :title="($entryId ? 'Edit ' : 'Tambah ').$def['label']" submit="save" max-width="30rem">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="me-code">Kode *</label>
            <input id="me-code" type="text" wire:model="code" class="cbm-form-input" maxlength="60" autocomplete="off" style="text-transform:uppercase;">
            @error('code') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="me-label">Nama *</label>
            <input id="me-label" type="text" wire:model="label" class="cbm-form-input" maxlength="255" autocomplete="off">
            @error('label') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        @foreach($def['fields'] as $key => $f)
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="me-{{ $key }}">{{ $f[0] }}{{ $f[2] ? ' *' : '' }}</label>
                @if($f[1] === 'select')
                    <div class="cbm-select-wrap">
                        <select id="me-{{ $key }}" wire:model="attrs.{{ $key }}" class="cbm-form-select">
                            <option value="">-</option>
                            @foreach($f[3] as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                        </select>
                    </div>
                @else
                    <input id="me-{{ $key }}" type="{{ $f[1] === 'number' ? 'number' : 'text' }}" step="any" wire:model="attrs.{{ $key }}" class="cbm-form-input">
                @endif
                @error('attrs.'.$key) <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        @endforeach
        <div style="display:flex;gap:1rem;align-items:center;">
            <div class="cbm-form-group" style="margin:0;max-width:8rem;">
                <label class="cbm-form-label" for="me-sort">Urutan</label>
                <input id="me-sort" type="number" wire:model="sort_order" class="cbm-form-input" min="0">
            </div>
            <label style="display:flex;align-items:center;gap:.5rem;margin-top:1.1rem;"><input type="checkbox" wire:model="is_active"> Aktif</label>
        </div>
    </x-master.modal>
</div>
