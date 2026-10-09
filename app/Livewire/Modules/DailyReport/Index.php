<?php

namespace App\Livewire\Modules\DailyReport;

use App\Exports\DailyReportExport;
use App\Imports\DailyReportImport;
use App\Livewire\Traits\WithLogTable;
use App\Models\Airport;
use App\Models\CmlLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Notifications\SystemNotification;
use App\Services\Dja\DailyReportArchiver;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithLogTable, WithPagination;

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
                $logs = $query->latest()->paginate($this->perPage);
                break;
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
                $logs = $query->orderBy('date', 'desc')->orderByDesc('id')->paginate($this->perPage);
                break;
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
                $logs = $query->latest()->paginate($this->perPage);
                break;
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
                $logs = $query->orderBy('date', 'desc')->orderByDesc('id')->paginate($this->perPage);
                break;
            case 'dja-nsrdi':
                // Data dari modul DJA (dja_id terisi) ATAU hasil import sheet DJA (import_source = 'dja').
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
                $logs = $query->orderByRaw('COALESCE(plan_date, created_at) DESC')->orderBy('id')->paginate($this->perPage);
                break;
            case 'unplanned-nsrdi':
                // Tanggal unplanned = plan_date (hasil import) atau report_date (data manual lama).
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
                $logs = $query->orderByRaw('COALESCE(plan_date, report_date) DESC')->orderBy('id')->paginate($this->perPage);
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
                $logs = $query->orderBy('date', 'desc')->orderByDesc('id')->paginate($this->perPage);
                break;
            case 'summary':
                // Base filter logic matching other tabs
                $woQuery = WoLog::where('is_submitted', true);
                $dmiQuery = DmiLog::where('is_submitted', true);
                $nsrdiQuery = NsrdiLog::where('is_submitted', true);
                $cmlQuery = CmlLog::query();

                // Adjust for date boundaries (same as other tabs: before activeDate, or exact dateFilter)
                if ($this->dateFilter) {
                    $woQuery->where(function ($q) {
                        $q->whereHas('dailyJobAssignment', function ($d) {
                            $d->whereDate('date', $this->dateFilter);
                        })
                            ->orWhereDate('date', $this->dateFilter);
                    });
                    $dmiQuery->where(function ($q) {
                        $q->whereHas('dailyJobAssignment', function ($d) {
                            $d->whereDate('date', $this->dateFilter);
                        })
                            ->orWhereDate('date', $this->dateFilter);
                    });
                    $nsrdiQuery->where(function ($q) {
                        $q->whereHas('dailyJobAssignment', function ($d) {
                            $d->whereDate('date', $this->dateFilter);
                        })
                            ->orWhereDate('plan_date', $this->dateFilter)
                            ->orWhereDate('report_date', $this->dateFilter);
                    });
                    $cmlQuery->whereDate('date', $this->dateFilter);
                } else {
                    $woQuery->where(function ($q) use ($activeDate) {
                        $q->whereHas('dailyJobAssignment', function ($d) use ($activeDate) {
                            $d->whereDate('date', '<', $activeDate);
                        })
                            ->orWhereDate('date', '<', $activeDate);
                    });
                    $dmiQuery->where(function ($q) use ($activeDate) {
                        $q->whereHas('dailyJobAssignment', function ($d) use ($activeDate) {
                            $d->whereDate('date', '<', $activeDate);
                        })
                            ->orWhereDate('date', '<', $activeDate);
                    });
                    $nsrdiQuery->where(function ($q) use ($activeDate) {
                        $q->whereHas('dailyJobAssignment', function ($d) use ($activeDate) {
                            $d->whereDate('date', '<', $activeDate);
                        })
                            ->orWhereDate('plan_date', '<', $activeDate)
                            ->orWhereDate('report_date', '<', $activeDate);
                    });
                    $cmlQuery->whereDate('date', '<', $activeDate);
                }

                $wos = $woQuery->get();
                $dmis = $dmiQuery->get();
                $nsrdis = $nsrdiQuery->get();
                $cmls = $cmlQuery->get();

                $summaryData = [];
                $other = ['WO' => 0, 'DMI' => 0, 'NSRDI' => 0, 'CML' => 0, 'TOTAL' => 0];
                // Initialize with Master Data Airports
                $airports = Airport::where('status', 'Aktif')->orderBy('kode')->pluck('kode');
                foreach ($airports as $airportCode) {
                    $summaryData[strtoupper($airportCode)] = ['WO' => 0, 'DMI' => 0, 'NSRDI' => 0, 'CML' => 0, 'TOTAL' => 0];
                }

                foreach ($wos as $wo) {
                    $sta = strtoupper(trim($wo->act_station ?? $wo->plan_station ?? ''));
                    if (isset($summaryData[$sta])) {
                        $summaryData[$sta]['WO']++;
                        $summaryData[$sta]['TOTAL']++;
                    } else {
                        $other['WO']++;
                        $other['TOTAL']++;
                    }
                }
                foreach ($dmis as $dmi) {
                    $sta = strtoupper(trim($dmi->act_station ?? $dmi->plan_station ?? ''));
                    if (isset($summaryData[$sta])) {
                        $summaryData[$sta]['DMI']++;
                        $summaryData[$sta]['TOTAL']++;
                    } else {
                        $other['DMI']++;
                        $other['TOTAL']++;
                    }
                }
                foreach ($nsrdis as $nsrdi) {
                    $sta = strtoupper(trim($nsrdi->act_station ?? $nsrdi->plan_station ?? ''));
                    if (isset($summaryData[$sta])) {
                        $summaryData[$sta]['NSRDI']++;
                        $summaryData[$sta]['TOTAL']++;
                    } else {
                        $other['NSRDI']++;
                        $other['TOTAL']++;
                    }
                }
                foreach ($cmls as $cml) {
                    $sta = strtoupper(trim($cml->station ?? ''));
                    if (isset($summaryData[$sta])) {
                        $summaryData[$sta]['CML']++;
                        $summaryData[$sta]['TOTAL']++;
                    } else {
                        $other['CML']++;
                        $other['TOTAL']++;
                    }
                }

                // Sort by TOTAL desc
                uasort($summaryData, function ($a, $b) {
                    return $b['TOTAL'] <=> $a['TOTAL'];
                });

                if ($other['TOTAL'] > 0) {
                    $summaryData['LAINNYA'] = $other; // stations missing from the airport master
                }

                $logs = $summaryData;
                break;
        }

        return view('livewire.modules.daily-report.index', [
            'logs' => $logs,
            'cutoffDate' => $activeDate,
            'held' => app(DailyReportArchiver::class)->held(),
        ])->layout('components.layouts.app', ['title' => 'Daily Report']);
    }
}
