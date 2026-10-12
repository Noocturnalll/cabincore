<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\DmiLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DmiExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    protected $search;

    protected $dateFilter;

    protected $activeTab; // For planned/unplanned if applicable

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
            'Total Record' => number_format($data->count()).' DMI items',
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
            '1. Matriks Rekapitulasi: Station vs Status DMI'
        );

        // 2. Breakdown: Kategori DMI
        $breakdownCategory = PivotBuilder::buildBreakdown(
            $data,
            'category',
            'Kategori DMI',
            '2. Rekapitulasi per Kategori DMI'
        );

        // 3. Breakdown: Part Number Required / Hold
        $breakdownHold = PivotBuilder::buildBreakdown(
            $data,
            'hold_reason_category',
            'Alasan Hold / Kategori',
            '3. Rekapitulasi Alasan Hold'
        );

        $tables = [$matrixStation, $breakdownCategory, $breakdownHold];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT DMI LOGS', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail DMI'),
        ];
    }

    public function collection(): Collection
    {
        $query = DmiLog::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('dmi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        if ($this->activeTab) {
            if ($this->activeTab === 'planned') {
                $query->whereNotNull('dja_id');
            } elseif ($this->activeTab === 'unplanned') {
                $query->whereNull('dja_id');
            }
        }

        return $query->orderBy('id', 'desc')->get()->map(function ($item) {
            return $item->makeHidden(['id', 'dja_id', 'created_at', 'updated_at']);
        });
    }

    public function headings(): array
    {
        $dummy = new DmiLog;
        $hidden = ['id', 'dja_id', 'created_at', 'updated_at'];
        $keys = array_diff(array_keys($dummy->getAttributes() ?: (\Schema::getColumnListing($dummy->getTable()))), $hidden);

        $headings = [];
        foreach ($keys as $key) {
            $headings[] = strtoupper(str_replace('_', ' ', $key));
        }

        return $headings;
    }
}
