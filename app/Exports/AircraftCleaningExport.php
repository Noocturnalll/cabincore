<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\AircraftCleaning;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AircraftCleaningExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    use Exportable;

    protected $search;

    protected $dateFilter;

    protected $activeTab;

    public function __construct($search = '', $dateFilter = '', $activeTab = '')
    {
        $this->search = $search;
        $this->dateFilter = $dateFilter;
        $this->activeTab = $activeTab;
    }

    public function sheets(): array
    {
        $data = $this->collection();

        $metadata = [
            'Total Record' => number_format($data->count()).' cleaning logs',
            'Filter Tanggal' => $this->dateFilter ?: 'Semua Periode',
            'Filter Tipe' => $this->activeTab ? strtoupper($this->activeTab) : 'SEMUA TIPE',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Pivot Matrix: Station x Type
        $matrixStationType = PivotBuilder::buildMatrix(
            $data,
            'station',
            'type',
            ['DCI', 'DCE', 'GENERAL', 'DBI'],
            'Station / Bandara',
            '1. Matriks Rekapitulasi: Station vs Jenis Cleaning'
        );

        // 2. Pivot Matrix: Station x Shift
        $matrixStationShift = PivotBuilder::buildMatrix(
            $data,
            'station',
            'shift',
            ['Morning', 'Afternoon', 'Night'],
            'Station / Bandara',
            '2. Matriks Rekapitulasi: Station vs Shift Kerja'
        );

        // 3. Breakdown: Operator / Maskapai
        $breakdownOperator = PivotBuilder::buildBreakdown(
            $data,
            'operator',
            'Operator / Maskapai',
            '3. Rekapitulasi per Operator / Maskapai'
        );

        $tables = [$matrixStationType, $matrixStationShift, $breakdownOperator];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT AIRCRAFT CLEANING LOGS', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail Cleaning'),
        ];
    }

    public function collection(): Collection
    {
        $query = AircraftCleaning::query()
            ->select(['aircraft_registration', 'date', 'shift', 'type', 'station', 'operator', 'status', 'remarks']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        } elseif (! $this->search && ! $this->activeTab) {
            // If completely unfiltered across 50,000+ records, limit to recent 30 days for optimal performance
            $query->where('date', '>=', now()->subDays(30)->toDateString());
        }

        if ($this->activeTab) {
            $query->where('type', $this->activeTab);

            if (in_array($this->activeTab, ['DCI', 'DCE'])) {
                $query->where('status', 'Closed');
            }
        }

        $rowNumber = 0;

        return $query->orderBy('date', 'desc')->get()->map(function ($row) use (&$rowNumber) {
            $rowNumber++;

            return [
                'no' => $rowNumber,
                'registration' => $row->aircraft_registration,
                'date' => $row->date ? (is_string($row->date) ? $row->date : $row->date->format('Y-m-d')) : null,
                'shift' => $row->shift ?: 'Morning',
                'type' => $row->type ?: 'General',
                'station' => $row->station ?: 'N/A',
                'operator' => $row->operator ?: 'Lion Group',
                'status' => $row->status ?: 'Open',
                'remarks' => $row->remarks,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'NO',
            'AIRCRAFT REGISTRATION',
            'DATE',
            'SHIFT',
            'TYPE',
            'STATION',
            'OPERATOR',
            'STATUS',
            'REMARKS',
        ];
    }
}
