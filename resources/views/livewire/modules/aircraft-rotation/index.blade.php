<div>
    <style>
        .rot-layout { display: grid; grid-template-columns: minmax(15rem, 19rem) minmax(0, 1fr); gap: 1.25rem; align-items: start; }
        @media (max-width: 960px) { .rot-layout { grid-template-columns: 1fr; } }
        .rot-list { display: flex; flex-direction: column; gap: .625rem; max-height: calc(100vh - 14rem); overflow-y: auto; padding-right: .25rem; }
        @media (max-width: 960px) { .rot-list { max-height: 18rem; } }
        .rot-item { text-align: left; width: 100%; background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-left: 3px solid transparent; border-radius: .875rem; padding: .875rem 1rem; cursor: pointer; font-family: inherit; transition: border-color .2s, background .2s, transform .2s; }
        .rot-item:hover { transform: translateY(-1px); background: var(--cbm-nav-hover); }
        .rot-item.active { border-left-color: var(--cbm-purple, #a855f7); background: var(--cbm-nav-active); }
        .rot-title { font-weight: 700; font-size: .875rem; color: var(--cbm-text); line-height: 1.3; word-break: break-word; }
        .rot-meta { font-size: .72rem; color: var(--cbm-text-muted); margin-top: .25rem; }
        .rot-actions { display: flex; gap: .375rem; margin-top: .625rem; }
        .rot-act { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .3rem; padding: .35rem .5rem; border-radius: .5rem; font-size: .72rem; font-weight: 700; border: 1px solid var(--cbm-card-border); background: transparent; color: var(--cbm-text-muted); cursor: pointer; text-decoration: none; font-family: inherit; }
        .rot-act:hover { color: var(--cbm-text); background: var(--cbm-nav-hover); }
        .rot-act.danger { color: #ef4444; border-color: rgba(239,68,68,.35); }
        .rot-act svg { width: .9rem; height: .9rem; }
        .rot-viewer { padding: 0; overflow: hidden; height: calc(100vh - 14rem); min-height: 28rem; display: flex; flex-direction: column; }
        .rot-viewer-bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .625rem 1rem; border-bottom: 1px solid var(--cbm-divider); background: var(--cbm-nav-hover); }
        .rot-viewer iframe { flex: 1; width: 100%; border: 0; background: #fff; }
    </style>

    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Operasional</div>
            <div class="mod-title">Aircraft Rotation</div>
            <div class="mod-subtitle">Unggah, render, dan pratinjau jadwal rotasi pesawat dari file Excel.</div>
        </div>
    </div>

    <x-flash />
    @if (session()->has('message'))
        <div class="cbm-flash cbm-flash-success" role="status" x-data x-init="setTimeout(() => $el.remove(), 6000)" style="margin-bottom:1rem;">{{ session('message') }}</div>
    @endif
    @if ($errors->any())
        <div class="cbm-flash cbm-flash-error" role="alert" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    {{-- Upload --}}
    <div class="mod-card mod-card-accent-purple" style="margin-bottom:1.25rem;">
        <form action="{{ route('import.aircraft-rotation') }}" method="POST" enctype="multipart/form-data"
              x-data="{ busy: false, name: '' }" @submit="busy = true"
              style="padding:1.25rem 1.5rem;display:flex;gap:1.25rem;align-items:center;flex-wrap:wrap;">
            @csrf
            <div style="flex:1 1 16rem;min-width:0;">
                <h4 style="margin:0;font-size:1rem;color:var(--cbm-text);font-weight:700;">Upload file rotasi baru</h4>
                <p style="margin:.25rem 0 0;font-size:.8125rem;color:var(--cbm-text-muted);">
                    Format .xls / .xlsx, maksimal 50 MB. Setiap sheet dirender ke tab. File besar dapat memakan beberapa menit.
                </p>
            </div>
            <label class="mod-btn-outline" :style="busy ? 'opacity:.6;pointer-events:none;' : 'cursor:pointer;'" style="margin:0;max-width:100%;">
                <span style="display:inline-flex;align-items:center;gap:.4rem;min-width:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    <span x-text="name || 'Pilih file Excel'" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:16rem;"></span>
                </span>
                <input type="file" name="importFile" accept=".xlsx,.xls" required style="display:none;" @change="name = $event.target.files[0]?.name || ''">
            </label>
            <button type="submit" class="mod-btn-primary" :disabled="busy || !name">
                <span x-show="!busy">Upload &amp; Render</span>
                <span x-show="busy" x-cloak style="display:inline-flex;align-items:center;gap:.4rem;"><span class="cbm-spinner"></span> Mengunggah &amp; merender...</span>
            </button>
        </form>
    </div>

    @if($rotations->isNotEmpty())
        <div class="rot-layout" x-data="{
                current: {{ $rotations->first()->id }},
                src(id) { return '{{ url('/rotations') }}/' + id + '/view'; },
                pick(id) { this.current = id; },
                item(id) { return document.querySelector('.rot-item[data-id=\'' + id + '\']'); },
                title() { return this.item(this.current)?.dataset.title || 'Pratinjau'; },
                fallback() { this.$nextTick(() => { if (!this.item(this.current)) { this.current = Number(document.querySelector('.rot-item')?.dataset.id) || null; } }); }
             }"
             @rotation-deleted.window="fallback()">
            <div>
                <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--cbm-text-muted);margin:0 0 .5rem .25rem;">
                    Riwayat upload <span class="cbm-tab-count">{{ $total }}</span>
                </div>
                <div class="rot-list">
                    @foreach($rotations as $rotation)
                        <div class="rot-item" data-id="{{ $rotation->id }}" data-title="{{ $rotation->title }}" :class="{ 'active': current === {{ $rotation->id }} }" wire:key="rot-{{ $rotation->id }}"
                             role="button" tabindex="0" @click="pick({{ $rotation->id }})" @keydown.enter.prevent="pick({{ $rotation->id }})">
                            <div class="rot-title">{{ $rotation->title }}</div>
                            <div class="rot-meta">
                                {{ $rotation->created_at->format('d M Y, H:i') }}
                                @if($rotation->size_bytes) &middot; {{ number_format($rotation->size_bytes / 1048576, 1) }} MB @endif
                            </div>
                            <div class="rot-actions" @click.stop>
                                <a class="rot-act" href="{{ route('rotations.view', $rotation->id) }}" target="_blank" rel="noopener" title="Buka di tab baru">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                    Buka
                                </a>
                                <a class="rot-act" href="{{ route('rotations.download', $rotation->id) }}" title="Unduh file Excel asli">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    Unduh
                                </a>
                                <button type="button" class="rot-act danger" wire:click="deleteRotation({{ $rotation->id }})" wire:confirm="Hapus file rotasi &quot;{{ $rotation->title }}&quot; secara permanen?" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                    @if($total > $rotations->count())
                        <button type="button" class="mod-btn-outline" style="justify-content:center;" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                            <span wire:loading.remove wire:target="loadMore">Muat lebih banyak ({{ $total - $rotations->count() }})</span>
                            <span wire:loading wire:target="loadMore"><span class="cbm-spinner"></span> Memuat...</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="mod-card rot-viewer" wire:ignore.self>
                <div class="rot-viewer-bar">
                    <span style="font-size:.8125rem;font-weight:700;color:var(--cbm-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="title()"></span>
                    <a :href="current ? src(current) : '#'" target="_blank" rel="noopener" class="rot-act" style="flex:none;padding:.3rem .625rem;">Layar penuh</a>
                </div>
                <template x-if="current">
                    <iframe :src="src(current)" :key="current" title="Pratinjau rotasi"></iframe>
                </template>
            </div>
        </div>
    @else
        <div class="mod-card">
            <div class="mod-empty">
                <div class="mod-empty-title">Belum ada data rotasi pesawat</div>
                <div class="mod-empty-sub">Upload file Excel (.xls atau .xlsx) jadwal Aircraft Rotation di atas.</div>
            </div>
        </div>
    @endif
</div>
