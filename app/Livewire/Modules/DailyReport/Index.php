<?php

namespace App\Livewire\Modules\DailyReport;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DailyReportImport;
use App\Models\WoLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\CmlLog;

class Index extends Component
{
    use WithPagination, WithFileUploads;

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
            session()->flash('success', 'Data Daily Report berhasil diimport.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: ' . $e->getMessage());
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $logs = [];
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        switch ($this->activeTab) {
            case 'dja-wo':
                $query = WoLog::whereNotNull('dja_id')->whereHas('dailyJobAssignment', function($q) use ($activeDate) {
                    $q->whereDate('date', '<', $activeDate);
                });
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('wo_number', 'like', '%' . $this->search . '%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereHas('dailyJobAssignment', function($q) {
                        $q->whereDate('date', $this->dateFilter);
                    });
                }
                $logs = $query->latest()->get();
                break;
            case 'unplanned-wo':
                $query = WoLog::whereNull('dja_id')->whereDate('date', '<', $activeDate);
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('wo_number', 'like', '%' . $this->search . '%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereDate('date', $this->dateFilter);
                }
                $logs = $query->orderBy('date', 'desc')->get();
                break;
            case 'dja-dmi':
                $query = DmiLog::whereNotNull('dja_id')->whereHas('dailyJobAssignment', function($q) use ($activeDate) {
                    $q->whereDate('date', '<', $activeDate);
                });
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('dmi_number', 'like', '%' . $this->search . '%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereHas('dailyJobAssignment', function($q) {
                        $q->whereDate('date', $this->dateFilter);
                    });
                }
                $logs = $query->latest()->get();
                break;
            case 'unplanned-dmi':
                $query = DmiLog::whereNull('dja_id')->whereDate('date', '<', $activeDate);
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('dmi_number', 'like', '%' . $this->search . '%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereDate('date', $this->dateFilter);
                }
                $logs = $query->orderBy('date', 'desc')->get();
                break;
            case 'dja-nsrdi':
                $query = NsrdiLog::whereNotNull('dja_id')->whereHas('dailyJobAssignment', function($q) use ($activeDate) {
                    $q->whereDate('date', '<', $activeDate);
                });
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('nsrdi_number', 'like', '%' . $this->search . '%');
                    });
                }
                if ($this->dateFilter) {
                    $query->whereHas('dailyJobAssignment', function($q) {
                        $q->whereDate('date', $this->dateFilter);
                    });
                }
                $logs = $query->latest()->get();
                break;
            case 'unplanned-nsrdi':
                $query = NsrdiLog::whereNull('dja_id')->whereDate('report_date', '<', $activeDate);
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('nsrdi_number', 'like', '%' . $this->search . '%');
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
                    $query->where(function($q) {
                        $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                          ->orWhere('status', 'like', '%' . $this->search . '%');
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
