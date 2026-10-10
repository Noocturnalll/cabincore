<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require 'vendor/autoload.php';
$s = IOFactory::load('C:/Users/achai/Downloads/CABIN ON-DUTY PRODUCTION 18 SEP 2026.xlsx');
print_r($s->getSheetNames());
