<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\DailyJobAssignment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DjaExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
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
            'Total Tugas DJA' => number_format($data->count()).' tugas',
            'Filter Tanggal' => $this->dateFilter ?: 'Semua Tanggal',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Pivot Matrix: Station x Job Type
        $matrixStation = PivotBuilder::buildMatrix(
            $data,
            'station',
            'job_type',
            ['TRANSIT', 'RON', 'DAILY', 'WEEKLY'],
            'Station / Bandara',
            '1. Matriks Rekapitulasi: Station vs Job Type (Jenis Tugas)'
        );

        // 2. Breakdown: Registrasi Pesawat
        $breakdownAircraft = PivotBuilder::buildBreakdown(
            $data,
            'registration',
            'Registrasi Pesawat',
            '2. Rekapitulasi Penugasan per Pesawat'
        );

        $tables = [$matrixStation, $breakdownAircraft];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT DAILY JOB ASSIGNMENT (DJA)', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail DJA'),
        ];
    }

    public function collection(): Collection
    {
        $query = DailyJobAssignment::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('task_id', 'like', '%'.$this->search.'%')
                    ->orWhere('job_type', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        return $query->latest('date')->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'registration' => $item->aircraft_registration,
                'task_id' => $item->task_id,
                'job_type' => $item->job_type ?: 'UNASSIGNED',
                'description' => $item->description,
                'station' => $item->station ?: 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Aircraft Registration',
            'Task ID',
            'Job Type',
            'Description',
            'Station',
        ];
    }
}
