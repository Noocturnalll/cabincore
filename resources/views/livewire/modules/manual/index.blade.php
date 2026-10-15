<div>
    <x-master.page-header title="Referensi Manual" subtitle="Indeks AMM, CMM, dan part number. File PDF tetap di Google Drive dan dibuka dari sana." accent="blue" eyebrow="Supporting" />

    <div class="mod-card mod-card-accent-blue">
        <div class="cbm-tabs" style="flex-wrap:wrap;">
            @forelse($tabs as $name => $count)
                <button type="button" wire:click="pick(@js($name))" class="cbm-tab {{ $activeTab === $name ? 'active' : '' }}">
                    {{ $name }} <span class="cbm-tab-count">{{ $count }}</span>
                </button>
            @empty
                <span class="mod-subtitle" style="padding:.75rem 1rem;">Belum ada data. Sinkronkan sumber "Referensi Manual" di halaman Sumber Data.</span>
            @endforelse
        </div>

        <div style="padding:.9rem 1.25rem; display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
            <input type="search" wire:model.live.debounce.300ms="search" class="cbm-form-input" style="max-width:26rem;" placeholder="Cari ATA, part number, registrasi, vendor ...">
            @if($syncedAt)<span class="mod-subtitle">Diperbarui {{ \Carbon\Carbon::parse($syncedAt)->diffForHumans() }}</span>@endif
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr wire:key="mr-{{ $row->id }}">
                            @foreach($headers as $h)
                                @php($link = $row->links[$h] ?? null)
                                <td>
                                    @if($link)
                                        <a href="{{ $link[1] }}" target="_blank" rel="noopener noreferrer" class="mod-action-btn">{{ $link[0] ?: 'Buka' }} ↗</a>
                                        @if(\App\Models\ManualReference::previewUrl($link[1]))
                                            <button type="button" wire:click="preview(@js($link[1]), @js($link[0]))" class="mod-action-btn" title="Pratinjau di halaman ini">Lihat</button>
                                        @endif
                                    @else
                                        <span style="white-space:pre-line;">{{ $row->cells[$h] ?? '' }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(1, count($headers)) }}"><div class="mod-empty"><div class="mod-empty-title">{{ $search ? 'Tidak ada yang cocok' : 'Tab ini belum berisi data' }}</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="mod-pagination">{{ $rows->links('pagination::tailwind') }}</div>@endif
    </div>

    @if($previewUrl)
        <div class="cbm-modal-overlay" style="display:flex;" x-data x-on:keydown.escape.window="$wire.closePreview()" wire:click.self="closePreview">
            <div class="cbm-modal-panel" style="width:min(1100px,96vw); height:90vh; display:flex; flex-direction:column;" @click.stop>
                <div class="cbm-modal-header">
                    <div><div class="cbm-modal-title">{{ $previewTitle ?: 'Pratinjau' }}</div><div class="cbm-modal-subtitle">Dimuat langsung dari Google Drive</div></div>
                    <button class="cbm-modal-close" wire:click="closePreview" aria-label="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <iframe src="{{ $previewUrl }}" title="Pratinjau manual" style="flex:1; width:100%; border:0;" loading="lazy" referrerpolicy="no-referrer"></iframe>
            </div>
        </div>
    @endif
</div>
