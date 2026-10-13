<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\NsrdiLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NsrdiExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    protected $search;

    protected $dateFilter;

    protected $activeTab; // For planned/unplanned if applicable

    protected $category;

    public function __construct($search = '', $dateFilter = '', $activeTab = '', $category = 'CBM')
    {
        $this->search = $search;
        $this->dateFilter = $dateFilter;
        $this->activeTab = $activeTab;
        $this->category = $category;
    }

    public function sheets(): array
    {
        $data = $this->collection();

        $catLabel = $this->category === 'PAINTING' ? 'Aircraft Painting' : 'Cabin Maintenance';

        $metadata = [
            'Kategori' => $catLabel,
            'Total Record' => number_format($data->count()).' NSRDI items',
            'Filter Tanggal' => $this->dateFilter ?: 'Semua Periode',
            'Filter Tab' => $this->activeTab ? strtoupper($this->activeTab) : 'SEMUA',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Pivot Matrix: Station x Status
        $matrixStation = PivotBuilder::buildMatrix(
            $data,
            'plan_station',
            'status',
            ['Open', 'Closed'],
            'Station / STA',
            '1. Matriks Rekapitulasi: Station vs Status NSRDI'
        );

        // 2. Pivot Matrix: AOC / Airline x Status
        $matrixAoc = PivotBuilder::buildMatrix(
            $data,
            'aoc',
            'status',
            ['Open', 'Closed'],
            'AOC / Maskapai',
            '2. Matriks Rekapitulasi: AOC Maskapai vs Status'
        );

        // 3. Breakdown: Alasan Hold / Open Code
        $breakdownReason = PivotBuilder::buildBreakdown(
            $data,
            'code_reason',
            'Kode Alasan (Hold / Open)',
            '3. Rekapitulasi Kode Alasan / Kategori Hold (NS, Spare, dll)'
        );

        $tables = [$matrixStation, $matrixAoc, $breakdownReason];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT NSRDI ('.strtoupper($catLabel).')', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail NSRDI'),
        ];
    }

    public function collection(): Collection
    {
        $query = NsrdiLog::query();

        if ($this->category === 'PAINTING') {
            $query->where('category', 'PAINTING');
        } else {
            $query->where(function ($q) {
                $q->where('category', 'CBM')
                    ->orWhereNull('category')
                    ->orWhere('category', '!=', 'PAINTING');
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->where(function ($q) {
                $q->whereDate('plan_date', $this->dateFilter)
                    ->orWhereDate('report_date', $this->dateFilter)
                    ->orWhereHas('dailyJobAssignment', function ($q2) {
                        $q2->whereDate('date', $this->dateFilter);
                    });
            });
        }

        if ($this->activeTab) {
            if ($this->activeTab === 'planned') {
                $query->whereNotNull('dja_id');
            } elseif ($this->activeTab === 'unplanned') {
                $query->whereNull('dja_id');
            } elseif ($this->activeTab === 'nospare') {
                $query->where(function ($q) {
                    $q->where('hold_reason_category', 'NS')
                        ->orWhere('code_open', 'NS');
                });
            }
        }

        return $query->orderBy('id', 'desc')->get()->map(function ($item) {
            $planDate = $item->dailyJobAssignment
                ? $item->dailyJobAssignment->date
                : $item->plan_date;

            return [
                'plan_date' => $planDate,
                'work_group' => $item->work_group,
                'aircraft_registration' => $item->aircraft_registration,
                'nsrdi_number' => $item->nsrdi_number,
                'description' => $item->description,
                'category' => $item->category,
                'report_date' => $item->report_date,
                'due_date' => $item->due_date,
                'part_number' => $item->part_number,
                'part_description' => $item->part_description,
                'defer' => $item->defer,
                'aoc' => $item->aoc,
                'type' => $item->type,
                'plan_station' => $item->plan_station ?: ($item->act_station ?: 'N/A'),
                'remarks' => $item->remarks,
                'status' => $item->status,
                'close_date' => $item->close_date,
                'act_station' => $item->act_station,
                'code_reason' => $item->hold_reason_category ?? ($item->code_open ?: 'None'),
                'reason_open' => $item->hold_remarks ?? $item->reason_open,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'PLAN DATE',
            'WORK GROUP',
            'A/C REG',
            'NSRDI NUMBER',
            'FINDING DESCRIPTION',
            'CATEGORY',
            'REPORT DATE',
            'DUE DATE',
            'PART NUMBER',
            'PART DESCRIPTION',
            'DEFER',
            'AOC',
            'TYPE',
            'PLAN STA',
            'REMARKS',
            'STATUS',
            'CLOSE DATE',
            'ACT STA',
            'CODE REASON',
            'REASON OPEN',
        ];
    }
}
