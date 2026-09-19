<div>
    <div class="cbm-page-header">
        <div style="display: flex; align-items: center; gap: 1rem; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-cyan)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(59,130,246,0.3);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <h1 class="cbm-greeting" style="font-size: 1.5rem;">Document Center</h1>
                    <p class="cbm-greeting-sub">Unduh template laporan, SOP, dan regulasi terbaru.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.75rem;">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari dokumen..." style="background: var(--cbm-input-bg); border: 1px solid var(--cbm-input-border); border-radius: 0.75rem; padding: 0.5rem 1rem; color: var(--cbm-text); outline: none; font-family: inherit; font-size: 0.875rem;">
                <select wire:model.live="category" style="background: var(--cbm-input-bg); border: 1px solid var(--cbm-input-border); border-radius: 0.75rem; padding: 0.5rem 1rem; color: var(--cbm-text); outline: none; font-family: inherit; font-size: 0.875rem;">
                    <option value="all">Semua Kategori</option>
                    <option value="template">Template Excel</option>
                    <option value="sop">SOP & Panduan</option>
                    <option value="regulasi">Regulasi</option>
                </select>
            </div>
        </div>
    </div>

    <style>
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }
        .doc-card {
            background: var(--cbm-card-bg);
            border: 1px solid var(--cbm-card-border);
            border-radius: 1rem;
            padding: 1.25rem;
            transition: var(--cbm-transition), transform 0.2s;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .doc-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px var(--cbm-blue-glow);
            border-color: var(--cbm-blue);
        }
        .doc-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            font-weight: 800;
        }
        .icon-xlsx { background: linear-gradient(135deg, #10b981, #059669); }
        .icon-pdf { background: linear-gradient(135deg, #f43f5e, #be123c); }
        
        .doc-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--cbm-text);
            line-height: 1.3;
        }
        .doc-meta {
            font-size: 0.75rem;
            color: var(--cbm-text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doc-btn {
            background: var(--cbm-nav-hover);
            color: var(--cbm-blue);
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--cbm-transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            text-decoration: none;
        }
        .doc-btn:hover {
            background: var(--cbm-blue);
            color: white;
        }
    </style>

    <div class="doc-grid">
        @forelse($documents as $doc)
        <div class="doc-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="doc-icon icon-{{ $doc['ext'] }}">
                    {{ strtoupper($doc['ext']) }}
                </div>
                <span style="font-size: 0.6875rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 99px; background: var(--cbm-nav-hover); color: var(--cbm-text-sub); text-transform: uppercase;">
                    {{ $doc['category'] }}
                </span>
            </div>
            
            <div>
                <h3 class="doc-title">{{ $doc['title'] }}</h3>
            </div>
            
            <div style="margin-top: auto;">
                <div class="doc-meta" style="margin-bottom: 0.75rem;">
                    <span>{{ $doc['size'] }}</span>
                    <span>Diperbarui: {{ $doc['date'] }}</span>
                </div>
                <button type="button" class="doc-btn" style="width: 100%;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1rem; height: 1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Dokumen
                </button>
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; background: var(--cbm-card-bg); border: 1px dashed var(--cbm-divider); border-radius: 1rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: var(--cbm-text-muted); opacity: 0.5;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <p style="color: var(--cbm-text-muted); font-weight: 500;">Tidak ada dokumen yang ditemukan.</p>
        </div>
        @endforelse
    </div>

</div>
