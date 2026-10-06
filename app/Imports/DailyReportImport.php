<?php

namespace App\Imports;

use App\Imports\Sheets\DailyReportSheetImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyReportImport implements Import, SkipsUnknownSheets, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'WO' => new DailyReportSheetImport('WO'),
            'DMI CBM' => new DailyReportSheetImport('DMI'),
            'DJA AOC NSRDIL R01' => new DailyReportSheetImport('NSRDI'),
            'UNPLANNED' => new DailyReportSheetImport('UNPLANNED'),
            'DAILY REPORT' => new DailyReportSheetImport('CML'),
        ];
    }

    public function onUnknownSheet(string|int $sheetName): void
    {
        Log::info('Unknown sheet: '.$sheetName);
    }
}
