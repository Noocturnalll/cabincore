<?php

namespace App\Livewire\Traits;

trait WithAdvancedFilter
{
    public $search = '';

    public $dateStart = '';

    public $dateEnd = '';

    public $filterStation = '';

    public $sortField = 'date';

    public $sortDirection = 'desc';

    public function mountWithAdvancedFilter()
    {
        $this->dateStart = now()->subDays(7)->format('Y-m-d');
        $this->dateEnd = now()->format('Y-m-d');
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStation = '';
        $this->dateStart = now()->subDays(7)->format('Y-m-d');
        $this->dateEnd = now()->format('Y-m-d');
        $this->sortField = 'date';
        $this->sortDirection = 'desc';
    }

    public function scopeAdvancedFilter($query, $searchFields = [])
    {
        // 1. Date Filtering
        if ($this->dateStart && $this->dateEnd) {
            $query->whereBetween('date', [$this->dateStart, $this->dateEnd]);
        } elseif ($this->dateStart) {
            $query->whereDate('date', '>=', $this->dateStart);
        } elseif ($this->dateEnd) {
            $query->whereDate('date', '<=', $this->dateEnd);
        }

        // 2. Station Filtering
        if ($this->filterStation) {
            $query->where('station', $this->filterStation);
        }

        // 3. Global Search (Ctrl+F like)
        if ($this->search && ! empty($searchFields)) {
            $query->where(function ($q) use ($searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', '%'.$this->search.'%');
                }
            });
        }

        // 4. Sorting
        $allowedSorts = ['date', 'aircraft_registration', 'station', 'created_at'];
        if (in_array($this->sortField, $allowedSorts)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }
}
