<div>
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Planner</div>
            <div class="mod-title">Daily Job Assignment</div>
            <div class="mod-subtitle">Tugas terencana dari Google Sheets Planner yang menjadi dasar WO, DMI, NSRDI, dan CML.</div>
        </div>
        <div class="mod-actions">
            @if($activeTab === 'data')
                <button type="button" wire:click="exportExcel" class="mod-btn-outline" wire:loading.attr="disabled" wire:target="exportExcel">
                    <span wire:loading.remove wire:target="exportExcel">
                        <span style="display:inline-flex;align-items:center;gap:.4rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Export Excel
                        </span>
                    </span>
                    <span wire:loading wire:target="exportExcel"><span class="cbm-spinner"></span> Mengexport...</span>
                </button>
            @endif
        </div>
    </div>

    <div class="cbm-seg" role="tablist" aria-label="Tampilan DJA">
        <button type="button" role="tab" wire:click="setTab('data')" class="cbm-seg-btn {{ $activeTab === 'data' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'data' ? 'true' : 'false' }}">Data DJA</button>
        @if($canReview)
            <button type="button" role="tab" wire:click="setTab('review')" class="cbm-seg-btn {{ $activeTab === 'review' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'review' ? 'true' : 'false' }}">
                Review Sync @if($reviewCounts['review'] > 0)<span class="cbm-tab-count" style="background:#f59e0b;color:#fff;border-color:transparent;">{{ $reviewCounts['review'] }}</span>@endif
            </button>
        @endif
        <button type="button" role="tab" wire:click="setTab('sync')" class="cbm-seg-btn {{ $activeTab === 'sync' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'sync' ? 'true' : 'false' }}">Import &amp; Sinkronisasi</button>
    </div>

    <x-flash />

    @if($syncSetting->spreadsheet_id)
        @if($freshness['failed'])
            <div class="cbm-flash cbm-flash-error" role="alert" style="margin-bottom:1rem;">
                <strong>Sync terakhir gagal</strong> ({{ $freshness['minutes'] !== null ? $freshness['minutes'].' menit lalu' : 'belum pernah berhasil' }}): {{ $syncSetting->last_message }}
            </div>
        @elseif($freshness['stale'])
            <div class="mod-hint mod-hint-warn" role="alert">
                Data DJA belum diperbarui {{ $freshness['minutes'] !== null ? $freshness['minutes'].' menit' : 'sama sekali' }}. Sync otomatis seharusnya berjalan setiap 15 menit.
                Pastikan scheduler server aktif (<code>php artisan schedule:run</code> tiap menit), atau jalankan sinkronisasi manual di tab Import &amp; Sinkronisasi.
            </div>
        @endif
    @endif

    @if($activeTab === 'data')
        {{-- Summary --}}
        <div class="mod-stats">
            <div class="mod-stat"><div class="mod-stat-label">Total tugas</div><div class="mod-stat-value">{{ number_format($stats['total']) }}</div><div class="mod-stat-sub">{{ ($search || $dateFilter) ? 'sesuai filter' : 'semua tanggal' }}</div></div>
            <div class="mod-stat"><div class="mod-stat-label">Pesawat</div><div class="mod-stat-value">{{ number_format($stats['aircraft']) }}</div><div class="mod-stat-sub">registrasi unik</div></div>
            <div class="mod-stat"><div class="mod-stat-label">Station</div><div class="mod-stat-value">{{ number_format($stats['stations']) }}</div><div class="mod-stat-sub">station terlibat</div></div>
            <div class="mod-stat"><div class="mod-stat-label">Tanggal terbaru</div><div class="mod-stat-value">{{ $stats['latest'] ? \Carbon\Carbon::parse($stats['latest'])->format('d M Y') : '-' }}</div><div class="mod-stat-sub">data DJA terakhir</div></div>
        </div>

        <div class="mod-card mod-card-accent-blue">
            <x-log-toolbar :logs="$rows" mode="date" :active="(bool) ($search || $dateFilter)" placeholder="Cari registrasi, task ID, station..." />

            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>REG A/C</th>
                            <th>TASK ID</th>
                            <th>JOB TYPE</th>
                            <th>STATION</th>
                            <th>DESKRIPSI</th>
                            <th>LAPORAN TERKAIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $linked = [
                                    'WO' => $row->wo_logs_count, 'DMI' => $row->dmi_logs_count,
                                    'NSRDI' => $row->nsrdi_logs_count, 'CML' => $row->cml_logs_count,
                                ];
                            @endphp
                            <tr wire:key="dja-{{ $row->id }}">
                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                <td><div class="mod-aircraft-name">{{ $row->aircraft_registration }}</div></td>
                                <td>{{ $row->task_id }}</td>
                                <td>{{ $row->job_type }}</td>
                                <td>{{ $row->station ?: '-' }}</td>
                                <td><x-text-popup :text="$row->description ?: '-'" title="Deskripsi Tugas" /></td>
                                <td>
                                    @if(array_sum($linked) === 0)
                                        <span class="mod-badge-inactive">Belum ada</span>
                                    @else
                                        <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
                                            @foreach($linked as $label => $n)
                                                @if($n > 0)<span class="mod-badge-closed">{{ $label }} {{ $n }}</span>@endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="mod-empty">
                                        <div class="mod-empty-title">{{ ($search || $dateFilter) ? 'Tidak ada data yang cocok' : 'Belum ada data DJA' }}</div>
                                        <div class="mod-empty-sub">
                                            @if($search || $dateFilter)
                                                Ubah kata kunci, tanggal, atau reset filter.
                                            @else
                                                Buka tab <a href="#" wire:click.prevent="setTab('sync')" style="color:var(--cbm-blue);font-weight:700;">Import &amp; Sinkronisasi</a> untuk memuat data DJA.
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())
                <div class="mod-pagination">{{ $rows->links('pagination::tailwind') }}</div>
            @endif
        </div>
    @elseif($activeTab === 'review')
        <div class="mod-hint" style="background:var(--cbm-nav-hover);color:var(--cbm-text-muted);border-color:var(--cbm-card-border);">
            Baris dari sheet planner yang <strong>tidak diterima otomatis</strong> ditampung di sini, tidak dibuang diam-diam.
            <strong>Terima</strong> memasukkannya ke DJA dan modul WO / DMI / NSRDI; <strong>Tolak</strong> menyembunyikannya.
            Keputusan Anda diingat pada sync berikutnya.
        </div>

        <div class="mod-card mod-card-accent-blue">
            <div class="cbm-tabs">
                @foreach(['review' => 'Perlu review', 'reject' => 'Ditolak aturan', 'decided' => 'Sudah diputuskan'] as $value => $label)
                    <button type="button" wire:click="$set('reviewFilter', '{{ $value }}')" class="cbm-tab {{ $reviewFilter === $value ? 'active' : '' }}">
                        {{ $label }} <span class="cbm-tab-count">{{ number_format($reviewCounts[$value]) }}</span>
                    </button>
                @endforeach
            </div>

            <x-log-toolbar :logs="$reviews" mode="none" :active="(bool) $search" placeholder="Cari task, registrasi, deskripsi, alasan..." />

            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr><th>JENIS</th><th>TASK ID</th><th>REG A/C</th><th>DESKRIPSI</th><th>ATA / KATEGORI</th><th>ALASAN SISTEM</th><th>TERAKHIR TERLIHAT</th><th style="text-align:right;">AKSI</th></tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $r)
                            <tr wire:key="review-{{ $r->id }}">
                                <td><span class="mod-badge-progress" style="text-transform:uppercase;">{{ $r->kind }}</span></td>
                                <td>{{ $r->task_id ?: '-' }}</td>
                                <td><div class="mod-aircraft-name">{{ $r->aircraft_registration ?: '-' }}</div></td>
                                <td style="max-width:22rem;white-space:normal;"><x-text-popup :text="$r->description ?: '-'" title="Deskripsi" /></td>
                                <td>{{ $r->ata ?: '-' }}@if($r->category) <span class="mod-aircraft-sub">{{ $r->category }}</span>@endif</td>
                                <td style="max-width:18rem;white-space:normal;">
                                    <div style="font-size:.8125rem;">{{ $r->reason }}</div>
                                    <div class="mod-aircraft-sub">{{ $r->rule }}</div>
                                </td>
                                <td style="white-space:nowrap;">{{ $r->last_seen_at?->diffForHumans() }}</td>
                                <td style="text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                        @if($r->decision)
                                            <span class="{{ $r->decision === 'accepted' ? 'mod-badge-closed' : 'mod-badge-open' }}">{{ $r->decision === 'accepted' ? 'Diterima' : 'Ditolak' }}@if($r->decider) &middot; {{ $r->decider->name }}@endif</span>
                                            <button type="button" wire:click="resetReview({{ $r->id }})" class="mod-action-btn">Batalkan</button>
                                        @else
                                            <button type="button" wire:click="acceptReview({{ $r->id }})" wire:loading.attr="disabled" class="mod-action-btn" style="color:#10b981;border-color:rgba(16,185,129,.4);">Terima</button>
                                            <button type="button" wire:click="rejectReview({{ $r->id }})" wire:loading.attr="disabled" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Tolak</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">
                                <div class="mod-empty">
                                    <div class="mod-empty-title">{{ $reviewFilter === 'review' ? 'Tidak ada baris yang perlu review' : 'Tidak ada data' }}</div>
                                    <div class="mod-empty-sub">Baris baru muncul di sini setelah sync berikutnya.</div>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reviews->hasPages())
                <div class="mod-pagination">{{ $reviews->links('pagination::tailwind') }}</div>
            @endif
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,24rem),1fr));gap:1.25rem;align-items:start;">
            {{-- Import Excel --}}
            <div class="mod-card mod-card-accent-blue" style="padding:1.5rem;">
                <h3 style="font-size:1rem;font-weight:800;color:var(--cbm-text);margin-bottom:.25rem;">Import dari Excel</h3>
                <p style="font-size:.8125rem;color:var(--cbm-text-muted);margin-bottom:1.25rem;line-height:1.6;">
                    Gunakan saat sinkronisasi Google Sheets belum tersedia. Header kolom: <code>date, aircraft_registration, task_id, description, station</code>.
                    Import bersifat satu paket: jika ada baris yang gagal, tidak ada data yang tersimpan.
                </p>
                <form wire:submit="import">
                    <div class="cbm-upload-zone">
                        <input type="file" wire:model="file" accept=".xlsx,.xls,.csv">
                        <div class="cbm-upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                        <div class="cbm-upload-title">{{ $file ? $file->getClientOriginalName() : 'Klik untuk pilih file' }}</div>
                        <div class="cbm-upload-sub">atau drag &amp; drop ke sini (maks. 10 MB)</div>
                        <div class="cbm-upload-badge"><span>.xlsx</span><span>.xls</span><span>.csv</span></div>
                        <div wire:loading wire:target="file" style="margin-top:.75rem;font-size:.8125rem;color:var(--cbm-text-muted);">Mengunggah file...</div>
                    </div>
                    @error('file') <span class="mod-field-error">{{ $message }}</span> @enderror
                    <button type="submit" class="mod-btn-primary" style="width:100%;justify-content:center;margin-top:1rem;" wire:loading.attr="disabled" wire:target="file, import" @disabled(! $file)>
                        <span wire:loading.remove wire:target="import">Import Data</span>
                        <span wire:loading wire:target="import"><span class="cbm-spinner"></span> Mengimport...</span>
                    </button>
                </form>
            </div>

            {{-- Google Sheets --}}
            <div class="mod-card mod-card-accent-green" style="padding:1.5rem;">
                <h3 style="font-size:1rem;font-weight:800;color:var(--cbm-text);margin-bottom:.25rem;">Google Sheets Sync</h3>
                <p style="font-size:.8125rem;color:var(--cbm-text-muted);margin-bottom:1.25rem;line-height:1.6;">
                    Tempel link sheet DJA hari ini lalu sinkronkan. Setelah itu sistem melakukan auto-sync berkala memakai link yang sama.
                </p>
                <form wire:submit="syncNow" style="display:flex;flex-direction:column;gap:.625rem;">
                    <input type="text" wire:model="sheetUrl" placeholder="https://docs.google.com/spreadsheets/d/..." class="mod-search-input mod-input-plain" style="width:100%;text-align:left;" aria-label="Link Google Sheet">
                    @error('sheetUrl') <span class="mod-field-error">{{ $message }}</span> @enderror
                    <button type="submit" class="mod-btn-primary" style="width:100%;justify-content:center;" wire:loading.attr="disabled" wire:target="syncNow">
                        <span wire:loading.remove wire:target="syncNow">Mulai Sinkronisasi</span>
                        <span wire:loading wire:target="syncNow"><span class="cbm-spinner"></span> Menyinkronkan...</span>
                    </button>
                </form>

                @include('livewire.modules.partials.sync-status', [
                    'syncSetting' => $syncSetting,
                    'hint' => 'Sheet DJA berganti tiap hari: tempel link sheet hari ini lalu klik sinkronisasi. Setelah itu sistem auto-sync tiap 15 menit.',
                ])
            </div>
        </div>
    @endif
</div>
