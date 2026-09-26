<?php

$files = glob('c:/Users/achai/cbm/resources/views/livewire/dashboard-*.blade.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Fix the corrupted em dash
    $content = str_replace('â€”', '&mdash;', $content);
    $content = str_replace('â€“', '&ndash;', $content); // in case there's an en dash
    // Also replace actual literal dashes to HTML entities to be safe from future powershell script encoding issues
    $content = str_replace('—', '&mdash;', $content);

    file_put_contents($file, $content);
}
