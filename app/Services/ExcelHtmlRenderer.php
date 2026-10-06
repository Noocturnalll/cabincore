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
body{margin:0;font-family:sans-serif;background:#0f1115;color:#e2e8f0;}
.tabs{position:sticky;top:0;background:#1a1d24;display:flex;gap:4px;overflow-x:auto;border-bottom:1px solid #334155;z-index:5;padding:8px}
.tab{border:0;padding:8px 14px;background:#334155;color:#94a3b8;cursor:pointer;white-space:nowrap;border-radius:6px;font-size:13px;font-weight:600;}
.tab:hover{background:#475569;color:#fff;}
.tab.on{background:#3b82f6;color:#fff;}
.pane{display:none;overflow:auto;padding:12px;background:#0f1115;color:#e2e8f0;}
.pane.on{display:block}

/* Override Excel Table Grid for Dark Mode */
table { border-collapse: collapse !important; width: max-content !important; min-width: 100%; }
td, th { border: 1px solid #1e293b !important; padding: 4px 8px; }

{$css}
</style></head><body>
<div class="tabs">{$tabs}</div>
{$panes}
<script>
// Tab Switching Logic
const tabs=[...document.querySelectorAll('.tab')];
function show(i){tabs.forEach(t=>t.classList.toggle('on',t.dataset.i==i));
 document.querySelectorAll('.pane').forEach(p=>p.classList.toggle('on',p.id=='p'+i));
 try{localStorage.setItem('rot_tab',i)}catch(e){}}
tabs.forEach(t=>t.onclick=()=>show(t.dataset.i));
let last=tabs[tabs.length-1]?.dataset.i;
try{last=localStorage.getItem('rot_tab')??last}catch(e){}
if(tabs.length)show(last);

// Dark Mode Color Normalizer (Mencegah warna sakit di mata)
document.querySelectorAll('td, th, span').forEach(el => {
    let style = window.getComputedStyle(el);
    let bg = style.backgroundColor;
    let color = style.color;
    
    let matchBg = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    let matchFg = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    
    // Cek apakah background transparent
    let isTransparent = bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent' || (matchBg && parseFloat(bg.split(',')[3]||1) === 0);
    
    if (matchBg && matchFg) {
        let r = parseInt(matchBg[1]), g = parseInt(matchBg[2]), b = parseInt(matchBg[3]);
        let fr = parseInt(matchFg[1]), fg = parseInt(matchFg[2]), fb = parseInt(matchFg[3]);
        
        let fgBrightness = (fr * 299 + fg * 587 + fb * 114) / 1000;
        
        // 1. Jika putih murni -> buat transparan, dan teks terang
        if (!isTransparent && r > 240 && g > 240 && b > 240) {
            el.style.backgroundColor = 'transparent';
            el.style.color = '#e2e8f0';
            return;
        }
        
        // 2. Jika warna cerah/neon (Red, Yellow, Green, Blue) -> Gelapkan 50%
        if (!isTransparent && (r > 100 || g > 100 || b > 100)) {
            el.style.backgroundColor = `rgb(${Math.floor(r*0.5)}, ${Math.floor(g*0.5)}, ${Math.floor(b*0.5)})`;
            el.style.color = '#ffffff'; // Teks wajib putih agar terbaca
            return;
        }
        
        // 3. Jika background sudah gelap/transparan, tapi teksnya hitam/gelap -> Terangkan teks
        if (fgBrightness < 100) {
            el.style.color = '#e2e8f0';
        }
    }
});
</script></body></html>
HTML;
    }
}
