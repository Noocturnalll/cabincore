<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;

class ExcelHtmlRenderer
{
    /** Kembalikan HTML lengkap (tab + semua sheet) */
    public function render(string $absolutePath): string
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadEmptyCells(false);          // hemat memori
        $spreadsheet = $reader->load($absolutePath);

        $writer = new Html($spreadsheet);
        $writer->writeAllSheets();
        $css = $writer->generateStyles(true);

        $tabs = '';
        $panes = '';

        foreach ($spreadsheet->getAllSheets() as $i => $sheet) {
            if ($sheet->getSheetState() !== Worksheet::SHEETSTATE_VISIBLE) {
                continue; // lewati sheet tersembunyi
            }
            $writer->setSheetIndex($i);
            $name = e($sheet->getTitle());

            $tabs  .= "<button class='tab' data-i='{$i}'>{$name}</button>";
            $panes .= "<section class='pane' id='p{$i}'>" . $writer->generateSheetData() . '</section>';
        }

        $spreadsheet->disconnectWorksheets();

        return <<<HTML
<!doctype html><html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{margin:0;font-family:sans-serif;background:#ffffff;color:#000000;}
.tabs-wrap { position:sticky; top:0; background:#f1f5f9; border-bottom:1px solid #cbd5e1; z-index:5; display:flex; align-items:center; justify-content:space-between; padding:8px; }
.tabs{ display:flex; gap:4px; overflow-x:auto; }
.tab{border:0;padding:6px 12px;background:#e2e8f0;color:#475569;cursor:pointer;white-space:nowrap;border-radius:4px;font-size:13px;font-weight:600;}
.tab:hover{background:#cbd5e1;color:#1e293b;}
.tab.on{background:#3b82f6;color:#fff;}
.pane{display:none; padding:12px; transform-origin: top left;}
.pane.on{display:block}

.zoom-bar { display:flex; align-items:center; gap:8px; font-size:13px; color:#475569; margin-left:12px; font-weight:600; flex-shrink:0; }
.zoom-bar input { cursor:pointer; }

/* Table Reset */
table { border-collapse: collapse !important; width: max-content !important; }
td, th { padding: 0 4px !important; }

{$css}
</style></head><body>
<div class="tabs-wrap">
    <div class="tabs">{$tabs}</div>
    <div class="zoom-bar">
        <span>Zoom</span>
        <input type="range" id="zoomSlider" min="0.3" max="1.5" step="0.05" value="0.7">
        <strong id="zoomLabel">70%</strong>
    </div>
</div>
{$panes}
<script>
// Tab Switching
const tabs=[...document.querySelectorAll('.tab')];
const panes=[...document.querySelectorAll('.pane')];
function show(i){
    tabs.forEach(t=>t.classList.toggle('on',t.dataset.i==i));
    panes.forEach(p=>p.classList.toggle('on',p.id=='p'+i));
    try{localStorage.setItem('rot_tab',i)}catch(e){}
}
tabs.forEach(t=>t.onclick=()=>show(t.dataset.i));
let last=tabs[tabs.length-1]?.dataset.i;
try{last=localStorage.getItem('rot_tab')??last}catch(e){}
if(tabs.length)show(last);

// Zoom Logic
const zoomSlider = document.getElementById('zoomSlider');
const zoomLabel = document.getElementById('zoomLabel');
function updateZoom() {
    const val = zoomSlider.value;
    zoomLabel.innerText = Math.round(val * 100) + '%';
    panes.forEach(p => p.style.zoom = val);
    try{localStorage.setItem('rot_zoom', val)}catch(e){}
}
zoomSlider.oninput = updateZoom;
try{
    let savedZoom = localStorage.getItem('rot_zoom');
    if(savedZoom) { zoomSlider.value = savedZoom; }
}catch(e){}
updateZoom();
</script></body></html>
HTML;
    }
}
