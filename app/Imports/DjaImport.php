<?php

namespace App\Imports;

use App\Services\Dja\DjaIngestor;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DjaImport implements Import, SkipsUnknownSheets, WithMultipleSheets
{
    public function __construct(protected ?string $targetDate = null)
    {
        @ini_set('memory_limit', '1024M');
        // Fresh counters for this file; the ingestor is shared so the caller can read the totals afterwards
        app(DjaIngestor::class)->begin();
    }

    public function sheets(): array
    {
        $dja = fn (string $tab) => new DjaSheetImport($tab, null, $this->targetDate);

        return [
            'List AC' => new DjaRonSheetImport('List AC', null, $this->targetDate),
            'LIST AC' => new DjaRonSheetImport('LIST AC', null, $this->targetDate),
            'Sheet1' => new DjaRonSheetImport('Sheet1', null, $this->targetDate),
            'DJA' => $dja('DJA'),
            'DJA R01' => $dja('DJA'),
            'DJA R01/WO' => $dja('DJA'),
            'DJA WO' => $dja('DJA'),
            'R01' => $dja('DJA'),
            'WO' => $dja('DJA'),
            'DJA DMI' => $dja('DJA DMI'),
            'DMI' => $dja('DJA DMI'),
            'DJA NSRD' => $dja('DJA NSRD'),
            'DJA NSRDI' => $dja('DJA NSRDI'),
            'DJA NSRDIL' => $dja('DJA NSRD'),
            'DJA AOC NSRDIL R01' => $dja('DJA NSRD'),
            'AOC NSRDI' => $dja('DJA NSRD'),
        ];
    }

    public function onUnknownSheet(string|int $sheetName): void
    {
        // Skip unknown sheets silently
    }
}
