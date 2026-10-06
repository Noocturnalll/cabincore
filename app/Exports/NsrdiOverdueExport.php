<?php

namespace App\Exports;

use App\Models\NsrdiOverdue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NsrdiOverdueExport implements FromCollection, WithHeadings
{
    protected $search;

    public function __construct($search = '')
    {
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $query = NsrdiOverdue::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('mddr', 'like', '%'.$this->search.'%');
            });
        }

        return $query->latest()->select(
            'operator',
            'aircraft_registration',
            'ac_status',
            'nsrdi_number',
            'mddr',
            'status',
            'area',
            'report_date',
            'month_due',
            'description',
            'status_final',
            'remarks',
            'closed_at'
        )->get();
    }

    public function headings(): array
    {
        return [
            'Operator',
            'A/C',
            'Status A/C',
            'Defect',
            'MDDR',
            'Status',
            'Area',
            'Report Date',
            'Month Due',
            'Defect Description',
            'Status Final',
            'Remarks',
            'Closed At',
        ];
    }
}
