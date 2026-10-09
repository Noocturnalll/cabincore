<?php

namespace App\Exports;

use App\Models\CmlLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CmlExport implements FromCollection, WithHeadings
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

        // We return the raw collection, omitting standard eloquent hidden fields
        // Exclude some internal fields if necessary, or just return all
        return $query->orderBy('id', 'desc')->get()->map(function ($item) {
            return $item->makeHidden(['id', 'dja_id', 'created_at', 'updated_at']);
        });
    }

    public function headings(): array
    {
        // Headings will be dynamically generated based on the first row's keys, or just left generic
        // A simple trick if dynamic headings fail is to fetch a dummy instance
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
