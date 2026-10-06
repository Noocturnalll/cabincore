<div>
    <style>
        .cbm-file-input::-webkit-file-upload-button {
            background: var(--cbm-bg);
            border: 1px solid var(--cbm-input-border);
            color: var(--cbm-text);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .cbm-file-input::-webkit-file-upload-button:hover {
            background: var(--cbm-nav-hover);
        }
        .cbm-file-input::file-selector-button {
            background: var(--cbm-bg);
            border: 1px solid var(--cbm-input-border);
            color: var(--cbm-text);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .cbm-file-input::file-selector-button:hover {
            background: var(--cbm-nav-hover);
        }
    </style>
    <div class="cbm-page-header" style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1rem; justify-content: space-between; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(16,185,129,0.3);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <h1 class="cbm-greeting" style="font-size: 1.5rem; margin-bottom: 0.25rem;">Master Data Dokumen</h1>
                    <p class="cbm-greeting-sub">Kelola file template, SOP, dan regulasi yang akan tampil di Document Center.</p>
                </div>
            </div>
            
            <button wire:click="create()" class="cbm-btn cbm-btn-primary" style="background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; padding: 0.5rem 1.5rem; border-radius: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Upload Dokumen
            </button>
        </div>
    </div>

    <div class="cbm-card" style="padding: 0; overflow: hidden; border: 1px solid var(--cbm-card-border);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="background: var(--cbm-nav-hover); border-bottom: 1px solid var(--cbm-card-border); text-align: left;">
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Judul Dokumen</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Kategori</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Tipe & Ukuran</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Diperbarui</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody style="background: var(--cbm-card-bg);">
                    @forelse($documents as $doc)
                    <tr style="border-bottom: 1px solid var(--cbm-card-border);">
                        <td style="padding: 1rem 1.5rem; font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">{{ $doc->title }}</td>
                        <td style="padding: 1rem 1.5rem;">
                            <span style="font-size: 0.6875rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 99px; background: rgba(59,130,246,0.1); color: #3b82f6; text-transform: uppercase;">
                                {{ $doc->category }}
                            </span>
                        </td>
                        <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: var(--cbm-text-muted);">
                            <span style="font-weight: 700; color: var(--cbm-text);">{{ strtoupper($doc->file_extension) }}</span> &middot; {{ $doc->file_size }}
                        </td>
                        <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: var(--cbm-text-muted);">
                            {{ $doc->updated_at->format('d M Y') }}
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="cbm-btn" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border-radius: 0.5rem; background: rgba(59,130,246,0.1); color: #3b82f6; text-decoration: none;">Lihat</a>
                            <button wire:click="edit({{ $doc->id }})" class="cbm-btn" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border-radius: 0.5rem; background: var(--cbm-nav-hover); color: var(--cbm-text);">Edit</button>
                            <button wire:click="delete({{ $doc->id }})" wire:confirm="Yakin ingin menghapus dokumen ini?" class="cbm-btn" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border-radius: 0.5rem; background: rgba(239,68,68,0.1); color: #ef4444;">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 3rem 1.5rem; text-align: center; color: var(--cbm-text-muted); font-size: 0.875rem;">
                            Belum ada master data dokumen.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    @if($isModalOpen)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; backdrop-filter: blur(4px);">
        <div style="background: var(--cbm-card-bg); border: 1px solid var(--cbm-card-border); border-radius: 1.25rem; width: 100%; max-width: 32rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-card-border); display: flex; justify-content: space-between; align-items: center; background: var(--cbm-nav-hover);">
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--cbm-text); margin: 0;">{{ $documentId ? 'Edit Dokumen' : 'Upload Dokumen Baru' }}</h3>
                <button wire:click="closeModal()" style="background: transparent; border: none; color: var(--cbm-text-muted); cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Judul Dokumen <span style="color: #ef4444;">*</span></label>
                    <input type="text" wire:model.defer="title" class="cbm-input" required style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                    @error('title') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">Kategori <span style="color: #ef4444;">*</span></label>
                    <select wire:model.defer="category" class="cbm-input" required style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                        <option value="template">Template Excel</option>
                        <option value="sop">SOP & Panduan</option>
                        <option value="regulasi">Regulasi</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                    @error('category') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">File Dokumen {{ $documentId ? '(Opsional - isi jika ingin mengganti file)' : '*' }}</label>
                    <input type="file" wire:model="file" class="cbm-input cbm-file-input" {{ $documentId ? '' : 'required' }} style="padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--cbm-input-border); background: var(--cbm-bg); color: var(--cbm-text);">
                    <span style="font-size: 0.75rem; color: var(--cbm-text-muted);">Format didukung: PDF, XLSX, DOCX (Maks: 50MB)</span>
                    @error('file') <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span> @enderror
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                    <button type="button" wire:click="closeModal()" class="cbm-btn" style="padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; background: transparent; border: 1px solid var(--cbm-input-border); color: var(--cbm-text);">Batal</button>
                    <button type="submit" class="cbm-btn cbm-btn-primary" style="padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 700; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; color: white; display: flex; align-items: center; gap: 0.5rem;">
                        <span wire:loading wire:target="save, file" style="margin-right: 0.25rem;">⏳</span>
                        Simpan Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
