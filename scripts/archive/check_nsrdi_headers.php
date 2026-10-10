<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require 'vendor/autoload.php';
$s = IOFactory::load('C:/Users/achai/Downloads/CABIN ON-DUTY PRODUCTION 18 SEP 2026.xlsx');
$sheet = $s->getSheetByName('DJA AOC NSRDIL R01');
$r = [];
foreach ($sheet->getRowIterator(2, 2) as $row) {
    foreach ($row->getCellIterator() as $cell) {
        $r[] = $cell->getValue();
    }
}
print_r($r);
