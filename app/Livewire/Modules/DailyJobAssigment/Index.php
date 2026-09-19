<?php

namespace App\Livewire\Modules\DailyJobAssigment;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DjaImport;

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
            session()->flash('success', 'Data DJA berhasil diimport dari Excel!');
            $this->reset('file');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    public function syncNow(\App\Services\GoogleSheetsSyncService $syncService)
    {
        $this->validate([
            'sheetUrl' => 'required|url'
        ]);

        preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $this->sheetUrl, $matches);
        $spreadsheetId = $matches[1] ?? null;

        if (!$spreadsheetId) {
            session()->flash('error', 'Invalid Google Sheet URL.');
            return;
        }

        $success = $syncService->pullSync($spreadsheetId);
        
        if ($success) {
            session()->flash('success', 'Sync completed successfully!');
        } else {
            session()->flash('error', 'Sync failed. Please check logs and credentials.');
        }
    }

    public function render()
    {
        return view('livewire.modules.daily-job-assigment.index')
            ->layout('components.layouts.app');
    }
}
