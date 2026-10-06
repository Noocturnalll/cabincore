<?php

namespace App\Livewire\Modules\AcMovement;

use App\Models\SyncSetting;
use App\Notifications\SystemNotification;
use App\Services\AcMovementSyncService;
use Livewire\Component;

class Index extends Component
{
    public $sheetUrl = '';

    public string $mainTab = 'sync';
    public string $activeTab = 'terminal1';

    public function mount(): void
    {
        $this->sheetUrl = SyncSetting::for(SyncSetting::AcMovement)->sheetUrl() ?? '';
    }

    public function syncNow(AcMovementSyncService $syncService)
    {
        $this->validate([
            'sheetUrl' => 'required|string',
        ]);

        $spreadsheetId = SyncSetting::extractSpreadsheetId($this->sheetUrl);

        if (! $spreadsheetId) {
            $this->notifyUser('error', 'Link / ID Google Sheet tidak valid.');

            return;
        }

        // Sheet ID disimpan permanen; scheduler memakai ID ini sampai diganti lagi di sini.
        $setting = SyncSetting::saveSpreadsheetId(SyncSetting::AcMovement, $spreadsheetId);
        $this->sheetUrl = $setting->sheetUrl();

        $result = $syncService->pullSync($spreadsheetId);

        $this->notifyUser($result['success'] ? 'success' : 'error', $result['message']);
    }

    protected function notifyUser(string $type, string $message): void
    {
        $this->dispatch('notify', ['icon' => $type, 'message' => $message]);
        auth()->user()->notify(new SystemNotification(['type' => $type, 'title' => 'Sistem', 'message' => $message]));
    }

    public function render()
    {
        $openNsrdis = \App\Models\NsrdiLog::where('status', 'Open')
            ->get()
            ->groupBy('aircraft_registration');

        return view('livewire.modules.ac-movement.index', [
            'syncSetting' => SyncSetting::for(SyncSetting::AcMovement),
            'terminal1' => \App\Models\TerminalMovement::where('terminal_name', 'TERMINAL 1')->orderBy('no_seq')->get(),
            'terminal2' => \App\Models\TerminalMovement::where('terminal_name', 'TERMINAL 2')->orderBy('no_seq')->get(),
            'acRon' => \App\Models\AcRon::orderBy('no_seq')->get(),
            'acStandby' => \App\Models\AcStandby::orderBy('no_seq')->get(),
            'openNsrdis' => $openNsrdis,
        ])->layout('components.layouts.app');
    }
}
