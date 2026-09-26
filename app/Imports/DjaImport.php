<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DjaImport implements Import, SkipsUnknownSheets, WithMultipleSheets
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
