<div>
    @php
        $chip = [
            'ok' => ['#34d399', 'rgba(52,211,153,.12)'],
            'yellow' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'red' => ['#ef4444', 'rgba(239,68,68,.16)'],
            'expired' => ['#ef4444', 'rgba(239,68,68,.16)'],
        ];
        $label = fn ($state) => match ($state) { 'yellow' => 'Segera berakhir', 'red' => 'Kritis', 'expired' => 'Kedaluwarsa', default => 'Aman' };
    @endphp

    <x-master.page-header title="Database Karyawan" subtitle="Data karyawan, kontrak, dan paspor beserta penanda masa berlaku." accent="blue" eyebrow="Development & GA" :create-label="$canManage ? 'Tambah Karyawan' : null" />

    <x-flash />

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem;margin-bottom:1rem;">
        <div class="mod-card" style="padding:.9rem 1rem;"><div class="mod-subtitle">Total karyawan</div><div style="font-size:1.6rem;font-weight:700;">{{ $summary['total'] }}</div></div>
        <button type="button" wire:click="$set('expiryFilter','contract')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Kontrak ≤ {{ config('hr.contract.yellow') }} bln</div>
            <div style="font-size:1.4rem;font-weight:700;"><span style="color:#f59e0b;">{{ $summary['contract_yellow'] }}</span> / <span style="color:#ef4444;">{{ $summary['contract_red'] }}</span></div>
            <div class="mod-subtitle">kuning / merah</div>
        </button>
        <div class="mod-card" style="padding:.9rem 1rem;">
            <div class="mod-subtitle">PAS Bandara ≤ 2 bln</div>
            <div style="font-size:1.4rem;font-weight:700;"><span style="color:#f59e0b;">{{ $summary['pas_yellow'] }}</span> / <span style="color:#ef4444;">{{ $summary['pas_red'] }}</span></div>
            <div class="mod-subtitle">kuning / merah</div>
        </div>
        <button type="button" wire:click="$set('expiryFilter','passport')" class="mod-card" style="padding:.9rem 1rem;text-align:left;cursor:pointer;">
            <div class="mod-subtitle">Paspor ≤ {{ config('hr.passport.yellow') }} bln</div>
            <div style="font-size:1.4rem;font-weight:700;"><span style="color:#f59e0b;">{{ $summary['passport_yellow'] }}</span> / <span style="color:#ef4444;">{{ $summary['passport_red'] }}</span></div>
            <div class="mod-subtitle">kuning / merah</div>
        </button>
    </div>

    {{-- One tab per division --}}
    <div class="emp-tabs" role="tablist" aria-label="Divisi">
        <button type="button" role="tab" wire:click="setDivision('')" class="emp-tab {{ $divisionFilter === '' ? 'on' : '' }}">Semua <span>{{ $summary['total'] }}</span></button>
        @foreach($divisions as $d)
            @if(($tabCounts[$d->id] ?? 0) > 0 || (string) $divisionFilter === (string) $d->id)
                <button type="button" role="tab" wire:click="setDivision('{{ $d->id }}')" class="emp-tab {{ (string) $divisionFilter === (string) $d->id ? 'on' : '' }}">{{ $d->name }} <span>{{ $tabCounts[$d->id] ?? 0 }}</span></button>
            @endif
        @endforeach
    </div>
    <style>
        .emp-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.35rem; margin-bottom:.9rem; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .emp-tabs::-webkit-scrollbar { display:none; }
        .emp-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.45rem .95rem; font-size:.85rem; font-weight:600; cursor:pointer; transition:background .15s,color .15s; }
        .emp-tab span { margin-left:.3rem; opacity:.7; font-weight:700; }
        .emp-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
    </style>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari nama / ID / jabatan / station / no. paspor..." aria-label="Cari karyawan">
                </div>

                <div class="cbm-select-wrap">
                    <select wire:model.live="expiryFilter" class="cbm-form-select" aria-label="Filter masa berlaku">
                        <option value="">Semua</option>
                        <option value="any">Kontrak/paspor mendekati habis</option>
                        <option value="contract">Kontrak mendekati habis</option>
                        <option value="passport">Paspor mendekati habis</option>
                    </select>
                </div>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($employees->total()) }} karyawan</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr><th>ID</th><th>NAMA</th><th>DIVISI / JABATAN</th><th>KONTRAK</th><th>PASPOR</th><th>PAS BANDARA</th><th>STATUS</th>@if($canManage)<th style="text-align:right;">AKSI</th>@endif</tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        @php
                            $cs = $e->contractStatus();
                            $ps = $e->passportStatus();
                        @endphp
                        <tr wire:key="emp-{{ $e->id }}">
                            <td>{{ $e->nik }}</td>
                            <td><div class="mod-aircraft-name">{{ $e->name }}</div>@if($e->phone)<div class="mod-subtitle">{{ $e->phone }}</div>@endif</td>
                            <td>{{ $e->division?->name ?? '-' }}@if($e->station) · {{ $e->station }}@endif<div class="mod-subtitle">{{ $e->job_title ?? $e->position?->name ?? '-' }}</div></td>
                            <td>
                                @if($e->contract_type === 'PKWTT')
                                    <span class="mod-subtitle">Tetap (PKWTT)</span>
                                @elseif($e->contract_end)
                                    <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $chip[$cs][0] }};background:{{ $chip[$cs][1] }};" title="{{ $label($cs) }}">
                                        {{ $e->contract_end->format('d M Y') }}
                                    </span>
                                    @if($cs !== 'ok')<div class="mod-subtitle" style="color:{{ $chip[$cs][0] }};">{{ $label($cs) }}</div>@endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($e->passport_expiry)
                                    <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $chip[$ps][0] }};background:{{ $chip[$ps][1] }};" title="{{ $label($ps) }}">
                                        {{ $e->passport_expiry->format('d M Y') }}
                                    </span>
                                    <div class="mod-subtitle" @if($ps !== 'ok') style="color:{{ $chip[$ps][0] }};" @endif>{{ $e->passport_no }}@if($ps !== 'ok') · {{ $label($ps) }}@endif</div>
                                @else
                                    <span class="mod-subtitle">Belum ada</span>
                                @endif
                            </td>
                            <td>
                                @php $pas = $pasByNik[(string) $e->nik] ?? null; $pstate = $pas?->expiryStatus() ?? 'none'; @endphp
                                @if($pas && $pas->due_date)
                                    <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $chip[$pstate][0] ?? '#34d399' }};background:{{ $chip[$pstate][1] ?? 'rgba(52,211,153,.12)' }};">{{ $pas->due_date->format('d M Y') }}</span>
                                    <div class="mod-subtitle" @if($pstate !== 'ok') style="color:{{ $chip[$pstate][0] ?? 'inherit' }};" @endif>{{ $pas->data['codes'] ?? '-' }}@if($label($pstate) !== 'Aman') · {{ $label($pstate) }}@endif</div>
                                @else
                                    <span class="mod-subtitle">Belum ada</span>
                                @endif
                            </td>
                            <td>
                                @if($e->status === 'Aktif')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Nonaktif</span>
                                @endif
                            </td>
                            @if($canManage)
                                <td style="text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                        <button type="button" wire:click="edit({{ $e->id }})" class="mod-action-btn">Edit</button>
                                        <button type="button" wire:click="delete({{ $e->id }})" wire:confirm="Hapus karyawan {{ $e->name }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 8 : 7 }}">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ $search || $expiryFilter || $divisionFilter ? 'Tidak ada data yang cocok' : 'Belum ada karyawan' }}</div>
                                <div class="mod-empty-sub">{{ $canManage ? 'Klik "Tambah Karyawan" untuk mulai.' : 'Data akan tampil setelah diinput admin divisi.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="mod-pagination">{{ $employees->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="$employeeId ? 'Edit Karyawan' : 'Tambah Karyawan'" submit="save" max-width="40rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.75rem 1rem;">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-nik">ID *</label>
                <input id="em-nik" type="text" wire:model="nik" class="cbm-form-input" maxlength="50" autocomplete="off">
                @error('nik') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-name">Nama *</label>
                <input id="em-name" type="text" wire:model="name" class="cbm-form-input" maxlength="255" autocomplete="off">
                @error('name') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-div">Divisi *</label>
                <div class="cbm-select-wrap">
                    <select id="em-div" wire:model="division_id" class="cbm-form-select" @disabled(! $seesAll)>
                        <option value="">Pilih divisi</option>
                        @foreach($divisions as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                @error('division_id') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-pos">Jabatan</label>
                <div class="cbm-select-wrap">
                    <select id="em-pos" wire:model="position_id" class="cbm-form-select">
                        <option value="">-</option>
                        @foreach($positions as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-phone">Telepon</label>
                <input id="em-phone" type="text" wire:model="phone" class="cbm-form-input" maxlength="30">
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-join">Tanggal masuk</label>
                <input id="em-join" type="date" wire:model="join_date" class="cbm-form-input">
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-ctype">Jenis kontrak *</label>
                <div class="cbm-select-wrap">
                    <select id="em-ctype" wire:model.live="contract_type" class="cbm-form-select">
                        <option value="PKWT">PKWT (kontrak)</option>
                        <option value="PKWTT">PKWTT (tetap)</option>
                    </select>
                </div>
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-status">Status *</label>
                <div class="cbm-select-wrap">
                    <select id="em-status" wire:model="status" class="cbm-form-select">
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
            @if($contract_type === 'PKWT')
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="em-cs">Awal kontrak</label>
                    <input id="em-cs" type="date" wire:model="contract_start" class="cbm-form-input">
                </div>
                <div class="cbm-form-group">
                    <label class="cbm-form-label" for="em-ce">Akhir kontrak *</label>
                    <input id="em-ce" type="date" wire:model="contract_end" class="cbm-form-input">
                    @error('contract_end') <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            @endif
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-pn">No. paspor</label>
                <input id="em-pn" type="text" wire:model="passport_no" class="cbm-form-input" maxlength="50" autocomplete="off">
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="em-pe">Paspor berlaku s/d</label>
                <input id="em-pe" type="date" wire:model="passport_expiry" class="cbm-form-input">
                @error('passport_expiry') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="cbm-form-group" style="margin:.75rem 0 0;">
            <label class="cbm-form-label" for="em-notes">Catatan</label>
            <textarea id="em-notes" wire:model="notes" class="cbm-form-input" rows="2" maxlength="1000"></textarea>
        </div>
    </x-master.modal>
</div>
