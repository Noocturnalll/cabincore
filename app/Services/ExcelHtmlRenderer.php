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
body{margin:0;font-family:sans-serif}
.tabs{position:sticky;top:0;background:#1a1d24;display:flex;gap:4px;overflow-x:auto;border-bottom:1px solid #334155;z-index:5;padding:8px}
.tab{border:0;padding:8px 14px;background:#334155;color:#94a3b8;cursor:pointer;white-space:nowrap;border-radius:6px;font-size:13px;font-weight:600;}
.tab:hover{background:#475569;color:#fff;}
.tab.on{background:#3b82f6;color:#fff;}
.pane{display:none;overflow:auto;padding:12px;background:#0f1115;color:#e2e8f0;}
.pane.on{display:block}
/* override phpspreadsheet default colors for dark mode if needed, but it might break cell colors. */
{$css}
</style></head><body>
<div class="tabs">{$tabs}</div>
{$panes}
<script>
const tabs=[...document.querySelectorAll('.tab')];
function show(i){tabs.forEach(t=>t.classList.toggle('on',t.dataset.i==i));
 document.querySelectorAll('.pane').forEach(p=>p.classList.toggle('on',p.id=='p'+i));
 try{localStorage.setItem('rot_tab',i)}catch(e){}}
tabs.forEach(t=>t.onclick=()=>show(t.dataset.i));
// default: sheet terakhir (sheet harian), atau tab terakhir yang dibuka
let last=tabs[tabs.length-1]?.dataset.i;
try{last=localStorage.getItem('rot_tab')??last}catch(e){}
if(tabs.length)show(last);
</script></body></html>
HTML;
    }
}
