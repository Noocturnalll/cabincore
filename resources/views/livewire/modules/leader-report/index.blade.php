<div>
    @php
        $tone = [
            'closed_planned' => ['#34d399', 'rgba(52,211,153,.14)'],
            'updated_planned' => ['#38bdf8', 'rgba(56,189,248,.14)'],
            'unplanned_new' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'unplanned_update' => ['#f59e0b', 'rgba(245,158,11,.16)'],
            'cml_new' => ['#a78bfa', 'rgba(167,139,250,.16)'],
            'cml_update' => ['#a78bfa', 'rgba(167,139,250,.16)'],
            'skipped' => ['#94a3b8', 'rgba(148,163,184,.16)'],
            'line_maintenance' => ['#fb7185', 'rgba(251,113,133,.14)'],
            'pending' => ['#94a3b8', 'rgba(148,163,184,.16)'],
        ];
    @endphp

    <x-master.page-header title="Laporan Leader" subtitle="Import laporan harian leader (Excel berisi task, man power, jam mulai dan selesai). Dokumen yang ada di DJA otomatis Closed; yang tidak ada masuk Unplanned." accent="blue" eyebrow="Cabin Maintenance" />

    <x-flash />

    <div class="mod-card mod-card-accent-blue" style="margin-bottom:1rem;">
        <form wire:submit="readFile" style="padding:1rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
            <div class="cbm-form-group" style="margin:0;">
                <label class="cbm-form-label" for="lr-date">Tanggal laporan *</label>
                <input id="lr-date" type="date" wire:model="reportDate" class="cbm-form-input">
                @error('reportDate') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group" style="margin:0;flex:1;min-width:16rem;">
                <label class="cbm-form-label" for="lr-file">File Excel leader *</label>
                <input id="lr-file" type="file" wire:model="file" accept=".xlsx,.xls,.xlsm,.csv" class="cbm-form-input">
                @error('file') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="mod-btn-primary" wire:loading.attr="disabled" wire:target="readFile,file" @disabled(! $file)>
                <span wire:loading.remove wire:target="readFile">Baca &amp; Pratinjau</span>
                <span wire:loading wire:target="readFile"><span class="cbm-spinner"></span> Membaca...</span>
            </button>
        </form>
        <div class="mod-subtitle" style="padding:0 1rem 1rem;">
            Tanggal laporan dipakai bila baris tidak punya kolom tanggal. Kolom dikenali dari nama header: nomor dokumen (WO / DMI NO / NSRDI / NO DOC), AC REG, STATUS, MP / MAN POWER, START / MULAI, END / FINISH / SELESAI.
        </div>
    </div>

    @if($import)
        <div class="mod-card" style="margin-bottom:1rem;">
            <div style="padding:1rem;display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center;">
                <div>
                    <strong>{{ $import->file_name }}</strong>
                    <div class="mod-subtitle">Tanggal {{ $import->report_date->format('d M Y') }} · {{ $import->status === 'applied' ? 'Sudah diterapkan '.$import->applied_at?->format('d M Y H:i') : 'Pratinjau (belum diterapkan)' }}</div>
                </div>
                @if($import->status === 'preview')
                    <div style="display:flex;gap:.5rem;">
                        <button type="button" wire:click="discard" wire:confirm="Buang pratinjau ini?" class="mod-btn-outline">Buang</button>
                        <button type="button" wire:click="apply" wire:confirm="Terapkan laporan ini? DJA yang cocok akan ditutup dan data unplanned dibuat." class="mod-btn-primary" wire:loading.attr="disabled" wire:target="apply">
                            <span wire:loading.remove wire:target="apply">Terapkan</span>
                            <span wire:loading wire:target="apply"><span class="cbm-spinner"></span> Menerapkan...</span>
                        </button>
                    </div>
                @endif
            </div>
            <div style="padding:0 1rem 1rem;display:flex;flex-wrap:wrap;gap:.5rem;">
                <button type="button" wire:click="$set('outcomeFilter','')" class="cbm-tab {{ $outcomeFilter === '' ? 'active' : '' }}">Semua ({{ $stats['total'] ?? 0 }})</button>
                @if($needCheck > 0)
                    <button type="button" wire:click="$set('outcomeFilter','check')" class="cbm-tab {{ $outcomeFilter === 'check' ? 'active' : '' }}" style="color:#f59e0b;">Perlu dicek ({{ $needCheck }})</button>
                @endif
                @foreach($outcomes as $key => $label)
                    @if(($stats[$key] ?? 0) > 0)
                        <button type="button" wire:click="$set('outcomeFilter','{{ $key }}')" class="cbm-tab {{ $outcomeFilter === $key ? 'active' : '' }}">{{ $label }} ({{ $stats[$key] }})</button>
                    @endif
                @endforeach
            </div>
            @if(!empty($stats['unplanned_sheet_written']))
                <div class="mod-subtitle" style="padding:0 1rem 1rem;">{{ $stats['unplanned_sheet_written'] }} baris unplanned ditulis ke tab UNPLANNED WO / DMI / NSRDI di Google Sheet.</div>
            @endif
            @if(!empty($stats['unplanned_sheet_failed']))
                <div class="mod-subtitle" style="padding:0 1rem 1rem;color:#f59e0b;">{{ $stats['unplanned_sheet_failed'] }} baris unplanned gagal ditulis ke Google Sheet (tab tidak ditemukan atau tidak ada akses). Data tetap tersimpan di CBM.</div>
            @endif
            @if(!empty($stats['sheet_failed']))
                <div class="mod-subtitle" style="padding:0 1rem 1rem;color:#f59e0b;">{{ $stats['sheet_failed'] }} baris Closed gagal ditulis ke Google Sheet DJA (di CBM sudah Closed).</div>
            @endif
        </div>

        <div class="mod-card">
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead><tr><th>JENIS</th><th>NO. DOKUMEN</th><th>REG</th><th>STA</th><th>STATUS</th><th style="text-align:right;">MP</th><th>MULAI – SELESAI</th><th style="text-align:right;">MAN HOUR</th><th>HASIL</th></tr></thead>
                    <tbody>
                        @forelse($rows as $r)
                            @php $t = $tone[$r->outcome] ?? $tone['pending']; @endphp
                            <tr wire:key="lr-{{ $r->id }}">
                                <td>{{ $r->doc_type ?? '-' }}<div class="mod-subtitle">{{ $r->sheet }}</div></td>
                                <td>{{ $r->doc_no }}</td>
                                <td>{{ $r->aircraft_registration }}</td>
                                <td>{{ $r->station ?? '-' }}</td>
                                <td>{{ $r->status ?? '-' }}</td>
                                <td style="text-align:right;">{{ $r->man_power ?? '-' }}</td>
                                <td style="white-space:nowrap;">{{ $r->start_at ? $r->start_at->format('H:i') : '-' }} – {{ $r->end_at ? $r->end_at->format('H:i') : '-' }}</td>
                                <td style="text-align:right;">{{ $r->man_hour !== null ? number_format($r->man_hour, 2) : '-' }}</td>
                                <td>
                                    <span style="display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;color:{{ $t[0] }};background:{{ $t[1] }};">{{ $outcomes[$r->outcome] ?? $r->outcome }}</span>
                                    @if($r->note)<div class="mod-subtitle">{{ $r->note }}</div>@endif
                                    @if($r->category && in_array($r->doc_type, ['WO', 'DMI', 'NSRDI'], true))
                                        <div style="margin-top:.3rem;display:flex;align-items:center;gap:.4rem;">
                                            @if($import->status === 'preview')
                                                <select wire:change="setCategory({{ $r->id }}, $event.target.value)" class="cbm-form-select" style="width:auto;padding:.15rem 1.5rem .15rem .5rem;font-size:.75rem;" aria-label="Kategori dokumen">
                                                    <option value="CBM" @selected($r->category === 'CBM')>CBM</option>
                                                    @if($r->doc_type === 'NSRDI')
                                                        <option value="PAINTING" @selected($r->category === 'PAINTING')>PAINTING</option>
                                                    @else
                                                        <option value="LINE" @selected($r->category === 'LINE')>Line Maintenance</option>
                                                    @endif
                                                </select>
                                            @else
                                                <span class="mod-subtitle">{{ $r->category }}</span>
                                            @endif
                                            @if($r->category_check)<span style="font-size:.7rem;font-weight:600;color:#f59e0b;">perlu dicek</span>@endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><div class="mod-empty"><div class="mod-empty-title">Tidak ada baris</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())<div class="mod-pagination">{{ $rows->links('pagination::tailwind') }}</div>@endif
        </div>
    @endif

    @if($history->isNotEmpty())
        <div class="mod-card" style="margin-top:1rem;">
            <div style="padding:.75rem 1rem;font-weight:600;">Riwayat import</div>
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead><tr><th>FILE</th><th>TANGGAL</th><th>OLEH</th><th>STATUS</th><th style="text-align:right;">BARIS</th><th></th></tr></thead>
                    <tbody>
                        @foreach($history as $h)
                            <tr wire:key="lh-{{ $h->id }}">
                                <td>{{ $h->file_name }}</td>
                                <td>{{ $h->report_date->format('d M Y') }}</td>
                                <td>{{ $h->user?->name ?? '-' }}</td>
                                <td>{{ $h->status === 'applied' ? 'Diterapkan' : 'Pratinjau' }}</td>
                                <td style="text-align:right;">{{ $h->stats['total'] ?? 0 }}</td>
                                <td style="text-align:right;"><button type="button" wire:click="open({{ $h->id }})" class="mod-action-btn">Lihat</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
