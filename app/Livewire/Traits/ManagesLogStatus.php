<?php

namespace App\Livewire\Traits;

use App\Models\DailyJobAssignment;
use App\Services\Dja\DjaPersister;
use App\Services\GoogleSheetsSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Closed / Open actions of the WO, DMI and NSRDI logs.
 *
 *  - Closed: one click (with confirmation), clears the reason fields.
 *  - Open:   a modal that requires BOTH a reason code and remarks.
 *
 * Every change is written back to the planner sheet, and the user is told when that write-back failed.
 */
trait ManagesLogStatus
{
    /** Reason codes offered for an Open item. */
    public const REASON_CODES = ['AUTHOR', 'DEFFECT', 'GSE', 'IRR', 'LT', 'MP', 'NS', 'NT', 'OCT', 'TC', 'WT'];

    public $isModalOpen = false;

    public $selectedLogId = null;

    public $status = 'Open';

    public $hold_reason_category = '';

    public $hold_remarks = '';

    /** @return class-string<Model> */
    abstract protected function statusLogModel(): string;

    /** Tab name in the planner sheet, e.g. 'DJA', 'DJA DMI', 'DJA NSRD'. */
    abstract protected function statusSheetTab(): string;

    /** Extra fields to set when the status changes (e.g. NSRDI close date). */
    protected function statusSideEffects(Model $log, string $status): array
    {
        return [];
    }

    public function openStatusModal($id)
    {
        $log = ($this->statusLogModel())::findOrFail($id);

        $this->selectedLogId = $log->id;
        $this->status = 'Open';
        $this->hold_reason_category = (string) $log->hold_reason_category;
        $this->hold_remarks = (string) $log->hold_remarks;
        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function closeStatusModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    /** Opening an item must say why: reason code and remarks are both mandatory. */
    protected function statusRules(): array
    {
        return [
            'hold_reason_category' => ['required', Rule::in(self::REASON_CODES)],
            'hold_remarks' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    protected function statusMessages(): array
    {
        return [
            'hold_reason_category.required' => 'Code reason wajib dipilih untuk status Open.',
            'hold_reason_category.in' => 'Code reason tidak valid.',
            'hold_remarks.required' => 'Remarks wajib diisi untuk status Open.',
            'hold_remarks.min' => 'Remarks minimal 3 karakter.',
        ];
    }

    /** Save as Open (modal submit). */
    public function updateStatus(GoogleSheetsSyncService $syncService)
    {
        $this->hold_remarks = trim((string) $this->hold_remarks);
        $this->validate($this->statusRules(), $this->statusMessages());

        $log = ($this->statusLogModel())::findOrFail($this->selectedLogId);
        $this->applyStatus($log, 'Open', $this->hold_reason_category, $this->hold_remarks, $syncService);

        $this->isModalOpen = false;
    }

    /** Close in one click: clears reason code and remarks. */
    public function markClosed($id, GoogleSheetsSyncService $syncService)
    {
        $log = ($this->statusLogModel())::findOrFail($id);
        $this->applyStatus($log, 'Closed', null, null, $syncService);
    }

    private function applyStatus(Model $log, string $status, ?string $code, ?string $remarks, GoogleSheetsSyncService $syncService): void
    {
        $log->forceFill([
            'status' => $status,
            'hold_reason_category' => $code,
            'hold_remarks' => $remarks,
        ] + $this->statusSideEffects($log, $status))->save();

        $label = $log->aircraft_registration ?: 'Data';
        $pushed = $this->pushStatusToSheet($log, $status, $code, $remarks, $syncService);

        if ($pushed === false) {
            $this->dispatch('notify', [
                'icon' => 'warning',
                'message' => "{$label} {$status} di CBM, tetapi GAGAL diperbarui di Google Sheet. Cek koneksi / akses sheet.",
                'timer' => 7000,
            ]);

            return;
        }

        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "{$label} ditandai {$status}".($pushed ? ' dan sheet diperbarui.' : '.'),
        ]);
    }

    /** @return bool|null null when the item has no planner-sheet task, otherwise whether the write-back worked */
    private function pushStatusToSheet(Model $log, string $status, ?string $code, ?string $remarks, GoogleSheetsSyncService $syncService): ?bool
    {
        if (! $log->dja_id) {
            return null;
        }

        $dja = DailyJobAssignment::find($log->dja_id);
        if (! $dja || ! $dja->source_spreadsheet_id) {
            return null;
        }

        return (bool) $syncService->pushSync($dja->source_spreadsheet_id, $this->statusSheetTab(), $dja->task_id, $status, $remarks, $code);
    }

    /** The operational day, used for close dates (rolls over at 18:00 like the rest of the app). */
    protected function operationalDate(): string
    {
        return DjaPersister::activeDate();
    }
}
