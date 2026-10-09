<?php

namespace App\Livewire\Modules\DailyJobAssigment;

use App\Exports\DjaExport;
use App\Helpers\RoleHelper;
use App\Imports\DjaImport;
use App\Livewire\Traits\WithLogTable;
use App\Models\DailyJobAssignment;
use App\Models\DjaSyncReview;
use App\Models\SyncSetting;
use App\Notifications\SystemNotification;
use App\Services\Dja\ClassificationMemory;
use App\Services\Dja\DjaIngestor;
use App\Services\Dja\DjaPersister;
use App\Services\GoogleSheetsSyncService;
use App\Support\DashboardScope;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithLogTable, WithPagination;

    /** Roles that may overrule the classification rules. */
    public const REVIEW_ROLES = [RoleHelper::SUPER_ADMIN, RoleHelper::MANAGER, RoleHelper::ADMIN_CGK];

    /** 'data' = daftar DJA, 'review' = baris yang tidak otomatis diterima, 'sync' = import & sinkronisasi */
    public $activeTab = 'data';

    /** review tab: 'review' (perlu dicek) | 'reject' (ditolak aturan) | 'decided' (sudah diputuskan manusia) */
    public $reviewFilter = 'review';

    public $sheetUrl = '';

    public $file;

    public $search = '';

    public $dateFilter = '';

    public function updatingReviewFilter()
    {
        $this->resetPage();
    }

    public function canReview(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(self::REVIEW_ROLES);
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            // All-or-nothing: a failed row must not leave half of the sheet imported
            DB::transaction(fn () => Excel::import(new DjaImport, $this->file));

            $s = app(DjaIngestor::class)->stats();
            $stored = $s['created'] + $s['updated'] + $s['unchanged'];
            $message = "Import selesai: {$stored} task masuk DJA (baru {$s['created']}, diperbarui {$s['updated']})";
            if ($s['review'] + $s['rejected'] > 0) {
                $message .= ", {$s['review']} perlu review, {$s['rejected']} ditolak aturan";
            }

            $this->notifyUser('success', $message.'.');
            $this->reset('file');
            $this->activeTab = 'data';
            $this->resetPage();
        } catch (\Exception $e) {
            $this->notifyUser('error', 'Gagal mengimport data: '.$e->getMessage());
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

        if ($result['success']) {
            $this->activeTab = ($result['stats']['review'] ?? 0) > 0 ? 'review' : 'data';
            $this->resetPage();
        }
    }

    // ── Review queue ──────────────────────────────────────────────────────

    private function authorizeReview(): void
    {
        abort_unless($this->canReview(), 403, 'Hanya Super Admin, Manager, atau Admin yang dapat memutuskan review DJA.');
    }

    public function acceptReview($id, DjaPersister $persister)
    {
        $this->authorizeReview();
        $review = DjaSyncReview::findOrFail($id);

        DB::transaction(function () use ($review, $persister) {
            $persister->store($review->payload, $review->spreadsheet_id);
            $review->update(['decision' => 'accepted', 'decided_by' => auth()->id(), 'decided_at' => now()]);
        });
        app(ClassificationMemory::class)->remember($review->kind, $review->payload['task_card'] ?? null, $review->description, ClassificationMemory::CBM, auth()->id());

        $this->dispatch('notify', ['icon' => 'success', 'message' => ($review->task_id ?: 'Baris').' diterima dan masuk ke '.strtoupper($review->kind).'.']);
    }

    public function rejectReview($id)
    {
        $this->authorizeReview();
        $review = DjaSyncReview::findOrFail($id);
        $review->update(['decision' => 'rejected', 'decided_by' => auth()->id(), 'decided_at' => now()]);
        app(ClassificationMemory::class)->remember($review->kind, $review->payload['task_card'] ?? null, $review->description, ClassificationMemory::LINE, auth()->id());

        $this->dispatch('notify', ['icon' => 'success', 'message' => ($review->task_id ?: 'Baris').' ditolak. Tidak akan ditanyakan lagi pada sync berikutnya.']);
    }

    /** Take back a human decision: the rules decide again on the next sync. */
    public function resetReview($id)
    {
        $this->authorizeReview();
        DjaSyncReview::findOrFail($id)->update(['decision' => null, 'decided_by' => null, 'decided_at' => null]);

        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Keputusan dibatalkan. Aturan akan menilai ulang pada sync berikutnya.']);
    }

    protected function notifyUser(string $type, string $message): void
    {
        $this->dispatch('notify', ['icon' => $type, 'message' => $message, 'timer' => 6000]);
        auth()->user()->notify(new SystemNotification(['type' => $type, 'title' => 'Sistem', 'message' => $message]));
    }

    public function exportExcel()
    {
        return Excel::download(new DjaExport($this->search, $this->dateFilter, $this->activeTab), 'DjaExport-'.date('Y-m-d').'.xlsx');
    }

    /** DJA rows the user may see (same station rule as the dashboard), with the current filters. */
    private function dataQuery()
    {
        return DailyJobAssignment::query()
            ->when(DashboardScope::for(auth()->user())->stations, fn ($q, $stations) => $q->whereIn('station', $stations))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($w) => $w->where('aircraft_registration', 'like', $term)
                    ->orWhere('task_id', 'like', $term)
                    ->orWhere('job_type', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('station', 'like', $term));
            })
            ->when($this->dateFilter, fn ($q) => $q->whereDate('date', $this->dateFilter));
    }

    private function reviewQuery()
    {
        return DjaSyncReview::query()
            ->when($this->reviewFilter === 'decided', fn ($q) => $q->whereNotNull('decision'))
            ->when($this->reviewFilter === 'review', fn ($q) => $q->whereNull('decision')->where('bucket', 'review'))
            ->when($this->reviewFilter === 'reject', fn ($q) => $q->whereNull('decision')->where('bucket', 'reject'))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($w) => $w->where('aircraft_registration', 'like', $term)
                    ->orWhere('task_id', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('reason', 'like', $term));
            });
    }

    /** @return array{minutes: int|null, stale: bool, failed: bool} */
    private function freshness(SyncSetting $setting): array
    {
        $minutes = $setting->last_synced_at ? (int) $setting->last_synced_at->diffInMinutes(now(), true) : null;

        return [
            'minutes' => $minutes,
            'failed' => $setting->last_status === 'failed',
            'stale' => $setting->spreadsheet_id !== null
                && ($minutes === null || $minutes > config('dja.stale_after_minutes', 20)),
        ];
    }

    public function render()
    {
        $rows = null;
        $reviews = null;
        $stats = ['total' => 0, 'stations' => 0, 'aircraft' => 0, 'latest' => null];

        if ($this->activeTab === 'data') {
            $rows = $this->dataQuery()
                ->withCount(['woLogs', 'dmiLogs', 'nsrdiLogs', 'cmlLogs'])
                ->orderByDesc('date')
                ->orderBy('station')
                ->orderBy('id')
                ->paginate($this->perPage);

            $filtered = $this->dataQuery();
            $stats = [
                'total' => (clone $filtered)->count(),
                'stations' => (clone $filtered)->whereNotNull('station')->distinct()->count('station'),
                'aircraft' => (clone $filtered)->distinct()->count('aircraft_registration'),
                'latest' => (clone $filtered)->max('date'),
            ];
        } elseif ($this->activeTab === 'review') {
            $reviews = $this->reviewQuery()->with('decider')->orderByDesc('last_seen_at')->orderBy('id')->paginate($this->perPage);
        }

        $setting = SyncSetting::for(SyncSetting::Dja);

        return view('livewire.modules.daily-job-assigment.index', [
            'syncSetting' => $setting,
            'freshness' => $this->freshness($setting),
            'rows' => $rows,
            'reviews' => $reviews,
            'stats' => $stats,
            'reviewCounts' => [
                'review' => DjaSyncReview::whereNull('decision')->where('bucket', 'review')->count(),
                'reject' => DjaSyncReview::whereNull('decision')->where('bucket', 'reject')->count(),
                'decided' => DjaSyncReview::whereNotNull('decision')->count(),
            ],
            'canReview' => $this->canReview(),
        ])->layout('components.layouts.app', ['title' => 'Daily Job Assignment']);
    }
}
