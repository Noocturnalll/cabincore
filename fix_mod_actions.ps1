$files = Get-ChildItem -Path c:\Users\achai\cbm\resources\views\livewire\modules\ims\master\ -Filter *.blade.php
foreach ($file in $files) {
    $content = Get-Content -Raw -Path $file.FullName
    $content = $content -replace '<div class="mod-header-actions">', '<div class="mod-actions">'
    Set-Content -Path $file.FullName -Value $content
}
echo "Replaced mod-header-actions with mod-actions in master blades."
