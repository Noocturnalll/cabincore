<?php

namespace App\Livewire\Modules\AcMovement;

use App\Models\AcRon;
use App\Models\AcStandby;
use App\Models\NsrdiLog;
use App\Models\SyncSetting;
use App\Models\TerminalMovement;
use App\Notifications\SystemNotification;
use App\Services\AcMovementSyncService;
use Livewire\Component;

class Index extends Component
{
    public $sheetUrl = '';

    public string $mainTab = 'table';

    public string $activeTab = 'terminal1';

    public string $search = '';

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

    public function setMainTab(string $tab): void
    {
        $this->mainTab = in_array($tab, ['sync', 'table'], true) ? $tab : 'table';
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['terminal1', 'terminal2', 'ron', 'standby'], true) ? $tab : 'terminal1';
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    /** Filter database query directly using SQL where clauses. */
    private function applySearch($query, array $fields)
    {
        $needle = trim($this->search);
        if ($needle === '') {
            return $query;
        }

        return $query->where(function ($q) use ($fields, $needle) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'like', "%{$needle}%");
            }
        });
    }

    public function render()
    {
        $openNsrdis = NsrdiLog::where('status', 'Open')
            ->whereNotNull('aircraft_registration')
            ->get(['id', 'aircraft_registration', 'nsrdi_number', 'description'])
            ->groupBy('aircraft_registration');

        $terminalFields = ['registration', 'flight_no_in', 'flight_no_out', 'plan_ps'];
        $ronFields = ['reg_flt', 'ex_flt', 'flt_no', 'stand', 'route', 'remarks'];
        $standbyFields = ['reg_flt', 'airline_category', 'parking', 'remarks'];

        $terminal1Query = $this->applySearch(TerminalMovement::where('terminal_name', 'TERMINAL 1'), $terminalFields);
        $terminal2Query = $this->applySearch(TerminalMovement::where('terminal_name', 'TERMINAL 2'), $terminalFields);
        $ronQuery = $this->applySearch(AcRon::query(), $ronFields);
        $standbyQuery = $this->applySearch(AcStandby::query(), $standbyFields);

        return view('livewire.modules.ac-movement.index', [
            'syncSetting' => $setting = SyncSetting::for(SyncSetting::AcMovement),
            'freshness' => [
                'minutes' => $setting->last_synced_at ? (int) $setting->last_synced_at->diffInMinutes(now(), true) : null,
                'failed' => $setting->last_status === 'failed',
                'stale' => $setting->spreadsheet_id !== null
                    && (! $setting->last_synced_at || $setting->last_synced_at->diffInMinutes(now(), true) > config('dja.ac_movement_stale_after_minutes', 10)),
            ],
            'counts' => [
                'terminal1' => TerminalMovement::where('terminal_name', 'TERMINAL 1')->count(),
                'terminal2' => TerminalMovement::where('terminal_name', 'TERMINAL 2')->count(),
                'ron' => AcRon::count(),
                'standby' => AcStandby::count(),
            ],
            'terminal1' => $terminal1Query->orderBy('no_seq')->get(),
            'terminal2' => $terminal2Query->orderBy('no_seq')->get(),
            'acRon' => $ronQuery->orderBy('no_seq')->get(),
            'acStandby' => $standbyQuery->orderBy('no_seq')->get(),
            'openNsrdis' => $openNsrdis,
        ])->layout('components.layouts.app', ['title' => 'AC Movement & RON']);
    }
}
