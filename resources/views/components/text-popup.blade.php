@props(['text', 'title' => 'Detail'])

@if(!empty(trim($text)))
<div x-data="{ open: false }" style="position: relative; max-width: 250px;" @mouseenter="open = true" @mouseleave="open = false">
    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: default; color: inherit;">
        {{ $text }}
    </div>
    
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         style="position: absolute; bottom: 100%; left: 0; z-index: 9999; display: none; margin-bottom: 5px;">
        <div style="background: #1f2937; color: #f3f4f6; padding: 10px 14px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); width: max-content; max-width: 400px; white-space: pre-wrap; font-size: 13px; line-height: 1.5; text-align: left; border: 1px solid #374151;">{{ trim($text) }}</div>
    </div>
</div>
@endif
