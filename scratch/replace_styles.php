<?php

$file = 'C:/Users/achai/cbm/resources/views/livewire/reports/summary.blade.php';
$content = file_get_contents($file);

$content = preg_replace(
    '/style="background:\s*rgba\((\d+),(\d+),(\d+),0\.05\);\s*border:\s*1px\s*solid\s*rgba\(\1,\2,\3,0\.1\);"/',
    'style="--kpi-r: $1; --kpi-g: $2; --kpi-b: $3;"',
    $content
);

file_put_contents($file, $content);
echo 'Replaced successfully!';
