<?php

namespace App\Exports;

use App\Models\DailyJobAssignment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DjaExport implements FromCollection, WithHeadings
{
    protected $search;

    protected $dateFilter;

    protected $activeTab;

    public function __construct($search = '', $dateFilter = '', $activeTab = '')
    {
        $this->search = $search;
        $this->dateFilter = $dateFilter;
        $this->activeTab = $activeTab;
    }

    public function collection(): Collection
    {
        $query = DailyJobAssignment::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('task_id', 'like', '%'.$this->search.'%')
                    ->orWhere('job_type', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        return $query->latest('date')->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'registration' => $item->aircraft_registration,
                'task_id' => $item->task_id,
                'job_type' => $item->job_type,
                'description' => $item->description,
                'station' => $item->station,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Aircraft Registration',
            'Task ID',
            'Job Type',
            'Description',
            'Station',
        ];
    }
}
