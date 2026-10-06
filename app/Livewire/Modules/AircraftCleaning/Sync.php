<?php

namespace App\Livewire\Modules\AircraftCleaning;

use App\Models\SyncSetting;
use App\Notifications\SystemNotification;
use Livewire\Component;

class Sync extends Component
{
    public $sheetUrl = '';

    public function mount(): void
    {
        $this->sheetUrl = SyncSetting::for(SyncSetting::Cleaning)->sheetUrl() ?? '';
    }

    public function syncNow()
    {
        $this->validate([
            'sheetUrl' => 'required|string',
        ]);

        $spreadsheetId = SyncSetting::extractSpreadsheetId($this->sheetUrl);

        if (! $spreadsheetId) {
            $this->notifyUser('error', 'Link / ID Google Sheet tidak valid.');
            return;
        }

        $setting = SyncSetting::saveSpreadsheetId(SyncSetting::Cleaning, $spreadsheetId);
        $this->sheetUrl = $setting->sheetUrl();

        // TBD: Actual sync logic here (like $syncService->syncCleaning($spreadsheetId))
        sleep(1);
        SyncSetting::recordResult(SyncSetting::Cleaning, true, 'Fitur Sync Aircraft Cleaning berhasil. Logika mapping TBD.');

        $this->notifyUser('success', 'Fitur Sync Aircraft Cleaning (UI) berhasil. Logika mapping TBD.');
    }

    protected function notifyUser(string $type, string $message): void
    {
        $this->dispatch('notify', ['icon' => $type, 'message' => $message]);
        auth()->user()->notify(new SystemNotification(['type' => $type, 'title' => 'Sistem', 'message' => $message]));
    }

    public function render()
    {
        return view('livewire.modules.aircraft-cleaning.sync', [
            'syncSetting' => SyncSetting::for(SyncSetting::Cleaning),
        ])->layout('components.layouts.app', ['title' => 'Cleaning Sync']);
    }
}
