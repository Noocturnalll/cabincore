<?php

$files = [
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-super-admin.blade.php',
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-manager.blade.php',
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-admin.blade.php',
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-pic.blade.php',
];

$buttons = <<<'BLADE'
{{-- ════ PERIOD SELECTION ════ --}}
<div class="cbm-tabs-container" style="margin-bottom: 1rem; border-color: transparent; background: transparent; box-shadow: none; padding: 0;">
    <button wire:click="$set('period','daily')"   class="cbm-tab-btn {{ $period==='daily'   ? 'active' : '' }}">Harian</button>
    <button wire:click="$set('period','weekly')"  class="cbm-tab-btn {{ $period==='weekly'  ? 'active' : '' }}">Mingguan</button>
    <button wire:click="$set('period','monthly')" class="cbm-tab-btn {{ $period==='monthly' ? 'active' : '' }}">Bulanan</button>
</div>

{{-- ════ TABS NAVIGATION ════ --}}
BLADE;

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);

        // Add $period variable to @php block if not there
        if (strpos($content, '$period = $this->period ?? \'daily\';') === false) {
            $content = str_replace('@php', "@php\n    \$period = \$this->period ?? 'daily';", $content);
        }

        // Add the tabs
        if (strpos($content, 'wire:click="$set(\'period\',\'daily\')"') === false) {
            $content = str_replace('{{-- ════ TABS NAVIGATION ════ --}}', $buttons, $content);
            file_put_contents($file, $content);
            echo "Patched $file\n";
        } else {
            echo "Already patched $file\n";
        }
    }
}
