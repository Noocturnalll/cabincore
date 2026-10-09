<div>
    <style>
        .ch-tabs { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.35rem; margin-bottom:.9rem; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
        .ch-tabs::-webkit-scrollbar { display:none; }
        .ch-tab { flex:0 0 auto; border:1px solid var(--cbm-input-border); background:var(--cbm-input-bg); color:var(--cbm-text-muted); border-radius:999px; padding:.45rem .95rem; font-size:.85rem; font-weight:700; cursor:pointer; transition:background .15s,color .15s; }
        .ch-tab.on { background:var(--cbm-nav-active); color:var(--cbm-nav-active-t); border-color:transparent; }
    </style>

    <div class="ch-tabs" role="tablist" aria-label="Jenis cleaning">
        @foreach($types as $key => [$class, $label])
            <button type="button" role="tab" aria-selected="{{ $type === $key ? 'true' : 'false' }}" wire:click="setType('{{ $key }}')" class="ch-tab {{ $type === $key ? 'on' : '' }}">{{ $label }}</button>
        @endforeach
    </div>

    <livewire:dynamic-component :is="$component" :key="'cleaning-'.$type" />
</div>
