<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\CmlLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CmlExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    protected $search;

    protected $dateFilter;

    protected $activeTab; // For planned/unplanned if applicable

    protected $dateEnd;

    protected $station;

    public function __construct($search = '', $dateFilter = '', $activeTab = '', $dateEnd = '', $station = '')
    {
        $this->dateEnd = $dateEnd;
        $this->station = $station;
        $this->search = $search;
        $this->dateFilter = $dateFilter;
        $this->activeTab = $activeTab;
    }

    public function sheets(): array
    {
        $data = $this->collection();

        $metadata = [
            'Total Record' => number_format($data->count()).' logs',
            'Filter Tanggal' => ($this->dateFilter || $this->dateEnd) ? (($this->dateFilter ?: '-').' s/d '.($this->dateEnd ?: '-')) : 'Default (Hari Ini / Kemarin)',
            'Filter Station' => $this->station ?: 'Semua Station',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Pivot Matrix: Station x Status
        $matrixStation = PivotBuilder::buildMatrix(
            $data,
            'station',
            'status',
            ['Open', 'Closed'],
            'Station / Bandara',
            '1. Matriks Rekapitulasi: Station vs Status Pekerjaan'
        );

        // 2. Breakdown: Operator / Maskapai
        $breakdownOperator = PivotBuilder::buildBreakdown(
            $data,
            'operator',
            'Operator / Maskapai',
            '2. Rekapitulasi per Operator / Maskapai'
        );

        // 3. Breakdown: Document Type
        $breakdownDocType = PivotBuilder::buildBreakdown(
            $data,
            'doc_type',
            'Jenis Dokumen',
            '3. Rekapitulasi per Jenis Dokumen'
        );

        $tables = [$matrixStation, $breakdownOperator, $breakdownDocType];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT CML LOGS', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail CML'),
        ];
    }

    public function collection(): Collection
    {
        $query = CmlLog::query();

        if ($this->dateEnd || $this->dateFilter) {
            // Range chosen on screen: dateFilter acts as the start date
            $from = $this->dateFilter ?: $this->dateEnd;
            $to = $this->dateEnd ?: $this->dateFilter;
            $query->whereBetween('date', [$from, $to]);
        } else {
            $filterDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');
            $query->whereDate('date', $filterDate);
        }

        if ($this->station) {
            $query->where('station', $this->station);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('doc_type', 'like', '%'.$this->search.'%')
                    ->orWhere('no_doc', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('ac_status', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
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
        $dummy = new CmlLog;
        $hidden = ['id', 'dja_id', 'created_at', 'updated_at'];
        $keys = array_diff(array_keys($dummy->getAttributes() ?: (\Schema::getColumnListing($dummy->getTable()))), $hidden);

        $headings = [];
        foreach ($keys as $key) {
            $headings[] = strtoupper(str_replace('_', ' ', $key));
        }

        return $headings;
    }
}
