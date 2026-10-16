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

    protected string $search;

    protected string $dateFilter;

    protected string $activeTab;

    protected string $monthFilter;

    protected string $stationFilter;

    protected string $shiftFilter;

    protected string $operatorFilter;

    protected string $statusFilter;

    public function __construct(
        string $search = '',
        string $dateFilter = '',
        string $activeTab = '',
        string $monthFilter = '',
        string $stationFilter = '',
        string $shiftFilter = '',
        string $operatorFilter = '',
        string $statusFilter = ''
    ) {
        $this->search = $search;
        $this->dateFilter = $dateFilter;
        $this->activeTab = $activeTab;
        $this->monthFilter = $monthFilter;
        $this->stationFilter = $stationFilter;
        $this->shiftFilter = $shiftFilter;
        $this->operatorFilter = $operatorFilter;
        $this->statusFilter = $statusFilter;
    }

    public function sheets(): array
    {
        $data = $this->collection();

        $metadata = [
            'Total Record' => number_format($data->count()).' cleaning logs',
            'Filter Periode Bulan' => $this->monthFilter ?: 'Semua Bulan',
            'Filter Tanggal' => $this->dateFilter ?: 'Semua Tanggal',
            'Filter Jenis Cleaning' => $this->activeTab ?: 'Semua Jenis',
            'Filter Stasiun' => $this->stationFilter ?: 'Semua Stasiun',
            'Filter Shift' => $this->shiftFilter ?: 'Semua Shift',
            'Filter Operator' => $this->operatorFilter ?: 'Semua Operator',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Pivot Matrix: Station x Type
        $matrixStationType = PivotBuilder::buildMatrix(
            $data,
            'station',
            'type',
            ['DCI', 'DCE', 'GCI', 'GCE', 'Transit', 'General', 'LGT'],
            'Station / Bandara',
            '1. Matriks Rekapitulasi: Station vs Jenis Cleaning'
        );

        // 2. Pivot Matrix: Station x Shift
        $matrixStationShift = PivotBuilder::buildMatrix(
            $data,
            'station',
            'shift',
            ['Pagi', 'Siang', 'Malam'],
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
        $query = AircraftCleaning::aiec();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('aircraft_type', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('ref_no', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->monthFilter) {
            $query->where('date', 'like', $this->monthFilter.'%');
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        if ($this->stationFilter) {
            $query->where('station', $this->stationFilter);
        }

        if ($this->shiftFilter) {
            $query->where('shift', $this->shiftFilter);
        }

        if ($this->operatorFilter) {
            $query->where('operator', $this->operatorFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->activeTab) {
            $query->where('type', $this->activeTab);
        }

        $rowNumber = 0;

        return $query->orderBy('date', 'desc')->get()->map(function ($row) use (&$rowNumber) {
            $rowNumber++;

            return [
                'no' => $rowNumber,
                'registration' => $row->aircraft_registration,
                'aircraft_type' => $row->aircraft_type ?: '-',
                'date' => $row->date ? (is_string($row->date) ? $row->date : $row->date->format('Y-m-d')) : null,
                'station' => $row->station ?: 'N/A',
                'shift' => $row->shift ?: 'Pagi',
                'operator' => $row->operator ?: 'Lion Group',
                'type' => $row->type ?: 'General',
                'ac_status' => $row->ac_status ?: '-',
                'task_type' => $row->task_type ?: '-',
                'ref_no' => $row->ref_no ?: '-',
                'man_power' => $row->man_power ?? 0,
                'man_hour' => $row->man_hour ? round($row->man_hour, 2) : 0,
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
            'AIRCRAFT TYPE',
            'DATE',
            'STATION',
            'SHIFT',
            'OPERATOR',
            'TYPE',
            'A/C STATUS',
            'TASK TYPE',
            'REF NO',
            'TOTAL MP',
            'ACTUAL MHRS',
            'STATUS',
            'REMARKS',
        ];
    }
}
