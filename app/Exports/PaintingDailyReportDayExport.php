<?php

namespace App\Exports;

use App\Services\Dja\DjaPersister;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** One workbook for one day of the Aircraft Painting daily report: DJA and Unplanned, each with pivot and detail. */
class PaintingDailyReportDayExport implements Export, WithMultipleSheets
{
    public const TABS = ['dja-painting' => 'DJA Painting', 'unplanned-painting' => 'Unplanned Painting'];

    public function __construct(protected string $date = '', protected string $search = '')
    {
        $this->date = $this->date ?: DjaPersister::activeDate();
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach (self::TABS as $tab => $prefix) {
            foreach ((new PaintingDailyReportExport($this->search, $this->date, $tab))->withSheetPrefix($prefix)->sheets() as $sheet) {
                $sheets[] = $sheet;
            }
        }

        return $sheets;
    }
}
