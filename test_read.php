<?php 
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('storage/app/private/livewire-tmp/qoSYY1FlZycgLInZeB1dbH6Iexufuc5lFwwOMxyH.xlsx'); 
foreach(['DJA', 'DJA NSRDI', 'DJA DMI'] as $tab) { 
    $sheet = $spreadsheet->getSheetByName($tab); 
    if ($sheet) { 
        echo "\n--- $tab ---\n"; 
        echo json_encode($sheet->rangeToArray('A1:X2', null, true, true, false)); 
    } 
}
