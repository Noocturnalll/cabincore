<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />
    <style>
        .as-bar { height:.4rem; border-radius:999px; background:var(--cbm-input-bg); overflow:hidden; min-width:4rem; }
        .as-bar > span { display:block; height:100%; border-radius:999px; background:var(--tone,#34d399); transition:width .3s ease; }
        .as-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(13rem,1fr)); gap:.75rem 1rem; }
        .as-pick { display:block; width:100%; text-align:left; padding:.45rem .65rem; border:0; border-bottom:1px solid var(--cbm-divider); background:transparent; color:var(--cbm-text); cursor:pointer; }
        .as-pick:hover { background:var(--cbm-nav-hover); }
        @media (max-width:640px) { .as-hide-sm { display:none; } }
    </style>

    @php
        $tones = ['green' => '#34d399', 'yellow' => '#f59e0b', 'red' => '#ef4444', 'none' => '#94a3b8', 'blue' => '#60a5fa'];
        $bgs = ['green' => 'rgba(52,211,153,.15)', 'yellow' => 'rgba(245,158,11,.17)', 'red' => 'rgba(239,68,68,.16)', 'none' => 'rgba(148,163,184,.13)', 'blue' => 'rgba(96,165,250,.16)'];
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="Data Asset" subtitle="Asset yang dimiliki dan tersedia: jumlah, kondisi, lokasi, siapa yang memegang, dan riwayat peminjaman." accent="blue" eyebrow="Supporting" :create-label="$canManage ? 'Tambah Asset' : null" />

    <x-flash />

    <div class="kd-cards">
        <div class="kd-card" style="--tone: {{ $tones['blue'] }}"><div class="t">Jenis asset</div><div class="v">{{ number_format($summary['types']) }}</div><div class="s">{{ number_format($summary['units']) }} unit seluruhnya</div></div>
        <div class="kd-card" style="--tone: {{ $tones['green'] }}"><div class="t">Tersedia</div><div class="v">{{ number_format($summary['available']) }}</div><div class="s">bisa dipinjamkan sekarang</div></div>
        <div class="kd-card" style="--tone: {{ $tones['yellow'] }}"><div class="t">Sedang dipakai</div><div class="v">{{ number_format($summary['in_use']) }}</div><div class="s">dipegang karyawan / station</div></div>
        <div class="kd-card" style="--tone: {{ $summary['unfit'] ? $tones['red'] : $tones['none'] }}"><div class="t">Tidak layak pakai</div><div class="v">{{ number_format($summary['unfit']) }}</div><div class="s">rusak, perbaikan, atau hilang</div></div>
    </div>

    @if($byStation->count() > 1)
        <section class="kd-panel" aria-label="Pivot per lokasi">
            <h2><span>Per lokasi</span><span class="kd-sub">jumlah unit</span></h2>
            <div class="kd-wrap">
                <table class="kd-table">
                    <thead><tr><th style="cursor:default;">Lokasi</th><th style="cursor:default;">Jenis</th><th style="cursor:default;">Total</th><th style="cursor:default;">Dipakai</th><th style="cursor:default;">Tersedia</th><th style="cursor:default;">Tidak layak</th></tr></thead>
                    <tbody>
                        @foreach($byStation as $b)
                            <tr wire:key="abs-{{ $b['station'] }}"><td><strong>{{ $b['station'] }}</strong></td><td>{{ $b['types'] }}</td><td>{{ $b['units'] }}</td><td>{{ $b['in_use'] }}</td><td style="color:{{ $b['available'] ? $tones['green'] : $tones['yellow'] }};font-weight:700;">{{ $b['available'] }}</td><td style="{{ $b['unfit'] ? 'color:#ef4444;font-weight:700;' : '' }}">{{ $b['unfit'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="kd-bar" style="position:static;">
        <div class="kd-group" style="flex:1;">
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari nama, kode, atau no. seri" aria-label="Cari asset" style="min-width:12rem;flex:1;">
            <select wire:model.live="category" aria-label="Kategori">
                <option value="">Semua kategori</option>
                @foreach($categories as $code => $c)<option value="{{ $code }}">{{ $c['label'] }}</option>@endforeach
            </select>
            @if($seesAll)
                <select wire:model.live="stationFilter" aria-label="Lokasi">
                    <option value="">Semua lokasi</option>
                    @foreach($stations as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </select>
            @endif
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:var(--cbm-text-muted);"><input type="checkbox" wire:model.live="onlyAvailable"> Hanya yang tersedia</label>
        </div>
    </div>

    <section class="kd-panel">
        <div class="kd-wrap">
            @if($assets->isEmpty())
                <div class="kd-empty">Belum ada asset{{ $search || $category ? ' yang cocok' : '' }}.<br><span class="kd-sub">{{ $canManage ? 'Klik "Tambah Asset" untuk mendata.' : 'Asset akan tampil setelah didata admin.' }}</span></div>
            @else
                <table class="kd-table">
                    <thead>
                        <tr><th style="cursor:default;">Asset</th><th style="cursor:default;" class="as-hide-sm">Kategori</th><th style="cursor:default;">Lokasi</th><th style="cursor:default;">Kondisi</th><th style="cursor:default;">Total</th><th style="cursor:default;">Dipakai</th><th style="cursor:default;">Tersedia</th><th style="cursor:default;"></th></tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $a)
                            @php
                                $used = (int) ($inUse[$a->id] ?? 0);
                                $free = $a->unitsAvailable($used);
                                $ok = in_array($a->condition, $lendable, true);
                                $tone = ! $ok ? 'red' : ($free === 0 ? 'yellow' : 'green');
                                $pct = $a->qty_total ? round(($a->qty_total - $used) / $a->qty_total * 100) : 0;
                            @endphp
                            <tr wire:key="as-{{ $a->id }}">
                                <td><strong>{{ $a->name }}</strong><div class="kd-sub">{{ $a->code }}{{ $a->serial_no ? ' · '.$a->serial_no : '' }}</div></td>
                                <td class="as-hide-sm">{{ $categories[$a->category]['label'] ?? $a->category }}</td>
                                <td>{{ $a->station ?? '-' }}</td>
                                <td><span class="kd-chip" style="color:{{ $tones[$ok ? 'green' : 'red'] }};background:{{ $bgs[$ok ? 'green' : 'red'] }};">{{ $conditions[$a->condition]['label'] ?? $a->condition }}</span></td>
                                <td>{{ $a->qty_total }} <span class="kd-sub">{{ $a->unit }}</span></td>
                                <td>{{ $used }}</td>
                                <td style="min-width:7rem;">
                                    <strong style="color:{{ $tones[$tone] }};">{{ $free }}</strong>
                                    <div class="as-bar" style="--tone:{{ $tones[$tone] }};"><span style="width:{{ $ok ? $pct : 0 }}%"></span></div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:.4rem;flex-wrap:wrap;">
                                        @if($canAssign && $free > 0)<button type="button" wire:click="openLend({{ $a->id }})" class="mod-action-btn">Pinjamkan</button>@endif
                                        <button type="button" wire:click="showHistory({{ $a->id }})" class="mod-action-btn">{{ $historyAssetId === $a->id ? 'Tutup' : 'Riwayat' }}</button>
                                        @if($canManage)
                                            <button type="button" wire:click="edit({{ $a->id }})" class="mod-action-btn">Edit</button>
                                            <button type="button" wire:click="delete({{ $a->id }})" wire:confirm="Hapus {{ $a->name }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($assets->hasPages())<div class="mod-pagination" style="padding:.5rem 1rem;">{{ $assets->links('pagination::tailwind') }}</div>@endif
    </section>

    {{-- History: the many-to-many between this asset and the people / stations that held it --}}
    @if($historyAsset)
        <section class="kd-panel" aria-label="Riwayat peminjaman">
            <h2><span>Riwayat {{ $historyAsset->name }}</span><button type="button" wire:click="showHistory(null)" class="mod-action-btn">Tutup</button></h2>
            <div class="kd-wrap">
                @if($history->isEmpty())
                    <div class="kd-empty">Belum pernah dipinjamkan.</div>
                @else
                    <table class="kd-table">
                        <thead><tr><th style="cursor:default;">Pemegang</th><th style="cursor:default;">Jumlah</th><th style="cursor:default;">Dipinjam</th><th style="cursor:default;">Jatuh tempo</th><th style="cursor:default;">Status</th><th style="cursor:default;"></th></tr></thead>
                        <tbody>
                            @foreach($history as $h)
                                @php $late = $h->isOverdue(); @endphp
                                <tr wire:key="ah-{{ $h->id }}">
                                    <td><strong>{{ $h->holder_name }}</strong><div class="kd-sub">{{ $h->holder_type === 'employee' ? ($h->employee?->nik ?? 'karyawan') : 'station' }}{{ $h->notes ? ' · '.$h->notes : '' }}</div></td>
                                    <td>{{ $h->qty }}</td>
                                    <td>{{ $h->assigned_at->format('d M Y') }}</td>
                                    <td>{{ $h->due_back?->format('d M Y') ?? '-' }}</td>
                                    <td>
                                        @if($h->returned_at)<span class="kd-chip" style="color:{{ $tones['none'] }};background:{{ $bgs['none'] }};">Kembali {{ $h->returned_at->format('d M') }}</span>
                                        @elseif($late)<span class="kd-chip" style="color:{{ $tones['red'] }};background:{{ $bgs['red'] }};">Lewat jatuh tempo</span>
                                        @else<span class="kd-chip" style="color:{{ $tones['yellow'] }};background:{{ $bgs['yellow'] }};">Dipakai</span>@endif
                                    </td>
                                    <td style="text-align:right;">@if(! $h->returned_at && $canAssign)<button type="button" wire:click="giveBack({{ $h->id }})" wire:confirm="Tandai sudah dikembalikan?" class="mod-action-btn">Kembalikan</button>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    @endif

    {{-- Register form --}}
    <x-master.modal :show="$isOpen" :title="$assetId ? 'Edit Asset' : 'Tambah Asset'" submit="save" max-width="40rem">
        <div class="as-grid">
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-code">Kode *</label><input id="as-code" type="text" wire:model="code" class="cbm-form-input" maxlength="40" autocomplete="off">@error('code')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-name">Nama *</label><input id="as-name" type="text" wire:model="name" class="cbm-form-input" maxlength="255" autocomplete="off">@error('name')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-cat">Kategori *</label><div class="cbm-select-wrap"><select id="as-cat" wire:model="asset_category" class="cbm-form-select"><option value="">Pilih kategori</option>@foreach($categories as $code => $c)<option value="{{ $code }}">{{ $c['label'] }}</option>@endforeach</select></div>@error('asset_category')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-cond">Kondisi *</label><div class="cbm-select-wrap"><select id="as-cond" wire:model="condition" class="cbm-form-select">@foreach($conditions as $code => $c)<option value="{{ $code }}">{{ $c['label'] }}</option>@endforeach</select></div>@error('condition')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-qty">Jumlah *</label><input id="as-qty" type="number" min="1" wire:model="qty_total" class="cbm-form-input">@error('qty_total')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-unit">Satuan *</label><input id="as-unit" type="text" wire:model="unit" class="cbm-form-input" maxlength="20"></div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-sta">Lokasi (station)</label><input id="as-sta" type="text" wire:model="station" class="cbm-form-input" maxlength="10" style="text-transform:uppercase;" @disabled(! $seesAll && auth()->user()->station)></div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-div">Divisi pemilik</label><div class="cbm-select-wrap"><select id="as-div" wire:model="division_id" class="cbm-form-select" @disabled(! $seesAll && auth()->user()->division_id)><option value="">-</option>@foreach($divisions as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>@error('division_id')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-sn">No. seri</label><input id="as-sn" type="text" wire:model="serial_no" class="cbm-form-input" maxlength="60"></div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="as-acq">Tanggal perolehan</label><input id="as-acq" type="date" wire:model="acquired_at" class="cbm-form-input"></div>
        </div>
        <div class="cbm-form-group" style="margin:.75rem 0 0;"><label class="cbm-form-label" for="as-notes">Catatan</label><textarea id="as-notes" wire:model="notes" class="cbm-form-input" rows="2" maxlength="1000"></textarea></div>
    </x-master.modal>

    {{-- Lending form --}}
    <x-master.modal :show="$lendOpen" :title="'Pinjamkan '.($lendAsset?->name ?? '')" submit="lend" max-width="30rem" submit-label="Pinjamkan" close="closeLend">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="ln-type">Dipegang oleh</label>
            <div class="cbm-select-wrap"><select id="ln-type" wire:model.live="holderType" class="cbm-form-select"><option value="employee">Karyawan</option><option value="station">Station / tim</option></select></div>
        </div>
        @if($holderType === 'employee')
            <div class="cbm-form-group" style="position:relative;">
                <label class="cbm-form-label" for="ln-emp">Karyawan *</label>
                <input id="ln-emp" type="search" wire:model.live.debounce.250ms="employeeSearch" wire:keydown="$set('employeeId', null)" class="cbm-form-input" placeholder="Ketik nama atau ID" autocomplete="off">
                @if($employees->isNotEmpty())
                    <div style="border:1px solid var(--cbm-input-border);border-radius:.65rem;margin-top:.25rem;overflow:hidden;background:var(--cbm-input-bg);">
                        @foreach($employees as $e)<button type="button" class="as-pick" wire:click="chooseEmployee({{ $e->id }})">{{ $e->name }} <span class="kd-sub">{{ $e->nik }} · {{ $e->station }}</span></button>@endforeach
                    </div>
                @endif
                @error('employeeId')<span class="mod-field-error">{{ $message }}</span>@enderror
            </div>
        @else
            <div class="cbm-form-group"><label class="cbm-form-label" for="ln-sta">Station *</label><input id="ln-sta" type="text" wire:model="holderStation" class="cbm-form-input" maxlength="10" style="text-transform:uppercase;">@error('holderStation')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
        @endif
        <div class="as-grid">
            <div class="cbm-form-group"><label class="cbm-form-label" for="ln-qty">Jumlah</label><input id="ln-qty" type="number" min="1" wire:model="lendQty" class="cbm-form-input">@error('lendQty')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="ln-due">Dikembalikan paling lambat</label><input id="ln-due" type="date" wire:model="dueBack" class="cbm-form-input">@error('dueBack')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
        </div>
        <div class="cbm-form-group" style="margin:.5rem 0 0;"><label class="cbm-form-label" for="ln-notes">Catatan</label><input id="ln-notes" type="text" wire:model="lendNotes" class="cbm-form-input" maxlength="255"></div>
    </x-master.modal>
</div>
