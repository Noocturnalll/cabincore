<?php

namespace App\Exports;

use App\Models\NsrdiLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NsrdiExport implements FromCollection, WithHeadings
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
        $query = NsrdiLog::query();

        if ($this->search) {
            $query->where(function ($q) {
                // Adjust search fields as necessary, default to aircraft_registration
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%');
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
                'plan_station' => $item->plan_station,
                'remarks' => $item->remarks,
                'status' => $item->status,
                'close_date' => $item->close_date,
                'act_station' => $item->act_station,
                'code_reason' => $item->hold_reason_category ?? $item->code_open,
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
