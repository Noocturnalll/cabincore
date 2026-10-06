<?php

namespace App\Imports;

use App\Imports\Sheets\DailyReportSheetImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyReportImport implements SkipsUnknownSheets, WithMultipleSheets
{
    /**
     * Key harus SAMA PERSIS dengan nama sheet di Excel.
     * Sheet lain di file (mis. 'ALL FINDING ', 'SUMMARY CABIN ON DUTY', dan 'UNSCHED' yang hidden)
     * otomatis dilewati lewat onUnknownSheet().
     */
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

    public function onUnknownSheet($sheetName): void
    {
        Log::info('Sheet dilewati: '.$sheetName);
    }
}
