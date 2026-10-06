<?php

namespace App\Exports;

use App\Models\CmlLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DailyReportExport implements FromCollection, WithHeadings
{
    protected $search;

    protected $dateFilter;

    protected $activeTab;

    public function __construct($search = '', $dateFilter = '', $activeTab = 'dja-wo')
    {
        $this->search = $search;
        $this->dateFilter = $dateFilter;
        $this->activeTab = $activeTab ?: 'dja-wo';
    }

    public function collection(): Collection
    {
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        switch ($this->activeTab) {
            case 'dja-wo':
                $query = WoLog::whereNotNull('dja_id')->where('is_submitted', true)->whereHas('dailyJobAssignment', function ($q) use ($activeDate) {
                    $q->whereDate('date', '<', $activeDate);
                });
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('wo_number', 'like', '%'.$this->search.'%')
                            ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                            ->orWhere('act_station', 'like', '%'.$this->search.'%')
                            ->orWhere('operator', 'like', '%'.$this->search.'%')
                            ->orWhere('work_group', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereHas('dailyJobAssignment', function ($q) {
                        $q->whereDate('date', $this->dateFilter);
                    });
                }

                return $query->latest()->get()->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'registration' => $item->aircraft_registration,
                        'wo_number' => $item->wo_number,
                        'work_group' => $item->work_group,
                        'description' => $item->description,
                        'plan_station' => $item->plan_station,
                        'act_station' => $item->act_station,
                        'operator' => $item->operator,
                        'status' => $item->status,
                    ];
                });

            case 'unplanned-wo':
                $query = WoLog::whereNull('dja_id')->where('is_submitted', true)->whereDate('date', '<', $activeDate);
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('wo_number', 'like', '%'.$this->search.'%')
                            ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                            ->orWhere('act_station', 'like', '%'.$this->search.'%')
                            ->orWhere('operator', 'like', '%'.$this->search.'%')
                            ->orWhere('work_group', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereDate('date', $this->dateFilter);
                }

                return $query->orderBy('date', 'desc')->get()->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'registration' => $item->aircraft_registration,
                        'wo_number' => $item->wo_number,
                        'work_group' => $item->work_group,
                        'description' => $item->description,
                        'plan_station' => $item->plan_station,
                        'act_station' => $item->act_station,
                        'operator' => $item->operator,
                        'status' => $item->status,
                    ];
                });

            case 'dja-dmi':
                $query = DmiLog::whereNotNull('dja_id')->where('is_submitted', true)->whereHas('dailyJobAssignment', function ($q) use ($activeDate) {
                    $q->whereDate('date', '<', $activeDate);
                });
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('dmi_number', 'like', '%'.$this->search.'%')
                            ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                            ->orWhere('act_station', 'like', '%'.$this->search.'%')
                            ->orWhere('category', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereHas('dailyJobAssignment', function ($q) {
                        $q->whereDate('date', $this->dateFilter);
                    });
                }

                return $query->latest()->get()->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'registration' => $item->aircraft_registration,
                        'dmi_number' => $item->dmi_number,
                        'description' => $item->description,
                        'plan_station' => $item->plan_station,
                        'act_station' => $item->act_station,
                        'category' => $item->category,
                        'status' => $item->status,
                    ];
                });

            case 'unplanned-dmi':
                $query = DmiLog::whereNull('dja_id')->where('is_submitted', true)->whereDate('date', '<', $activeDate);
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('dmi_number', 'like', '%'.$this->search.'%')
                            ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                            ->orWhere('act_station', 'like', '%'.$this->search.'%')
                            ->orWhere('category', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereDate('date', $this->dateFilter);
                }

                return $query->orderBy('date', 'desc')->get()->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'registration' => $item->aircraft_registration,
                        'dmi_number' => $item->dmi_number,
                        'description' => $item->description,
                        'plan_station' => $item->plan_station,
                        'act_station' => $item->act_station,
                        'category' => $item->category,
                        'status' => $item->status,
                    ];
                });

            case 'dja-nsrdi':
                $query = NsrdiLog::where('is_submitted', true)->where(function ($q) use ($activeDate) {
                    $q->where(function ($x) use ($activeDate) {
                        $x->whereNotNull('dja_id')->whereHas('dailyJobAssignment', function ($d) use ($activeDate) {
                            $d->whereDate('date', '<', $activeDate);
                        });
                    })->orWhere(function ($x) use ($activeDate) {
                        $x->whereNull('dja_id')->where('import_source', 'dja')->whereDate('plan_date', '<', $activeDate);
                    });
                });
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                            ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                            ->orWhere('act_station', 'like', '%'.$this->search.'%')
                            ->orWhere('category', 'like', '%'.$this->search.'%')
                            ->orWhere('aoc', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->where(function ($q) {
                        $q->whereHas('dailyJobAssignment', function ($d) {
                            $d->whereDate('date', $this->dateFilter);
                        })->orWhere(function ($x) {
                            $x->whereNull('dja_id')->where('import_source', 'dja')->whereDate('plan_date', $this->dateFilter);
                        });
                    });
                }

                return $query->orderByRaw('COALESCE(plan_date, created_at) DESC')->orderBy('id')->get()->map(function ($item) {
                    return [
                        'plan_date' => $item->plan_date,
                        'registration' => $item->aircraft_registration,
                        'nsrdi_number' => $item->nsrdi_number,
                        'description' => $item->description,
                        'plan_station' => $item->plan_station,
                        'act_station' => $item->act_station,
                        'category' => $item->category,
                        'aoc' => $item->aoc,
                        'status' => $item->status,
                    ];
                });

            case 'unplanned-nsrdi':
                $query = NsrdiLog::whereNull('dja_id')->where('is_submitted', true)
                    ->where(function ($q) {
                        $q->whereNull('import_source')->orWhere('import_source', 'unplanned');
                    })
                    ->whereRaw('DATE(COALESCE(plan_date, report_date)) < ?', [$activeDate]);
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                            ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                            ->orWhere('act_station', 'like', '%'.$this->search.'%')
                            ->orWhere('category', 'like', '%'.$this->search.'%')
                            ->orWhere('aoc', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereRaw('DATE(COALESCE(plan_date, report_date)) = ?', [$this->dateFilter]);
                }

                return $query->orderByRaw('COALESCE(plan_date, report_date) DESC')->orderBy('id')->get()->map(function ($item) {
                    return [
                        'date' => $item->plan_date ?? $item->report_date,
                        'registration' => $item->aircraft_registration,
                        'nsrdi_number' => $item->nsrdi_number,
                        'description' => $item->description,
                        'plan_station' => $item->plan_station,
                        'act_station' => $item->act_station,
                        'category' => $item->category,
                        'aoc' => $item->aoc,
                        'status' => $item->status,
                    ];
                });

            case 'cml':
            default:
                $query = CmlLog::whereDate('date', '<', $activeDate);
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                            ->orWhere('status', 'like', '%'.$this->search.'%')
                            ->orWhere('doc_type', 'like', '%'.$this->search.'%')
                            ->orWhere('no_doc', 'like', '%'.$this->search.'%')
                            ->orWhere('station', 'like', '%'.$this->search.'%')
                            ->orWhere('operator', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereDate('date', $this->dateFilter);
                }

                return $query->orderBy('date', 'desc')->get()->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'registration' => $item->aircraft_registration,
                        'doc_type' => $item->doc_type,
                        'no_doc' => $item->no_doc,
                        'station' => $item->station,
                        'operator' => $item->operator,
                        'status' => $item->status,
                        'description' => $item->description,
                    ];
                });
        }
    }

    public function headings(): array
    {
        switch ($this->activeTab) {
            case 'dja-wo':
            case 'unplanned-wo':
                return ['Date', 'Aircraft Registration', 'WO Number', 'Work Group', 'Description', 'Plan Station', 'Act Station', 'Operator', 'Status'];
            case 'dja-dmi':
            case 'unplanned-dmi':
                return ['Date', 'Aircraft Registration', 'DMI Number', 'Description', 'Plan Station', 'Act Station', 'Category', 'Status'];
            case 'dja-nsrdi':
            case 'unplanned-nsrdi':
                return ['Date', 'Aircraft Registration', 'NSRDI Number', 'Description', 'Plan Station', 'Act Station', 'Category', 'AOC', 'Status'];
            case 'cml':
            default:
                return ['Date', 'Aircraft Registration', 'Doc Type', 'No Doc', 'Station', 'Operator', 'Status', 'Description'];
        }
    }
}
