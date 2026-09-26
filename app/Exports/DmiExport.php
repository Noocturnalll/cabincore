<?php

namespace App\Exports;

use App\Models\DmiLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DmiExport implements FromCollection, WithHeadings
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

    public function collection(): Collection
    {
        $query = DmiLog::query();

        if ($this->search) {
            $query->where(function ($q) {
                // Adjust search fields as necessary, default to aircraft_registration
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter); // Adjust date column if needed
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
