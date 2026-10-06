$files = @(
    "c:\Users\achai\cbm\resources\views\livewire\modules\ims\repair\index.blade.php",
    "c:\Users\achai\cbm\resources\views\livewire\modules\ims\peminjaman\index.blade.php",
    "c:\Users\achai\cbm\resources\views\livewire\modules\ims\katalog\index.blade.php",
    "c:\Users\achai\cbm\resources\views\livewire\modules\ims\approval\index.blade.php"
)

foreach ($file in $files) {
    $content = Get-Content -Raw -Path $file
    $content = $content -replace '--cbm-color-border', '--cbm-border'
    $content = $content -replace '--cbm-color-primary', '--cbm-blue'
    $content = $content -replace '--cbm-color-text-muted', '--cbm-text-muted'
    $content = $content -replace '--cbm-color-text', '--cbm-text'
    $content = $content -replace '--cbm-color-surface', '--cbm-card-bg'
    $content = $content -replace '--cbm-color-background', '--cbm-sidebar-bg'
    $content = $content -replace '--cbm-radius-lg', '1rem'
    $content = $content -replace '--cbm-shadow-xl', '--cbm-card-shadow'
    Set-Content -Path $file -Value $content
}
echo "Replaced wrong CSS variables in IMS modules."
