<?php

namespace App\Livewire\Modules\DailyJobAssigment;

use App\Exports\DjaExport;
use App\Imports\DjaImport;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Notifications\SystemNotification;
use App\Services\GoogleSheetsSyncService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads;

    public $sheetUrl = '';

    public $file;

    public function import()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            Excel::import(new DjaImport, $this->file);
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data DJA berhasil diimport dari Excel!']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data DJA berhasil diimport dari Excel!']));
            $this->reset('file');
        } catch (\Exception $e) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Gagal mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Gagal mengimport data: '.$e->getMessage()]));
        }
    }

    public function syncNow(GoogleSheetsSyncService $syncService)
    {
        $this->validate([
            'sheetUrl' => 'required|url',
        ]);

        preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $this->sheetUrl, $matches);
        $spreadsheetId = $matches[1] ?? null;

        if (! $spreadsheetId) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Invalid Google Sheet URL.']);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Invalid Google Sheet URL.']));

            return;
        }

        $pulledTaskIds = $syncService->pullSync($spreadsheetId);

        if (is_array($pulledTaskIds)) {
            // Ghost Task Protocol
            $ghostTasks = DailyJobAssignment::where('source_spreadsheet_id', $spreadsheetId)
                ->whereNotIn('task_id', $pulledTaskIds)->get();

            foreach ($ghostTasks as $ghost) {
                $logClass = null;
                if ($ghost->job_type === 'R01/WO') {
                    $logClass = WoLog::class;
                } elseif ($ghost->job_type === 'DMI') {
                    $logClass = DmiLog::class;
                } elseif ($ghost->job_type === 'AOC/NSRDI') {
                    $logClass = NsrdiLog::class;
                }

                if ($logClass) {
                    $log = $logClass::where('dja_id', $ghost->id)->first();
                    if ($log) {
                        $log->dja_id = null;
                        $log->hold_remarks = $log->hold_remarks."\n[SYSTEM] Task removed from DJA by Planner. Converted to Unplanned.";
                        $log->save();
                    }
                }
                $ghost->delete();
            }

            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Sync completed successfully!']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Sync completed successfully!']));
        } else {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Sync failed. Please check logs and credentials.']);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Sync failed. Please check logs and credentials.']));
        }
    }

    public function exportExcel()
    {
        $search = property_exists($this, 'search') ? $this->search : '';
        $dateFilter = property_exists($this, 'dateFilter') ? $this->dateFilter : '';
        $activeTab = property_exists($this, 'activeTab') ? $this->activeTab : '';

        return Excel::download(new DjaExport($search, $dateFilter, $activeTab), 'DjaExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        return view('livewire.modules.daily-job-assigment.index')
            ->layout('components.layouts.app');
    }
}
