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

class PaintingDailyReportExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    public function __construct(
        protected string $search = '',
        protected string $dateFilter = '',
        protected string $activeTab = 'dja-painting'
    ) {
        $this->activeTab = $this->activeTab ?: 'dja-painting';
    }

    public function sheets(): array
    {
        $data = $this->collection();

        $metadata = [
            'Modul' => 'Daily Report - Aircraft Painting',
            'Total Record' => number_format($data->count()).' items',
            'Tab / Tipe' => strtoupper($this->activeTab),
            'Filter Tanggal' => $this->dateFilter ?: 'Default (Sebelum Tanggal Aktif)',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        $matrixStation = PivotBuilder::buildMatrix(
            $data,
            'plan_station',
            'status',
            ['Open', 'Closed'],
            'Station / STA',
            '1. Matriks Rekapitulasi: Station vs Status (Aircraft Painting)'
        );

        $matrixAoc = PivotBuilder::buildMatrix(
            $data,
            'aoc',
            'status',
            ['Open', 'Closed'],
            'AOC / Maskapai',
            '2. Matriks Rekapitulasi: AOC vs Status (Aircraft Painting)'
        );

        $tables = [$matrixStation, $matrixAoc];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI DAILY REPORT AIRCRAFT PAINTING', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail Painting'),
        ];
    }

    public function collection(): Collection
    {
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');
        $query = NsrdiLog::where('category', 'PAINTING')->where('is_submitted', true);

        if ($this->activeTab === 'dja-painting') {
            $query->where(function ($q) use ($activeDate) {
                $q->where(function ($x) use ($activeDate) {
                    $x->whereNotNull('dja_id')->whereHas('dailyJobAssignment', function ($d) use ($activeDate) {
                        $d->whereDate('date', '<', $activeDate);
                    });
                })->orWhere(function ($x) use ($activeDate) {
                    $x->whereNull('dja_id')->where('import_source', 'dja')->whereDate('plan_date', '<', $activeDate);
                });
            });

            if ($this->dateFilter) {
                $query->where(function ($q) {
                    $q->whereHas('dailyJobAssignment', function ($d) {
                        $d->whereDate('date', $this->dateFilter);
                    })->orWhere(function ($x) {
                        $x->whereNull('dja_id')->where('import_source', 'dja')->whereDate('plan_date', $this->dateFilter);
                    });
                });
            }
        } elseif ($this->activeTab === 'unplanned-painting') {
            $query->whereNull('dja_id')
                ->where(function ($q) {
                    $q->whereNull('import_source')->orWhere('import_source', 'unplanned');
                })
                ->whereRaw('DATE(COALESCE(plan_date, report_date)) < ?', [$activeDate]);

            if ($this->dateFilter) {
                $query->whereRaw('DATE(COALESCE(plan_date, report_date)) = ?', [$this->dateFilter]);
            }
        } else {
            // All archived painting
            if ($this->dateFilter) {
                $query->where(function ($q) {
                    $q->whereDate('plan_date', $this->dateFilter)
                        ->orWhereDate('report_date', $this->dateFilter)
                        ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $this->dateFilter));
                });
            } else {
                $query->whereRaw('DATE(COALESCE(plan_date, report_date)) < ?', [$activeDate]);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                    ->orWhere('act_station', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('aoc', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderByRaw('COALESCE(plan_date, report_date) DESC')->orderByDesc('id')->get()->map(function ($item) {
            return [
                'plan_date' => $item->plan_date ? $item->plan_date->format('Y-m-d') : ($item->report_date ? $item->report_date->format('Y-m-d') : null),
                'aircraft_registration' => $item->aircraft_registration,
                'nsrdi_number' => $item->nsrdi_number,
                'description' => $item->description,
                'plan_station' => $item->plan_station ?: ($item->act_station ?: 'N/A'),
                'act_station' => $item->act_station,
                'aoc' => $item->aoc,
                'status' => $item->status,
                'code_reason' => $item->hold_reason_category ?? ($item->code_open ?: 'None'),
                'reason_open' => $item->hold_remarks ?? $item->reason_open,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'PLAN DATE',
            'A/C REG',
            'NSRDI NUMBER',
            'FINDING DESCRIPTION',
            'PLAN STA',
            'ACT STA',
            'AOC',
            'STATUS',
            'CODE REASON',
            'REASON OPEN',
        ];
    }
}
