<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />
    <style>
        .sr-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(20rem,1fr)); gap:.9rem; margin-bottom:1.25rem; }
        .sr-card { background:var(--cbm-card-bg); border:1px solid var(--cbm-card-border); border-radius:1.1rem; box-shadow:var(--cbm-card-shadow); padding:1rem; display:flex; flex-direction:column; gap:.55rem; }
        .sr-card h3 { margin:0; font-size:1rem; color:var(--cbm-text); }
        .sr-row { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
        .sr-link { flex:1; min-width:10rem; background:var(--cbm-input-bg); color:var(--cbm-text); border:1px solid var(--cbm-input-border); border-radius:.65rem; padding:.45rem .65rem; font-size:.8rem; }
        .sr-h { margin:.25rem 0 .6rem; font-size:.78rem; letter-spacing:.07em; text-transform:uppercase; color:var(--cbm-text-muted); }
    </style>

    @php
        $tones = ['success' => ['#34d399', 'rgba(52,211,153,.15)'], 'failed' => ['#ef4444', 'rgba(239,68,68,.16)'], 'none' => ['#94a3b8', 'rgba(148,163,184,.13)']];
        $every = ['hourly' => 'tiap jam', 'daily' => 'tiap hari'];
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="Sumber Data" subtitle="Semua Google Sheet yang dibaca sistem. Hanya DJA dan AC Movement yang berasal dari planner; sisanya hasil pekerjaan harian dan data acuan." accent="blue" eyebrow="Sistem" />

    <x-flash />

    @foreach($groups as $kind => [$title, $items])
        @if($items->isNotEmpty())
            <div class="sr-h">{{ $title }}</div>
            <div class="sr-grid">
                @foreach($items as $key => $s)
                    @php $status = $s['last_status'] ?? 'none'; $tone = $tones[$status] ?? $tones['none']; @endphp
                    <div class="sr-card" wire:key="src-{{ $key }}">
                        <div class="sr-row" style="justify-content:space-between;">
                            <h3>{{ $s['label'] }}</h3>
                            @if($s['syncable'])
                                <span class="kd-chip" style="color:{{ $tone[0] }};background:{{ $tone[1] }};min-width:0;">{{ $status === 'success' ? 'Berhasil' : ($status === 'failed' ? 'Gagal' : 'Belum pernah') }}</span>
                            @endif
                        </div>
                        <div class="kd-sub">{{ $s['about'] }}</div>

                        @if($s['syncable'])
                            <div class="kd-sub">
                                @if($s['last_synced_at'])Terakhir {{ $s['last_synced_at']->diffForHumans() }}@else Belum pernah disinkronkan @endif
                                @if($s['every']) · otomatis {{ $every[$s['every']] ?? $s['every'] }}@endif
                                @if($s['tab']) · tab {{ $s['tab'] }}@endif
                            </div>
                            @if($s['last_message'])<div class="kd-sub" style="color:{{ $status === 'failed' ? '#ef4444' : 'var(--cbm-text-muted)' }};">{{ $s['last_message'] }}</div>@endif

                            @if($s['spreadsheet_id'])
                                <a class="kd-sub" href="https://docs.google.com/spreadsheets/d/{{ $s['spreadsheet_id'] }}/edit" target="_blank" rel="noopener noreferrer" style="color:var(--cbm-nav-active-t);">Buka sheet ↗</a>
                            @endif

                            @if($canSync)
                                <div class="sr-row">
                                    <input type="text" class="sr-link" wire:model="links.{{ $key }}" placeholder="Ganti link / ID sheet (opsional)" aria-label="Link {{ $s['label'] }}">
                                    <button type="button" class="mod-action-btn" wire:click="save('{{ $key }}')" @disabled(empty($links[$key] ?? ''))>Simpan</button>
                                </div>
                                @error("links.$key")<span class="mod-field-error">{{ $message }}</span>@enderror
                                <div class="sr-row">
                                    <button type="button" class="mod-btn-primary" wire:click="sync('{{ $key }}')" wire:loading.attr="disabled" wire:target="sync('{{ $key }}')">
                                        <span wire:loading.remove wire:target="sync('{{ $key }}')">Sinkron sekarang</span>
                                        <span wire:loading wire:target="sync('{{ $key }}')"><span class="cbm-spinner"></span> Membaca sheet...</span>
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="sr-row"><a href="{{ route($s['route']) }}" wire:navigate class="mod-action-btn">Buka halaman {{ $s['label'] }}</a></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
