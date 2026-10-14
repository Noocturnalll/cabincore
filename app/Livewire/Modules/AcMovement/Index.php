<?php

namespace App\Livewire\Modules\AcMovement;

use App\Models\AcRon;
use App\Models\AcStandby;
use App\Models\NsrdiLog;
use App\Models\StretcherTransaction;
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

    public bool $isStretcherModalOpen = false;

    public ?int $selectedStretcherId = null;

    public ?StretcherTransaction $selectedStretcher = null;

    protected array $stretcherRegMap = [];

    protected array $stretcherFltMap = [];

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

    public function showStretcherDetail(int $id): void
    {
        $this->selectedStretcher = StretcherTransaction::with(['stretcher', 'creator'])->find($id);
        if ($this->selectedStretcher) {
            $this->selectedStretcherId = $id;
            $this->isStretcherModalOpen = true;
        }
    }

    public function closeStretcherModal(): void
    {
        $this->isStretcherModalOpen = false;
        $this->selectedStretcher = null;
        $this->selectedStretcherId = null;
    }

    protected function initStretcherMaps(): void
    {
        $stretcherTransactions = StretcherTransaction::with('stretcher')
            ->where('status', '!=', 'CANCELLED')
            ->orderByRaw("CASE WHEN status = 'REQUESTED' THEN 1 WHEN status = 'IN_TRANSIT' THEN 2 ELSE 3 END")
            ->latest('date')
            ->latest('id')
            ->get();

        $this->stretcherRegMap = [];
        $this->stretcherFltMap = [];

        foreach ($stretcherTransactions as $trx) {
            $r = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $trx->aircraft_registration));
            if ($r !== '' && ! isset($this->stretcherRegMap[$r])) {
                $this->stretcherRegMap[$r] = $trx;
            }
            $f = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $trx->flight_number));
            if ($f !== '' && ! isset($this->stretcherFltMap[$f])) {
                $this->stretcherFltMap[$f] = $trx;
            }
        }
    }

    public function findStretcherMatch(?string $reg, ?string ...$flightNumbers): ?StretcherTransaction
    {
        if (empty($this->stretcherRegMap) && empty($this->stretcherFltMap)) {
            $this->initStretcherMaps();
        }

        if ($reg) {
            $cleanReg = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $reg));
            if ($cleanReg !== '' && isset($this->stretcherRegMap[$cleanReg])) {
                return $this->stretcherRegMap[$cleanReg];
            }
            if ($cleanReg !== '') {
                foreach ($this->stretcherRegMap as $mapReg => $trx) {
                    if (str_contains($cleanReg, $mapReg) || str_contains($mapReg, $cleanReg)) {
                        return $trx;
                    }
                }
            }
        }

        foreach ($flightNumbers as $flt) {
            if (! $flt) {
                continue;
            }
            $cleanFlt = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $flt));
            if ($cleanFlt !== '' && isset($this->stretcherFltMap[$cleanFlt])) {
                return $this->stretcherFltMap[$cleanFlt];
            }
            if ($cleanFlt !== '') {
                foreach ($this->stretcherFltMap as $mapFlt => $trx) {
                    if (str_contains($cleanFlt, $mapFlt) || str_contains($mapFlt, $cleanFlt)) {
                        return $trx;
                    }
                }
            }
        }

        return null;
    }

    public function render()
    {
        $this->initStretcherMaps();
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
