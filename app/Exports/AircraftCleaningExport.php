<?php

namespace App\Exports;

use App\Models\AircraftCleaning;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AircraftCleaningExport implements FromQuery, WithHeadings, WithMapping
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

    public function query()
    {
        $query = AircraftCleaning::query();

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
        }

        if ($this->activeTab) {
            $query->where('type', $this->activeTab);

            if (in_array($this->activeTab, ['DCI', 'DCE'])) {
                $query->where('status', 'Closed');
            }
        }

        return $query->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Aircraft Registration',
            'Date',
            'Shift',
            'Type',
            'Station',
            'Operator',
            'Status',
            'Remarks',
        ];
    }

    public function map($row): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        return [
            $rowNumber,
            $row->aircraft_registration,
            $row->date,
            $row->shift,
            $row->type,
            $row->station,
            $row->operator,
            $row->status,
            $row->remarks,
        ];
    }
}
