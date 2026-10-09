<?php

namespace App\Imports;

use App\Services\Dja\DjaIngestor;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DjaImport implements Import, SkipsUnknownSheets, WithMultipleSheets
{
    public function __construct()
    {
        // Fresh counters for this file; the ingestor is shared so the caller can read the totals afterwards
        app(DjaIngestor::class)->begin();
    }

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
