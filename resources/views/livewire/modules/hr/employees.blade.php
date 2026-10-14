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

    {{-- Tabs per Divisi --}}
    <div style="margin-bottom:.5rem;">
        <div style="font-size:.76rem;font-weight:700;color:var(--cbm-text-muted);margin-bottom:.3rem;letter-spacing:.02em;">
            🏢 FILTER DIVISI:
        </div>
        <div class="emp-tabs" role="tablist" aria-label="Divisi">
            <button type="button" role="tab" wire:click="setDivision('')" class="emp-tab {{ $divisionFilter === '' ? 'on' : '' }}">Semua Divisi <span>{{ $summary['total'] }}</span></button>
            @foreach($divisions as $d)
                @if(($tabCounts[$d->id] ?? 0) > 0 || (string) $divisionFilter === (string) $d->id)
                    <button type="button" role="tab" wire:click="setDivision('{{ $d->id }}')" class="emp-tab {{ (string) $divisionFilter === (string) $d->id ? 'on' : '' }}">{{ $d->name }} <span>{{ $tabCounts[$d->id] ?? 0 }}</span></button>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Tabs per Station --}}
    <div style="margin-bottom:.9rem;">
        <div style="font-size:.76rem;font-weight:700;color:var(--cbm-text-muted);margin-bottom:.3rem;letter-spacing:.02em;">
            📍 FILTER STATION:
        </div>
        <div class="emp-tabs" role="tablist" aria-label="Station">
            <button type="button" role="tab" wire:click="setStation('')" class="emp-tab {{ $stationFilter === '' ? 'on' : '' }}">Semua Station <span>{{ $summary['total'] }}</span></button>
            @foreach($stations as $stn)
                @if(($stationCounts[$stn] ?? 0) > 0 || $stationFilter === $stn)
                    <button type="button" role="tab" wire:click="setStation('{{ $stn }}')" class="emp-tab {{ $stationFilter === $stn ? 'on' : '' }}">{{ $stn }} <span>{{ $stationCounts[$stn] ?? 0 }}</span></button>
                @endif
            @endforeach
        </div>
    </div>
    <style>
        .emp-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.35rem; margin-bottom:.3rem; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .emp-tabs::-webkit-scrollbar { display:none; }
        .emp-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.4rem .85rem; font-size:.82rem; font-weight:600; cursor:pointer; transition:background .15s,color .15s; }
        .emp-tab span { margin-left:.3rem; opacity:.75; font-weight:700; background:rgba(255,255,255,.08); padding:1px 6px; border-radius:99px; font-size:.75rem; }
        .emp-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
    </style>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters" style="flex-wrap:wrap;gap:.5rem;">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari nama / ID / jabatan / station / no. paspor..." aria-label="Cari karyawan">
                </div>

                <div class="cbm-select-wrap">
                    <select wire:model.live="stationFilter" class="cbm-form-select" aria-label="Filter station">
                        <option value="">Semua Station</option>
                        @foreach($stations as $stn)
                            <option value="{{ $stn }}">{{ $stn }} ({{ $stationCounts[$stn] ?? 0 }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="cbm-select-wrap">
                    <select wire:model.live="expiryFilter" class="cbm-form-select" aria-label="Filter masa berlaku">
                        <option value="">Semua Dokumen</option>
                        <option value="any">Kontrak/paspor mendekati habis</option>
                        <option value="contract">Kontrak mendekati habis</option>
                        <option value="passport">Paspor mendekati habis</option>
                    </select>
                </div>

                @if($search || $divisionFilter || $stationFilter || $expiryFilter)
                    <button type="button" wire:click="resetFilters" class="kd-act-btn" style="background:var(--cbm-input-bg);color:var(--cbm-text-muted);border:1px solid var(--cbm-input-border);border-radius:6px;padding:.38rem .7rem;font-size:.78rem;font-weight:600;cursor:pointer;">
                        ✕ Reset
                    </button>
                @endif
            </div>
            <div class="mod-meta" style="display:flex;align-items:center;gap:.75rem;">
                @if($canManage)
                    <button type="button" wire:click="openImport" class="kd-act-btn" title="Import data excel karyawan baru (tidak menimpa data lama)" style="background:#2563eb;color:#fff;border-color:#2563eb;display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .8rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:1rem;height:1rem;" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        Import Excel
                    </button>
                @endif
                <button type="button" wire:click="exportExcel" class="kd-act-btn" title="Unduh data karyawan dengan rekapitulasi sheet Pivot Excel" style="background:#107c41;color:#fff;border-color:#107c41;display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .8rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;">
                    <span>📊</span> Ekspor Excel (Pivot)
                </button>
                <span class="mod-record-count">{{ number_format($employees->total()) }} karyawan</span>
            </div>
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
                            <td>
                                <div>
                                    <strong>{{ $e->station ?: ($e->division?->name ?? '-') }}</strong>
                                    @if($e->directorate) <span style="font-size:.72rem;background:rgba(255,255,255,.08);padding:1px 5px;border-radius:4px;margin-left:3px;font-weight:600;">{{ $e->directorate }}</span>@endif
                                </div>
                                <div class="mod-subtitle" style="font-size:.78rem;">{{ $e->job_title ?? $e->position?->name ?? '-' }}</div>
                                @if($e->supervisor_name)
                                    <div style="font-size:.72rem;color:var(--cbm-text-muted);">Atasan: {{ $e->supervisor_name }}</div>
                                @endif
                                @if($e->join_date)
                                    <div style="font-size:.72rem;color:var(--cbm-text-muted);">TMT: {{ $e->join_date->format('d/m/Y') }}</div>
                                @endif
                            </td>
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

    {{-- Import Modal --}}
    @if($isImportOpen)
        <div class="cbm-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;padding:1rem;">
            <div class="cbm-modal-card" style="background:var(--cbm-card-bg, #1e293b);border:1px solid var(--cbm-border, rgba(255,255,255,0.1));border-radius:1rem;max-width:32rem;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);overflow:hidden;">
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--cbm-border, rgba(255,255,255,0.1));display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h3 style="font-size:1.125rem;font-weight:700;color:var(--cbm-text, #f8fafc);margin:0;">Import Data Karyawan / Kontrak (Excel)</h3>
                        <p style="font-size:0.75rem;color:var(--cbm-text-muted, #94a3b8);margin:0.25rem 0 0;">Mendukung format master database karyawan maupun berkas perpanjangan kontrak (KONTRAK KARYAWAN).</p>
                    </div>
                    <button type="button" wire:click="closeImport" style="background:none;border:none;color:var(--cbm-text-muted, #94a3b8);cursor:pointer;font-size:1.25rem;line-height:1;">&times;</button>
                </div>
                <form wire:submit.prevent="importExcel" style="padding:1.5rem;">
                    <div class="cbm-form-group" style="margin-bottom:1.25rem;">
                        <label class="cbm-form-label" for="emp-file" style="display:block;margin-bottom:0.5rem;font-weight:600;font-size:0.875rem;">Pilih File Excel (.xlsx, .xls, .csv)</label>
                        <input id="emp-file" type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="cbm-form-input" style="width:100%;padding:0.5rem;background:rgba(255,255,255,0.05);border:1px dashed var(--cbm-border, rgba(255,255,255,0.2));border-radius:0.5rem;">
                        <div wire:loading wire:target="importFile" style="font-size:0.75rem;color:#38bdf8;margin-top:0.375rem;">Mengunggah file...</div>
                        @error('importFile') <span class="mod-field-error" style="color:#ef4444;font-size:0.75rem;display:block;margin-top:0.375rem;">{{ $message }}</span> @enderror
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.82rem;color:var(--cbm-text);user-select:none;">
                            <input type="checkbox" wire:model="updateExisting" style="accent-color:#2563eb;width:1.1rem;height:1.1rem;cursor:pointer;">
                            <span><strong>Perbarui data lama jika ID/NIK cocok</strong> (update station, atasan langsung, direktorat, dan TMT)</span>
                        </label>
                    </div>

                    <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.75rem;color:#93c5fd;line-height:1.4;">
                        <strong>Informasi Alur:</strong> Sistem akan mencocokkan ID/NIK karyawan dari file Excel. Jika opsi perbarui dicentang, data karyawan lama akan disegarkan dengan data terbaru dari Excel tanpa menghapus histori kontrak atau paspor yang telah diinput.
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                        <button type="button" wire:click="closeImport" class="cbm-btn cbm-btn-secondary" style="padding:0.5rem 1rem;border-radius:0.5rem;background:rgba(255,255,255,0.1);border:none;color:var(--cbm-text, #fff);cursor:pointer;">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="cbm-btn cbm-btn-primary" style="padding:0.5rem 1.25rem;border-radius:0.5rem;background:#2563eb;border:none;color:#fff;font-weight:600;cursor:pointer;">
                            <span wire:loading.remove wire:target="importExcel">Proses Import</span>
                            <span wire:loading wire:target="importExcel">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
