<div>
    <style>
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 17.5rem), 1fr)); gap: 1.25rem; }
        .doc-card { background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: 1rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; box-shadow: var(--cbm-card-shadow); }
        .doc-card:hover { transform: translateY(-3px); box-shadow: 0 8px 1.5rem var(--cbm-blue-glow); border-color: var(--cbm-blue); }
        .doc-icon { width: 3rem; height: 3rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .75rem; font-weight: 800; letter-spacing: .03em; flex-shrink: 0; }
        .doc-icon.xls { background: linear-gradient(135deg, #10b981, #059669); }
        .doc-icon.pdf { background: linear-gradient(135deg, #f43f5e, #be123c); }
        .doc-icon.doc { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .doc-icon.ppt { background: linear-gradient(135deg, #fb923c, #ea580c); }
        .doc-icon.other { background: linear-gradient(135deg, #64748b, #475569); }
        .doc-title { font-size: 1rem; font-weight: 700; color: var(--cbm-text); line-height: 1.3; margin: 0; word-break: break-word; }
        .doc-meta { font-size: .75rem; color: var(--cbm-text-muted); display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .doc-btn { background: var(--cbm-nav-hover); color: var(--cbm-blue); border: 0; border-radius: .625rem; padding: .55rem; font-size: .8125rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: .375rem; text-decoration: none; transition: background .2s, color .2s; width: 100%; }
        .doc-btn:hover { background: var(--cbm-blue); color: #fff; }
        .doc-btn.missing { color: #ef4444; cursor: not-allowed; pointer-events: none; }
    </style>

    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-blue">Lainnya</div>
            <h1 class="mod-title">Document Center</h1>
            <p class="mod-subtitle">Unduh template laporan, SOP, dan regulasi terbaru.</p>
        </div>
        <div class="mod-actions">
            @hasrole(\App\Helpers\RoleHelper::SUPER_ADMIN)
                <a href="{{ route('documents.master') }}" wire:navigate class="mod-btn-outline" style="text-decoration:none;">Kelola dokumen</a>
            @endhasrole
        </div>
    </div>

    <div class="cbm-tabs" style="margin-bottom:1rem;border:1px solid var(--cbm-card-border);border-radius:1rem;background:var(--cbm-card-bg);padding-top:.25rem;">
        <button type="button" wire:click="setCategory('all')" class="cbm-tab {{ $category === 'all' ? 'active' : '' }}">Semua <span class="cbm-tab-count">{{ $counts->sum() }}</span></button>
        @foreach($categories as $value => $label)
            <button type="button" wire:click="setCategory('{{ $value }}')" class="cbm-tab {{ $category === $value ? 'active' : '' }}">{{ $label }} <span class="cbm-tab-count">{{ $counts[$value] ?? 0 }}</span></button>
        @endforeach
    </div>

    <div class="mod-card" style="padding:0;margin-bottom:1.25rem;">
        <div class="mod-toolbar" style="border-bottom:0;">
            <div class="mod-filters">
                <div class="mod-field mod-field-search" style="max-width:26rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari judul dokumen..." aria-label="Cari dokumen">
                    <button type="button" wire:click="$set('search', '')" class="mod-clear-btn" aria-label="Hapus pencarian" x-data x-show="$wire.search" x-cloak>&times;</button>
                </div>
                <span wire:loading wire:target="search, setCategory" class="cbm-spinner" aria-label="Memuat"></span>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($documents->total()) }} dokumen</span></div>
        </div>
    </div>

    <div class="doc-grid">
        @forelse($documents as $doc)
            <div class="doc-card" wire:key="doc-{{ $doc->id }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
                    <div class="doc-icon {{ $doc->typeClass() }}">{{ strtoupper($doc->file_extension ?: 'FILE') }}</div>
                    <div style="display:flex;gap:.375rem;flex-wrap:wrap;justify-content:flex-end;">
                        @if($doc->updated_at->gt(now()->subDays(7)))<span class="mod-badge-closed">Baru</span>@endif
                        <span class="mod-badge-inactive" style="text-transform:uppercase;">{{ $doc->categoryLabel() }}</span>
                    </div>
                </div>

                <h3 class="doc-title">{{ $doc->title }}</h3>

                <div style="margin-top:auto;">
                    <div class="doc-meta" style="margin-bottom:.75rem;">
                        <span>{{ $doc->file_size ?: '-' }}</span>
                        <span>Diperbarui {{ $doc->updated_at->format('d M Y') }}</span>
                    </div>
                    @if($doc->fileExists())
                        <a href="{{ route('documents.download', $doc) }}" class="doc-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem;height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            Unduh dokumen
                        </a>
                    @else
                        <span class="doc-btn missing" title="File tidak ada di server. Hubungi administrator.">File tidak tersedia</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="mod-card" style="grid-column:1/-1;">
                <div class="mod-empty">
                    <div class="mod-empty-title">{{ ($search || $category !== 'all') ? 'Tidak ada dokumen yang cocok' : 'Belum ada dokumen' }}</div>
                    <div class="mod-empty-sub">{{ ($search || $category !== 'all') ? 'Ubah kata kunci atau pilih kategori lain.' : 'Dokumen akan muncul di sini setelah diunggah oleh administrator.' }}</div>
                </div>
            </div>
        @endforelse
    </div>

    @if($documents->hasPages())
        <div class="mod-pagination" style="margin-top:1rem;">{{ $documents->links('pagination::tailwind') }}</div>
    @endif
</div>
