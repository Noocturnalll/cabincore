<?php

namespace App\Imports;

use App\Imports\Sheets\AircraftCleaningSheetImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AircraftCleaningImport implements Import, SkipsUnknownSheets, WithMultipleSheets
{
    protected string $targetSheet;

    public function __construct(string $targetSheet = 'ALL')
    {
        $this->targetSheet = $targetSheet;
    }

    public function sheets(): array
    {
        // Jika target sheet spesifik dipilih
        if ($this->targetSheet !== 'ALL') {
            return [
                $this->targetSheet => new AircraftCleaningSheetImport($this->targetSheet),
            ];
        }

        // Default: Import semua sheet bulanan yang ada pada AIEC Report 2026
        return [
            'DATABASE' => new AircraftCleaningSheetImport('DATABASE (Okt 2026)'),
            'SEP' => new AircraftCleaningSheetImport('SEP (Sep 2026)'),
            'AUG 26' => new AircraftCleaningSheetImport('AUG 26 (Ags 2026)'),
            'JUL 26' => new AircraftCleaningSheetImport('JUL 26 (Jul 2026)'),
            'JUN 26' => new AircraftCleaningSheetImport('JUN 26 (Jun 2026)'),
            0 => new AircraftCleaningSheetImport('Sheet 1'),
        ];
    }

    public function onUnknownSheet(string|int $sheetName): void
    {
        Log::info('AIEC Import: Lewati sheet yang tidak dipetakan: '.$sheetName);
    }
}
