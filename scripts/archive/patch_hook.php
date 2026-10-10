<?php

$files = [
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-super-admin.blade.php',
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-manager.blade.php',
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-admin.blade.php',
    'c:/Users/achai/cbm/resources/views/livewire/dashboard-pic.blade.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);

        $hookCode = <<<'JS'
document.addEventListener('livewire:navigated', function() { 
    requestAnimationFrame(cbmInitCharts); 
    cbmInitTabs();
});
document.addEventListener('livewire:initialized', () => {
    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(cbmInitCharts);
    });
});
JS;

        if (strpos($content, "Livewire.hook('morph.updated'") === false) {
            $content = str_replace("document.addEventListener('livewire:navigated', function() { \n    requestAnimationFrame(cbmInitCharts); \n    cbmInitTabs();\n});", $hookCode, $content);
            file_put_contents($file, $content);
            echo "Added hook to $file\n";
        } else {
            echo "Hook already in $file\n";
        }
    }
}
