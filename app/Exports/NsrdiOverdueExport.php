<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\NsrdiOverdue;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NsrdiOverdueExport implements Export, WithMultipleSheets
{
    protected $search;

    protected $tab;

    protected $operator;

    public function __construct($search = '', $tab = 'all', $operator = '')
    {
        $this->search = $search;
        $this->tab = $tab;
        $this->operator = $operator;
    }

    public function sheets(): array
    {
        $query = NsrdiOverdue::query();

        if ($this->tab === 'open') {
            $query->whereRaw('LOWER(status) != ?', ['closed']);
        } elseif ($this->tab === 'closed') {
            $query->whereRaw('LOWER(status) = ?', ['closed']);
        }

        if ($this->operator) {
            $query->where('operator', $this->operator);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('mddr', 'like', '%'.$this->search.'%')
                    ->orWhere('part_number', 'like', '%'.$this->search.'%');
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
                'due_date' => $r->due_date ? (is_string($r->due_date) ? substr($r->due_date, 0, 10) : $r->due_date->format('Y-m-d')) : '-',
                'month_due' => $r->month_due ?: '-',
                'description' => $r->description ?: '-',
                'part_number' => $r->part_number ?: '-',
                'status_final' => $r->status_final ?: '-',
                'remarks' => $r->remarks ?: '-',
                'closed_at' => $r->closed_at ? (is_string($r->closed_at) ? substr($r->closed_at, 0, 19) : $r->closed_at->format('Y-m-d H:i:s')) : '-',
                // days to the due date (negative = overdue); only for what is still open
                'days_to_due' => ($r->due_date && strtolower((string) $r->status) !== 'closed') ? (int) now()->startOfDay()->diffInDays(Carbon::parse($r->due_date)->startOfDay(), false) : '-',
                'closed_via' => ['daily_report' => 'Daily Report', 'bank' => 'Bank Data', 'file' => 'File', 'manual' => 'Manual'][$r->closed_via] ?? ($r->closed_via ?: '-'),
                'bank_ref' => $r->bank_ref ?: '-',
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
            'Kategori Tab' => ucfirst($this->tab),
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
            'Due Date',
            'Month Due',
            'Defect Description',
            'Part Number',
            'Status Final',
            'Remarks',
            'Closed At',
            'Sisa Hari ke Due Date (minus = terlambat)',
            'Ditutup Via',
            'Cocok Dengan (No NSRDI Bank)',
        ];

        $sheets[] = new DetailDataSheet('Detail NSRDI Overdue', $headings, $mapped);

        return $sheets;
    }
}
