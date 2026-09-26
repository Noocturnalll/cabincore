<?php

namespace App\Livewire\Modules\DailyReport;

use App\Exports\DailyReportExport;
use App\Imports\DailyReportImport;
use App\Models\CmlLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public $activeTab = 'dja-wo';

    public $importFile;

    public $search = '';

    public $dateFilter = '';

    public function importExcel()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new DailyReportImport, $this->importFile);
            $this->importFile = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data Daily Report berhasil diimport.']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data Daily Report berhasil diimport.']));
        } catch (\Exception $e) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function exportExcel()
    {
        $search = property_exists($this, 'search') ? $this->search : '';
        $dateFilter = property_exists($this, 'dateFilter') ? $this->dateFilter : '';
        $activeTab = property_exists($this, 'activeTab') ? $this->activeTab : '';

        return Excel::download(new DailyReportExport($search, $dateFilter, $activeTab), 'DailyReportExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $logs = [];
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        switch ($this->activeTab) {
            case 'dja-wo':
                $query = WoLog::whereNotNull('dja_id')->whereHas('dailyJobAssignment', function ($q) use ($activeDate) {
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
                $logs = $query->latest()->get();
                break;
            case 'unplanned-wo':
                $query = WoLog::whereNull('dja_id')->whereDate('date', '<', $activeDate);
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
                $logs = $query->orderBy('date', 'desc')->get();
                break;
            case 'dja-dmi':
                $query = DmiLog::whereNotNull('dja_id')->whereHas('dailyJobAssignment', function ($q) use ($activeDate) {
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
                $logs = $query->latest()->get();
                break;
            case 'unplanned-dmi':
                $query = DmiLog::whereNull('dja_id')->whereDate('date', '<', $activeDate);
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
                $logs = $query->orderBy('date', 'desc')->get();
                break;
            case 'dja-nsrdi':
                $query = NsrdiLog::whereNotNull('dja_id')->whereHas('dailyJobAssignment', function ($q) use ($activeDate) {
                    $q->whereDate('date', '<', $activeDate);
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
                    $query->whereHas('dailyJobAssignment', function ($q) {
                        $q->whereDate('date', $this->dateFilter);
                    });
                }
                $logs = $query->latest()->get();
                break;
            case 'unplanned-nsrdi':
                $query = NsrdiLog::whereNull('dja_id')->whereDate('report_date', '<', $activeDate);
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
                    $query->whereDate('report_date', $this->dateFilter);
                }
                $logs = $query->orderBy('report_date', 'desc')->get();
                break;
            case 'cml':
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
                $logs = $query->orderBy('date', 'desc')->get();
                break;
        }

        return view('livewire.modules.daily-report.index', [
            'logs' => $logs,
        ])->layout('components.layouts.app', ['title' => 'Daily Report']);
    }
}
