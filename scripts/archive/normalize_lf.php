<?php

$dir = new RecursiveDirectoryIterator(__DIR__.'/resources/views');
$iterator = new RecursiveIteratorIterator($dir);

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        if (str_contains($content, "\r\n")) {
            file_put_contents($file->getPathname(), str_replace("\r\n", "\n", $content));
            $count++;
        }
    }
}

echo "Normalized {$count} files.\n";
