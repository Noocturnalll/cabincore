<div>
    <style>
        .cbm-file-input::-webkit-file-upload-button {
            background: var(--cbm-bg);
            border: 1px solid var(--cbm-border);
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
            border: 1px solid var(--cbm-border);
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
    {{-- Header --}}
    <div class="mod-header">
        <div class="mod-title-block">
            <div class="mod-title-accent mod-title-accent-purple">Aircraft Rotation</div>
            <div class="mod-title">Aircraft Rotation</div>
            <div class="mod-subtitle">Render dan pratinjau file rotasi Excel langsung di web.</div>
        </div>
    </div>

    @if (session()->has('message'))
        <div style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(34, 197, 94, 0.2);">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            {{ session('error') }}
        </div>
    @endif

    <div class="mod-card mod-card-accent-purple" style="margin-bottom: 2rem;">
        <div class="mod-toolbar" style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <form wire:submit.prevent="import" style="display: flex; gap: 10px; align-items: center;">
                <input type="file" wire:model="importFile" class="mod-search-input cbm-file-input" accept=".xlsx,.xls" style="padding-top: 5px; width: auto;" required>
                <button type="submit" class="mod-btn" style="background: var(--cbm-accent-purple); color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="import">Upload & Render Excel</span>
                    <span wire:loading wire:target="import">Memproses...</span>
                </button>
            </form>
            @error('importFile') <span style="color: #f87171; font-size: 0.85rem;">{{ $message }}</span> @enderror
        </div>
    </div>

    @if($rotations->count() > 0)
        <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <div class="mod-card" style="width: 250px; flex-shrink: 0; padding: 1rem;">
                <h4 style="margin: 0 0 1rem 0; font-size: 0.9rem; color: var(--cbm-text-muted); text-transform: uppercase;">Riwayat Upload</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    @foreach($rotations as $rotation)
                        <div style="background: var(--cbm-bg); padding: 0.75rem; border-radius: 6px; border: 1px solid var(--cbm-border); font-size: 0.85rem;">
                            <div style="font-weight: 600; color: var(--cbm-text); margin-bottom: 0.25rem;">{{ $rotation->title }}</div>
                            <div style="color: var(--cbm-text-muted); margin-bottom: 0.5rem;">{{ $rotation->created_at->format('d M Y, H:i') }}</div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('rotations.view', $rotation->id) }}" target="rotation_frame" style="flex: 1; text-align: center; background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.25rem; border-radius: 4px; text-decoration: none;">Lihat</a>
                                <a href="{{ route('rotations.download', $rotation->id) }}" style="flex: 1; text-align: center; background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 0.25rem; border-radius: 4px; text-decoration: none;">Unduh</a>
                            </div>
                            <button wire:click="deleteRotation({{ $rotation->id }})" wire:confirm="Hapus file rotasi ini?" style="width: 100%; margin-top: 0.5rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; padding: 0.25rem; border-radius: 4px; cursor: pointer;">Hapus</button>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div class="mod-card" style="flex-grow: 1; padding: 0; overflow: hidden; height: 80vh;">
                <iframe name="rotation_frame" src="{{ route('rotations.view', $rotations->first()->id) }}" style="width:100%; height:100%; border:0;"></iframe>
            </div>
        </div>
    @else
        <div style="color: var(--cbm-text-sub); text-align: center; padding: 3rem 0;">
            Belum ada data rotasi pesawat. Silakan upload file Excel.
        </div>
    @endif
</div>
