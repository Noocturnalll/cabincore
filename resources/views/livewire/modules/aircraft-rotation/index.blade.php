<div>
    <style>
        .cbm-file-input::-webkit-file-upload-button {
            background: var(--cbm-card-bg);
            border: 1px solid var(--cbm-border);
            color: var(--cbm-text);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .cbm-file-input::-webkit-file-upload-button:hover {
            background: var(--cbm-nav-hover);
        }
        .cbm-file-input::file-selector-button {
            background: var(--cbm-card-bg);
            border: 1px solid var(--cbm-border);
            color: var(--cbm-text);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
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
            <div class="mod-subtitle">Unggah, render, dan pratinjau jadwal rotasi pesawat secara interaktif.</div>
        </div>
    </div>

    @if (session()->has('message'))
        <div style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid rgba(34, 197, 94, 0.2); display: flex; align-items: center; gap: 0.75rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 0.75rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="mod-card mod-card-accent-purple" style="margin-bottom: 1.5rem;">
        <div style="padding: 1.25rem 1.5rem;">
            <div style="margin-bottom: 1rem;">
                <h4 style="margin: 0; font-size: 1rem; color: var(--cbm-text); font-weight: 600;">Upload File Rotasi Baru</h4>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.8125rem; color: var(--cbm-text-muted);">Format didukung: .xls, .xlsx (maks 50MB). Pastikan tab-tab Excel disusun dengan rapi.</p>
            </div>
            
            <form action="{{ route('import.aircraft-rotation') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('span').innerText='Memproses, harap tunggu...';">
                @csrf
                <div style="flex: 1; min-width: 15rem; position: relative;">
                    <input type="file" name="importFile" class="cbm-file-input" accept=".xlsx,.xls" required style="width: 100%; padding: 0.5rem; background: var(--cbm-bg); border: 1px dashed var(--cbm-border); border-radius: 0.5rem; color: var(--cbm-text-muted); cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='var(--cbm-purple)'" onblur="this.style.borderColor='var(--cbm-border)'">
                </div>
                <button type="submit" style="background: var(--cbm-purple); color: white; border: none; padding: 0.625rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(192, 132, 252, 0.25); transition: all 0.2s; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span>Upload & Render</span>
                </button>
            </form>
            @error('importFile') <div style="color: #f87171; font-size: 0.8125rem; margin-top: 0.5rem;">{{ $message }}</div> @enderror
        </div>
    </div>

    @if($rotations->count() > 0)
        <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
            <div style="width: 16rem; flex-shrink: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                <h4 style="margin: 0; font-size: 0.8125rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding-left: 0.25rem;">Riwayat Upload</h4>
                
                @foreach($rotations as $rotation)
                    <div class="mod-card" style="padding: 1rem; border-left: 3px solid var(--cbm-purple); transition: transform 0.2s; cursor: default;">
                        <div style="font-weight: 600; color: var(--cbm-text); margin-bottom: 0.375rem; font-size: 0.875rem; line-height: 1.3;">{{ $rotation->title }}</div>
                        <div style="color: var(--cbm-text-sub); margin-bottom: 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.375rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            {{ $rotation->created_at->format('d M Y, H:i') }}
                        </div>
                        
                        <div style="display: flex; gap: 0.375rem;">
                            <a href="{{ route('rotations.view', $rotation->id) }}" target="rotation_frame" title="Lihat Pratinjau HTML" style="flex: 1; text-align: center; background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.4rem; border-radius: 0.375rem; text-decoration: none; display: flex; justify-content: center; align-items: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(59, 130, 246, 0.15)'" onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                            <a href="{{ route('rotations.download', $rotation->id) }}" title="Unduh File Excel" style="flex: 1; text-align: center; background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 0.4rem; border-radius: 0.375rem; text-decoration: none; display: flex; justify-content: center; align-items: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(34, 197, 94, 0.15)'" onmouseout="this.style.background='rgba(34, 197, 94, 0.1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </a>
                            <button wire:click="deleteRotation({{ $rotation->id }})" wire:confirm="Hapus file rotasi ini permanen?" title="Hapus File" style="flex: 1; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; padding: 0.4rem; border-radius: 0.375rem; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.15)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mod-card" style="flex: 1; min-width: 18.75rem; padding: 0; overflow: hidden; height: calc(100vh - 12rem); min-height: 31.25rem; border: 1px solid var(--cbm-border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);">
                <div style="background: var(--cbm-nav-hover); padding: 0.75rem 1rem; border-bottom: 1px solid var(--cbm-border); display: flex; align-items: center; gap: 0.5rem;">
                    <div style="display: flex; gap: 0.375rem;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></div>
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--cbm-text-muted); font-family: monospace; margin-left: 0.5rem;">Rotations Viewer</span>
                </div>
                <iframe name="rotation_frame" src="{{ route('rotations.view', $rotations->first()->id) }}" style="width:100%; height:calc(100% - 2.5rem); border:0; background: var(--cbm-bg);"></iframe>
            </div>
        </div>
    @else
        <div class="cbm-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <h4>Belum ada data Rotasi Pesawat</h4>
            <p>Silakan upload file Excel (.xls atau .xlsx) dari jadwal Aircraft Rotation.</p>
        </div>
    @endif
</div>
