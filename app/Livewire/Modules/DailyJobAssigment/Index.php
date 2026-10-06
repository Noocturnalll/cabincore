<?php

namespace App\Livewire\Modules\DailyJobAssigment;

use App\Exports\DjaExport;
use App\Imports\DjaImport;
use App\Models\SyncSetting;
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
            session()->flash('error', 'Gagal mengimport data: '.$e->getMessage());
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Gagal mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Gagal mengimport data: '.$e->getMessage()]));
        }
    }

    public function mount(): void
    {
        $this->sheetUrl = SyncSetting::for(SyncSetting::Dja)->sheetUrl() ?? '';
    }

    public function syncNow(GoogleSheetsSyncService $syncService)
    {
        $this->validate([
            'sheetUrl' => 'required|string',
        ]);

        $spreadsheetId = SyncSetting::extractSpreadsheetId($this->sheetUrl);

        if (! $spreadsheetId) {
            $this->notifyUser('error', 'Link / ID Google Sheet tidak valid.');

            return;
        }

        // Sheet DJA berganti tiap hari: link terakhir yang di-sync menjadi sheet aktif untuk auto-sync.
        $setting = SyncSetting::saveSpreadsheetId(SyncSetting::Dja, $spreadsheetId);
        $this->sheetUrl = $setting->sheetUrl();

        $result = $syncService->syncDja($spreadsheetId);

        $this->notifyUser($result['success'] ? 'success' : 'error', $result['message']);
    }

    protected function notifyUser(string $type, string $message): void
    {
        $this->dispatch('notify', ['icon' => $type, 'message' => $message]);
        auth()->user()->notify(new SystemNotification(['type' => $type, 'title' => 'Sistem', 'message' => $message]));
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
        return view('livewire.modules.daily-job-assigment.index', [
            'syncSetting' => SyncSetting::for(SyncSetting::Dja),
        ])->layout('components.layouts.app');
    }
}
