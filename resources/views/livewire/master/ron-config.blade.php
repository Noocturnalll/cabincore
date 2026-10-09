<div>
    <x-master.page-header title="RON Settings" subtitle="Jumlah A/C Remain Over Night (RON) per station dan maskapai." accent="purple">
        <button type="button" class="mod-btn-outline" style="color:#ef4444;border-color:rgba(239,68,68,.4);"
                wire:click="resetToDefault" wire:confirm="Semua nilai RON akan di-reset menjadi 0. Lanjutkan?" wire:loading.attr="disabled" wire:target="resetToDefault">
            <span wire:loading.remove wire:target="resetToDefault" style="display:inline-flex;align-items:center;gap:.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem;height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                Reset semua ke 0
            </span>
            <span wire:loading wire:target="resetToDefault"><span class="cbm-spinner"></span> Mereset...</span>
        </button>
    </x-master.page-header>

    <x-flash />

    <div class="mod-card mod-card-accent-purple">
        <div class="mod-toolbar">
            <div class="mod-meta"><span class="mod-record-count">{{ $stations->count() }} station &middot; total RON {{ $stations->sum(fn ($s) => $s->ron_jt + $s->ron_iw + $s->ron_id + $s->ron_iu + $s->ron_sl + $s->ron_od) }}</span></div>
        </div>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr><th>NO</th><th>KH REGION</th><th>STA</th><th style="text-align:center;">JT</th><th style="text-align:center;">IW</th><th style="text-align:center;">ID</th><th style="text-align:center;">IU</th><th style="text-align:center;">SL</th><th style="text-align:center;">OD</th><th style="text-align:right;">AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse($stations as $s)
                        <tr wire:key="ron-{{ $s->id }}">
                            <td>{{ $s->order_no }}</td>
                            <td style="font-weight:800;">{{ $s->kh_region }}</td>
                            <td><div class="mod-aircraft-name">{{ $s->station_code }}</div></td>
                            @foreach(['ron_jt', 'ron_iw', 'ron_id', 'ron_iu', 'ron_sl', 'ron_od'] as $col)
                                <td style="text-align:center;{{ $s->$col ? 'font-weight:800;' : 'color:var(--cbm-text-sub);' }}">{{ $s->$col ?: '-' }}</td>
                            @endforeach
                            <td style="text-align:right;"><button type="button" wire:click="editRon({{ $s->id }})" class="mod-action-btn">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="mod-empty"><div class="mod-empty-title">Belum ada station</div><div class="mod-empty-sub">Tambahkan station di Capacity Settings terlebih dahulu.</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-master.modal :show="$isModalOpen" :title="'Edit RON - '.$station_code" :subtitle="$kh_region" submit="saveRon" close="closeModal" max-width="26rem" submit-label="Simpan RON">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(7rem,1fr));gap:1rem;">
            @foreach(['jt' => 'JT', 'iw' => 'IW', 'id' => 'ID', 'iu' => 'IU', 'sl' => 'SL', 'od' => 'OD'] as $key => $label)
                <div class="cbm-form-group" style="margin-bottom:0;">
                    <label class="cbm-form-label" for="ron-{{ $key }}">{{ $label }}</label>
                    <input id="ron-{{ $key }}" type="number" min="0" wire:model="ron_{{ $key }}" class="cbm-form-input">
                    @error('ron_'.$key) <span class="mod-field-error">{{ $message }}</span> @enderror
                </div>
            @endforeach
        </div>
    </x-master.modal>
</div>
