@props([
    'title',
    'subtitle' => null,
    'accent' => 'blue',
    'eyebrow' => 'Master Data',
    'createLabel' => null,     // when set, shows the primary "+ label" button that calls create()
])

<div class="mod-header">
    <div class="mod-title-block">
        <div class="mod-title-accent mod-title-accent-{{ $accent }}">{{ $eyebrow }}</div>
        <h1 class="mod-title">{{ $title }}</h1>
        @if($subtitle)<p class="mod-subtitle">{{ $subtitle }}</p>@endif
    </div>
    <div class="mod-actions">
        {{ $slot }}
        @if($createLabel)
            <button type="button" wire:click="create" class="mod-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                {{ $createLabel }}
            </button>
        @endif
    </div>
</div>
