<?php

namespace App\Livewire\Modules\LeaderReport;

use App\Models\LeaderReportImport;
use App\Models\LeaderReportRow;
use App\Services\Dja\ClassificationMemory;
use App\Services\Dja\DjaPersister;
use App\Services\Leader\LeaderReportParser;
use App\Services\Leader\LeaderReportReconciler;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Leader report import: the group leader's Excel (tasks, man power, start / finish) is matched against the DJA.
 * Step 1 reads the file and shows what will happen per row; step 2 applies it.
 */
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $file = null;

    public string $reportDate = '';

    public ?int $importId = null;

    public string $outcomeFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('leader.import'), 403);
        $this->reportDate = DjaPersister::activeDate();
    }

    private function guard(): void
    {
        abort_unless(auth()->user()?->can('leader.import'), 403);
    }

    public function updatingOutcomeFilter(): void
    {
        $this->resetPage();
    }

    public function readFile(LeaderReportParser $parser, LeaderReportReconciler $reconciler): void
    {
        $this->guard();
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,xlsm,csv', 'max:12288'],
            'reportDate' => ['required', 'date'],
        ], [], ['file' => 'file laporan', 'reportDate' => 'tanggal laporan']);

        try {
            $rows = $parser->parse($this->file->getRealPath(), Carbon::parse($this->reportDate));
        } catch (\Throwable $e) {
            $this->addError('file', 'File tidak bisa dibaca: '.$e->getMessage());

            return;
        }

        if (! $rows) {
            $this->addError('file', 'Tidak ada baris dokumen yang dikenali. Pastikan ada kolom nomor dokumen (WO / DMI / NSRDI / NO DOC) dan AC REG.');

            return;
        }

        $import = LeaderReportImport::create([
            'file_name' => $this->file->getClientOriginalName(),
            'report_date' => $this->reportDate,
            'user_id' => auth()->id(),
        ]);
        $now = now();
        foreach (array_chunk($rows, 200) as $chunk) {
            $import->rows()->insert(array_map(fn ($r) => $r + ['import_id' => $import->id, 'created_at' => $now, 'updated_at' => $now], $chunk));
        }
        $reconciler->plan($import);
        $import->update(['stats' => $reconciler->stats($import)]);

        $this->importId = $import->id;
        $this->file = null;
        $this->outcomeFilter = '';
        $this->resetPage();
    }

    public function apply(LeaderReportReconciler $reconciler): void
    {
        $this->guard();
        $import = LeaderReportImport::where('status', 'preview')->findOrFail($this->importId);
        $stats = $reconciler->apply($import);

        $msg = 'Laporan diterapkan: '.($stats['closed_planned'] ?? 0).' DJA ditutup, '
            .(($stats['unplanned_new'] ?? 0) + ($stats['unplanned_update'] ?? 0)).' unplanned, '
            .(($stats['cml_new'] ?? 0) + ($stats['cml_update'] ?? 0)).' CML.';
        if (! empty($stats['unplanned_sheet_written'])) {
            $msg .= " {$stats['unplanned_sheet_written']} unplanned ditulis ke Google Sheet.";
        }
        $failed = (int) ($stats['sheet_failed'] ?? 0) + (int) ($stats['unplanned_sheet_failed'] ?? 0);
        if ($failed) {
            $msg .= " {$failed} gagal ditulis ke Google Sheet (data tetap tersimpan di CBM).";
        }
        $this->dispatch('notify', ['icon' => $failed ? 'warning' : 'success', 'message' => $msg, 'timer' => 8000]);
    }

    /** Lets the person fix the guessed NSRDI category (CBM / PAINTING) before the report is applied. */
    public function setCategory(int $rowId, string $category): void
    {
        $this->guard();
        $row = LeaderReportRow::where('import_id', $this->importId)->whereIn('doc_type', ['WO', 'DMI', 'NSRDI'])->findOrFail($rowId);
        $allowed = $row->doc_type === 'NSRDI' ? ['CBM', 'PAINTING'] : ['CBM', 'LINE'];
        abort_unless(in_array($category, $allowed, true), 422);
        abort_unless(LeaderReportImport::where('status', 'preview')->whereKey($row->import_id)->exists(), 403);
        $row->update(['category' => $category, 'category_check' => false]);
        if (in_array($row->doc_type, ['WO', 'DMI'], true)) {
            app(ClassificationMemory::class)->remember(strtolower($row->doc_type), null, $row->description, $category, auth()->id());
        }
        app(LeaderReportReconciler::class)->replanRow($row->fresh());
    }

    public function discard(): void
    {
        $this->guard();
        LeaderReportImport::where('status', 'preview')->whereKey($this->importId)->delete();
        $this->importId = null;
    }

    public function open(int $id): void
    {
        $this->guard();
        $this->importId = LeaderReportImport::findOrFail($id)->id;
        $this->outcomeFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        $import = $this->importId ? LeaderReportImport::find($this->importId) : null;
        $rows = $import
            ? $import->rows()->when($this->outcomeFilter === 'check', fn ($q) => $q->where('category_check', true))
                ->when($this->outcomeFilter !== '' && $this->outcomeFilter !== 'check', fn ($q) => $q->where('outcome', $this->outcomeFilter))->orderBy('id')->paginate(25)
            : null;

        return view('livewire.modules.leader-report.index', [
            'import' => $import,
            'rows' => $rows,
            'stats' => $import ? app(LeaderReportReconciler::class)->stats($import) : [],
            'needCheck' => $import ? $import->rows()->where('category_check', true)->count() : 0,
            'history' => LeaderReportImport::with('user')->latest('id')->limit(8)->get(),
            'outcomes' => LeaderReportReconciler::OUTCOMES,
        ])->layout('components.layouts.app', ['title' => 'Laporan Leader']);
    }
}
