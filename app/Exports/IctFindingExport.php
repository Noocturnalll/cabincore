<?php

namespace App\Exports;

use App\Models\IctFinding;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IctFindingExport implements FromCollection, WithHeadings
{
    protected $search;
    protected $dateFilter;

    public function __construct($search = '', $dateFilter = '')
    {
        $this->search = $search;
        $this->dateFilter = $dateFilter;
    }

    public function collection()
    {
        $query = IctFinding::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('no_finding', 'like', '%' . $this->search . '%')
                  ->orWhere('aircraft_registration', 'like', '%' . $this->search . '%')
                  ->orWhere('defect_description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        return $query->orderBy('date', 'desc')->select(
            'date',
            'no_finding',
            'operator',
            'aircraft_registration',
            'defect_description',
            'remarks',
            'status'
        )->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'No Finding',
            'Operator',
            'Aircraft Registration',
            'Defect Description',
            'Remarks',
            'Status'
        ];
    }
}
