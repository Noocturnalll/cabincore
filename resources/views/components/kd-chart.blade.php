@props(['config', 'height' => '16rem'])
{{-- A card with one Chart.js chart. The element is keyed by its data, so a new period builds a fresh chart. --}}
<figure class="kd-chart-card" wire:key="kdc-{{ $config['id'] ?? 'x' }}-{{ md5(json_encode($config)) }}" wire:ignore>
    <figcaption>
        <strong>{{ $config['title'] }}</strong>
        @if(! empty($config['sub']))<span class="kd-sub">{{ $config['sub'] }}</span>@endif
    </figcaption>
    <div class="kd-chart-box" style="height: {{ $height }};" x-data="kdChart({{ \Illuminate\Support\Js::from($config) }})">
        <canvas x-ref="canvas" role="img" aria-label="{{ $config['title'] }}"></canvas>
    </div>
</figure>
