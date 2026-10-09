<div>
    <x-master.page-header title="Master Data Dokumen" subtitle="Kelola file template, SOP, dan regulasi yang tampil di Document Center." accent="green" eyebrow="Lainnya" create-label="Upload Dokumen" />

    <x-flash />

    <div class="mod-card mod-card-accent-green">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari judul dokumen..." aria-label="Cari dokumen">
                </div>
                <label class="mod-field mod-field-labelled"><span>Kategori</span>
                    <select wire:model.live="categoryFilter" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        @foreach($categories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                @if($search || $categoryFilter)
                    <button type="button" wire:click="clearFilters" class="mod-btn-outline mod-btn-sm">Reset filter</button>
                @endif
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($documents->total()) }} dokumen</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr><th>JUDUL DOKUMEN</th><th>KATEGORI</th><th>TIPE &amp; UKURAN</th><th>DIPERBARUI</th><th style="text-align:right;">AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr wire:key="doc-{{ $doc->id }}">
                            <td>
                                <div class="mod-aircraft-name">{{ $doc->title }}</div>
                                @unless($doc->fileExists())<div class="mod-aircraft-sub" style="color:#ef4444;">File tidak ada di server</div>@endunless
                            </td>
                            <td><span class="mod-badge-inactive">{{ $doc->categoryLabel() }}</span></td>
                            <td><strong>{{ strtoupper($doc->file_extension ?: '-') }}</strong> &middot; {{ $doc->file_size ?: '-' }}</td>
                            <td style="white-space:nowrap;">{{ $doc->updated_at->format('d M Y') }}</td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    @if($doc->fileExists())
                                        <a href="{{ route('documents.download', $doc) }}" class="mod-action-btn" style="text-decoration:none;">Unduh</a>
                                    @endif
                                    <button type="button" wire:click="edit({{ $doc->id }})" class="mod-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $doc->id }})" wire:confirm="Hapus dokumen &quot;{{ $doc->title }}&quot; beserta filenya?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ ($search || $categoryFilter) ? 'Tidak ada dokumen yang cocok' : 'Belum ada dokumen' }}</div>
                                <div class="mod-empty-sub">{{ ($search || $categoryFilter) ? 'Ubah kata kunci atau reset filter.' : 'Klik "Upload Dokumen" untuk menambahkan.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())
            <div class="mod-pagination">{{ $documents->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isModalOpen" :title="$documentId ? 'Edit Dokumen' : 'Upload Dokumen Baru'" submit="save" close="closeModal" max-width="32rem" submit-label="Simpan Dokumen">
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="doc-title">Judul dokumen *</label>
            <input id="doc-title" type="text" wire:model="title" class="cbm-form-input" maxlength="255" autocomplete="off">
            @error('title') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group">
            <label class="cbm-form-label" for="doc-cat">Kategori *</label>
            <div class="cbm-select-wrap">
                <select id="doc-cat" wire:model="category" class="cbm-form-select">
                    @foreach($categories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            @error('category') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="cbm-form-group" style="margin-bottom:0;">
            <label class="cbm-form-label">File dokumen {{ $documentId ? '(kosongkan jika tidak ingin mengganti)' : '*' }}</label>
            <div class="cbm-upload-zone">
                <input type="file" wire:model="file" accept=".{{ implode(',.', \App\Models\Document::ALLOWED_EXTENSIONS) }}">
                <div class="cbm-upload-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                </div>
                <div class="cbm-upload-title">{{ $file ? $file->getClientOriginalName() : 'Klik untuk pilih file' }}</div>
                <div class="cbm-upload-sub">{{ $allowed }} &middot; maks. {{ $maxMb }} MB</div>
                <div wire:loading wire:target="file" style="margin-top:.5rem;font-size:.8125rem;color:var(--cbm-text-muted);">Mengunggah file...</div>
            </div>
            @error('file') <span class="mod-field-error">{{ $message }}</span> @enderror
        </div>
    </x-master.modal>
</div>
