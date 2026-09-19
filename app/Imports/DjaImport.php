<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\Import;

class DjaImport implements WithMultipleSheets, SkipsUnknownSheets, Import
{
    public function sheets(): array
    {
        return [
            'DJA' => new DjaSheetImport('DJA'),
            'DJA DMI' => new DjaSheetImport('DJA DMI'),
            'DJA NSRD' => new DjaSheetImport('DJA NSRD'),
            'DJA NSRDI' => new DjaSheetImport('DJA NSRDI'),
        ];
    }
    
    public function onUnknownSheet(string|int $sheetName): void
    {
        // Skip unknown sheets silently
    }
}
