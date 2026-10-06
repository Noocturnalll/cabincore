<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require 'vendor/autoload.php';
$s = IOFactory::load('C:/Users/achai/Downloads/CABIN ON-DUTY PRODUCTION 18 SEP 2026.xlsx');

$sheet = $s->getSheetByName('WO');
$rows = [];
foreach ($sheet->getRowIterator(1, 10) as $row) {
    $r = [];
    foreach ($row->getCellIterator() as $cell) {
        $r[] = $cell->getValue();
    }
    $rows[] = $r;
}
print_r($rows);
