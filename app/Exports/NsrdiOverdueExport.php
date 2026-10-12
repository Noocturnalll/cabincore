<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\NsrdiOverdue;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NsrdiOverdueExport implements Export, WithMultipleSheets
{
    protected $search;

    public function __construct($search = '')
    {
        $this->search = $search;
    }

    public function sheets(): array
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

        $records = $query->latest()->get();

        $mapped = $records->map(function ($r) {
            return [
                'operator' => $r->operator ?: 'LION AIR',
                'aircraft_registration' => $r->aircraft_registration ?: '-',
                'ac_status' => $r->ac_status ?: 'Normal',
                'nsrdi_number' => $r->nsrdi_number ?: '-',
                'mddr' => $r->mddr ?: '-',
                'status' => $r->status ?: 'Open',
                'area' => $r->area ?: '-',
                'report_date' => $r->report_date ? (is_string($r->report_date) ? substr($r->report_date, 0, 10) : $r->report_date->format('Y-m-d')) : '-',
                'month_due' => $r->month_due ?: '-',
                'description' => $r->description ?: '-',
                'status_final' => $r->status_final ?: '-',
                'remarks' => $r->remarks ?: '-',
                'closed_at' => $r->closed_at ? (is_string($r->closed_at) ? substr($r->closed_at, 0, 19) : $r->closed_at->format('Y-m-d H:i:s')) : '-',
            ];
        });

        // 1. Pivot Table: Operator vs Status
        $matrixOperatorStatus = PivotBuilder::matrix(
            $mapped,
            'operator',
            'status',
            null,
            'count'
        );

        // 2. Pivot Table: Month Due vs Status
        $matrixMonthStatus = PivotBuilder::matrix(
            $mapped,
            'month_due',
            'status',
            null,
            'count'
        );

        // 3. Breakdown: Status A/C
        $breakdownAcStatus = PivotBuilder::breakdown(
            $mapped,
            'ac_status',
            'Kondisi / Status Pesawat'
        );

        $tables = [
            [
                'title' => '1. Rekapitulasi Operator vs Status Defect Overdue',
                'headers' => $matrixOperatorStatus['headers'],
                'rows' => $matrixOperatorStatus['rows'],
                'totalRow' => $matrixOperatorStatus['totalRow'],
            ],
            [
                'title' => '2. Rekapitulasi Jatuh Tempo (Month Due) vs Status',
                'headers' => $matrixMonthStatus['headers'],
                'rows' => $matrixMonthStatus['rows'],
                'totalRow' => $matrixMonthStatus['totalRow'],
            ],
            [
                'title' => '3. Distribusi Status Operasional A/C',
                'headers' => $breakdownAcStatus['headers'],
                'rows' => $breakdownAcStatus['rows'],
                'totalRow' => $breakdownAcStatus['totalRow'],
            ],
        ];

        $metadata = [
            'Total Overdue Defect' => (string) $records->count(),
            'Filter Pencarian' => $this->search ?: 'Semua Data',
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN REKAPITULASI NSRDI OVERDUE DEFECTS', $metadata, $tables),
        ];

        $headings = [
            'Operator',
            'A/C',
            'Status A/C',
            'Defect (NSRDI No)',
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

        $sheets[] = new DetailDataSheet('Detail NSRDI Overdue', $headings, $mapped);

        return $sheets;
    }
}
