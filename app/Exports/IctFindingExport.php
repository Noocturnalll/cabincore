<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\IctFinding;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class IctFindingExport implements Export, WithMultipleSheets
{
    protected $search;

    protected $dateFilter;

    protected $status;

    public function __construct($search = '', $dateFilter = '', $status = '')
    {
        $this->status = $status;
        $this->search = $search;
        $this->dateFilter = $dateFilter;
    }

    public function sheets(): array
    {
        $query = IctFinding::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('no_finding', 'like', '%'.$this->search.'%')
                    ->orWhere('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('defect_description', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        if (in_array($this->status, ['Open', 'Closed'], true)) {
            $query->where('status', $this->status);
        }

        $records = $query->orderBy('date', 'desc')->get();

        $mapped = $records->map(function ($r) {
            return [
                'date' => $r->date ? (is_string($r->date) ? substr($r->date, 0, 10) : $r->date->format('Y-m-d')) : '-',
                'no_finding' => $r->no_finding ?: '-',
                'operator' => $r->operator ?: 'LION AIR',
                'aircraft_registration' => $r->aircraft_registration ?: '-',
                'defect_description' => $r->defect_description ?: '-',
                'remarks' => $r->remarks ?: '-',
                'status' => $r->status ?: 'Open',
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

        // 2. Breakdown: Operator
        $breakdownOperator = PivotBuilder::breakdown(
            $mapped,
            'operator',
            'Operator Penerbangan'
        );

        // 3. Summary Status Finding
        $openCount = $records->filter(fn ($r) => strcasecmp($r->status, 'Open') === 0)->count();
        $closedCount = $records->filter(fn ($r) => strcasecmp($r->status, 'Closed') === 0)->count();

        $summaryRows = [
            ['Total Temuan (Findings)', (string) $records->count(), '100%'],
            ['Status Open (Menunggu Perbaikan)', (string) $openCount, $records->count() ? round(($openCount / $records->count()) * 100, 1).'%' : '0%'],
            ['Status Closed (Selesai)', (string) $closedCount, $records->count() ? round(($closedCount / $records->count()) * 100, 1).'%' : '0%'],
        ];

        $tables = [
            [
                'title' => '1. Rekapitulasi Operator vs Status Finding ICT',
                'headers' => $matrixOperatorStatus['headers'],
                'rows' => $matrixOperatorStatus['rows'],
                'totalRow' => $matrixOperatorStatus['totalRow'],
            ],
            [
                'title' => '2. Distribusi Temuan per Operator',
                'headers' => $breakdownOperator['headers'],
                'rows' => $breakdownOperator['rows'],
                'totalRow' => $breakdownOperator['totalRow'],
            ],
            [
                'title' => '3. Ringkasan Status Penyelesaian Finding',
                'headers' => ['Status Finding', 'Jumlah Temuan', 'Rasio (%)'],
                'rows' => $summaryRows,
            ],
        ];

        $metadata = [
            'Total Data' => (string) $records->count(),
            'Filter Pencarian' => $this->search ?: 'Semua',
            'Filter Tanggal' => $this->dateFilter ?: 'Semua Tanggal',
            'Filter Status' => $this->status ?: 'Semua Status',
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN REKAPITULASI ICT FINDINGS', $metadata, $tables),
        ];

        $headings = [
            'Date',
            'No Finding',
            'Operator',
            'Aircraft Registration',
            'Defect Description',
            'Remarks',
            'Status',
        ];

        $sheets[] = new DetailDataSheet('Detail ICT Findings', $headings, $mapped);

        return $sheets;
    }
}
