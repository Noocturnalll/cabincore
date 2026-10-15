<div>
    <x-master.page-header title="Log Sinkron GSS" subtitle="Dokumen konflik, penulisan ke GSS yang gagal (dicoba ulang otomatis), dan yang sudah pulih sendiri." accent="blue" eyebrow="Sistem" />

    <div class="mod-card" style="padding:.9rem 1.25rem;margin-bottom:1rem;">
        <div><strong>Sinkron terakhir:</strong>
            <span class="{{ $lastStatus === 'failed' ? 'mod-badge-open' : 'mod-badge-closed' }}">{{ $lastStatus ?: 'belum pernah' }}</span>
            @if($lastAt) <span class="mod-subtitle"> · {{ \Carbon\Carbon::parse($lastAt)->diffForHumans() }}</span> @endif
        </div>
        @if($lastMessage)<div class="mod-subtitle" style="margin-top:.35rem;">{{ $lastMessage }}</div>@endif
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="cbm-tabs">
            <button type="button" wire:click="$set('filter','attention')" class="cbm-tab {{ $filter === 'attention' ? 'active' : '' }}">Perlu perhatian <span class="cbm-tab-count">{{ $open }}</span></button>
            <button type="button" wire:click="$set('filter','healed')" class="cbm-tab {{ $filter === 'healed' ? 'active' : '' }}">Sudah pulih</button>
            <button type="button" wire:click="$set('filter','all')" class="cbm-tab {{ $filter === 'all' ? 'active' : '' }}">Semua</button>
        </div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead><tr><th>WAKTU</th><th>ARAH</th><th>JENIS</th><th>DOKUMEN</th><th>KEJADIAN</th><th>PERCOBAAN</th><th>PESAN</th><th style="text-align:right;">AKSI</th></tr></thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr wire:key="sl-{{ $r->id }}">
                            <td>{{ $r->updated_at->format('d M H:i') }}</td>
                            <td>{{ $r->direction === 'push' ? 'Web → GSS' : 'GSS → Web' }}</td>
                            <td>{{ strtoupper((string) $r->kind) ?: '-' }}</td>
                            <td><strong>{{ $r->doc_no ?: '-' }}</strong></td>
                            <td>
                                @if($r->event === 'healed')<span class="mod-badge-closed">Pulih</span>
                                @elseif($r->event === 'conflict')<span class="mod-badge-open">Konflik</span>
                                @elseif($r->event === 'failed')<span class="mod-badge-open">Gagal</span>
                                @else {{ $r->event }} @endif
                                @if($r->resolved_at && $r->event !== 'healed')<span class="mod-subtitle"> selesai</span>@endif
                            </td>
                            <td>{{ $r->attempts }}</td>
                            <td><x-text-popup :text="$r->message ?? ''" title="Pesan" /></td>
                            <td style="text-align:right;">
                                @if(! $r->resolved_at && $r->event !== 'healed')
                                    <button type="button" wire:click="resolve({{ $r->id }})" class="mod-action-btn">Tandai selesai</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="mod-empty"><div class="mod-empty-title">{{ $filter === 'attention' ? 'Tidak ada yang perlu perhatian' : 'Belum ada catatan' }}</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="mod-pagination">{{ $rows->links('pagination::tailwind') }}</div>@endif
    </div>
</div>
