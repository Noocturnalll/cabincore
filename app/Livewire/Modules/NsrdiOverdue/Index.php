<?php

namespace App\Livewire\Modules\NsrdiOverdue;

use App\Exports\NsrdiOverdueExport;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Stock;
use App\Models\Ims\Unit;
use App\Models\NsrdiOverdue;
use App\Services\Nsrdi\NsrdiOverdueSync;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public $activeTab = 'open';

    public $isImportModalOpen = false;

    public $isEditModalOpen = false;

    public $selectedId = null;

    public $editStatus = 'Open';

    public $editStatusFinal = '';

    public $editRemarks = '';

    public $file;

    /** @var array<string, mixed>|null result of analyzing / importing the chosen file */
    public ?array $analysis = null;

    public bool $importDone = false;

    public $search = '';

    public $filterOperator = '';

    public $sortBy = 'due_date_asc';

    // IMS Integration State
    public bool $isImsModalOpen = false;

    public ?string $selectedPartNumber = null;

    public ?Item $selectedImsItem = null;

    // Quick Register Part into IMS
    public bool $isQuickRegisterModalOpen = false;

    public string $newPartNumber = '';

    public string $newName = '';

    public string $newDescription = '';

    public ?int $newCategoryId = null;

    public ?int $newUnitId = null;

    public ?int $newLocationId = null;

    public int $newInitialStock = 0;

    public function setTab(string $tab)
    {
        $this->activeTab = in_array($tab, ['open', 'closed', 'all'], true) ? $tab : 'open';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterOperator()
    {
        $this->resetPage();
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function openEditModal($id)
    {
        $overdue = NsrdiOverdue::findOrFail($id);
        $this->selectedId = $overdue->id;
        $this->editStatus = $overdue->status ?? 'Open';
        $this->editStatusFinal = $overdue->status_final ?? '';
        $this->editRemarks = $overdue->remarks ?? '';
        $this->isEditModalOpen = true;
    }

    public function updateOverdue()
    {
        if (! $this->selectedId) {
            return;
        }

        $overdue = NsrdiOverdue::findOrFail($this->selectedId);

        $data = [
            'status' => $this->editStatus,
            'status_final' => $this->editStatusFinal,
            'remarks' => $this->editRemarks,
        ];

        if ($this->editStatus === 'Closed' && ! $overdue->closed_at) {
            $data['closed_at'] = now();
        } elseif ($this->editStatus === 'Open') {
            $data['closed_at'] = null;
        }

        $overdue->update($data);

        $this->isEditModalOpen = false;
        $this->selectedId = null;
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data NSRDI Overdue berhasil diperbarui.']);
    }

    /** What importing the chosen file would do: counts and the documents the data bank would close. Nothing is written. */
    public function analyzeFile(NsrdiOverdueSync $sync): void
    {
        $this->validate(['file' => 'required|mimes:xlsx,xls|max:20480'], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat Excel (.xlsx / .xls).',
        ]);

        try {
            $this->importDone = false;
            $this->analysis = $sync->analyze($this->file->getRealPath());
        } catch (\Throwable $e) {
            $this->analysis = null;
            $this->addError('file', 'File tidak bisa dibaca: '.$e->getMessage());
        }
    }

    public function updatedFile(): void
    {
        $this->analysis = null;
        $this->importDone = false;
    }

    public function importData(NsrdiOverdueSync $sync): void
    {
        $this->validate(['file' => 'required|mimes:xlsx,xls|max:20480'], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat Excel (.xlsx / .xls).',
        ]);

        try {
            $report = $sync->apply($this->file->getRealPath());
            // documents that were already in the list and have been closed in the bank since
            $later = $sync->closeFromBank();
            $report['closed_by_bank'] = array_merge($report['closed_by_bank'], $later);
            $sync->tagClosedFromBank();

            $this->analysis = $report;
            $this->importDone = true;
            $this->file = null;
            $closed = count($report['closed_by_bank']);
            $this->dispatch('notify', ['icon' => 'success', 'message' => "{$report['created']} baru, {$report['updated']} diperbarui, {$closed} ditutup otomatis dari Bank Data / Daily Report.", 'timer' => 6000]);
        } catch (\Throwable $e) {
            $this->addError('file', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
        }
    }

    public function closeImportModal(): void
    {
        $this->isImportModalOpen = false;
        $this->analysis = null;
        $this->importDone = false;
        $this->file = null;
        $this->resetErrorBag();
    }

    /** Close every open document that the data bank / Daily Report has closed. */
    public function syncData(NsrdiOverdueSync $sync): void
    {
        $closed = $sync->closeFromBank();
        $tagged = $sync->tagClosedFromBank();

        $this->dispatch('notify', ['icon' => 'success', 'message' => count($closed).' dokumen ditutup dari Bank Data / Daily Report'.($tagged ? " ({$tagged} catatan lama dilengkapi tanggal closenya)" : '').'.']);
    }

    public function exportData()
    {
        return Excel::download(
            new NsrdiOverdueExport($this->search, $this->activeTab, $this->filterOperator),
            'NsrdiOverdue-'.$this->activeTab.'-'.date('Y-m-d').'.xlsx'
        );
    }

    public function showImsDetail(string $partNumber): void
    {
        $this->selectedPartNumber = strtoupper(trim($partNumber));
        $this->selectedImsItem = Item::with(['stocks.location', 'category', 'unit', 'location'])
            ->where('part_number', $this->selectedPartNumber)
            ->first();

        if (! $this->selectedImsItem) {
            $this->openQuickRegisterIms($this->selectedPartNumber);

            return;
        }

        $this->isImsModalOpen = true;
    }

    public function closeImsModal(): void
    {
        $this->isImsModalOpen = false;
        $this->selectedPartNumber = null;
        $this->selectedImsItem = null;
    }

    public function openQuickRegisterIms(string $partNumber, ?int $overdueId = null): void
    {
        $this->newPartNumber = strtoupper(trim($partNumber));
        $this->newName = '';
        $this->newDescription = '';

        if ($overdueId) {
            $overdue = NsrdiOverdue::find($overdueId);
            if ($overdue) {
                $this->newName = substr((string) ($overdue->description ?: "Part {$partNumber}"), 0, 100);
                $this->newDescription = (string) ($overdue->description ?? '');
            }
        }

        if (! $this->newName) {
            $this->newName = "Part {$this->newPartNumber}";
        }

        $this->newCategoryId = Category::where('is_active', true)->first()?->id;
        $this->newUnitId = Unit::where('is_active', true)->first()?->id;
        $this->newLocationId = Location::where('is_active', true)->first()?->id;
        $this->newInitialStock = 0;

        $this->isImsModalOpen = false;
        $this->isQuickRegisterModalOpen = true;
    }

    public function closeQuickRegisterModal(): void
    {
        $this->isQuickRegisterModalOpen = false;
        $this->newPartNumber = '';
        $this->newName = '';
        $this->newDescription = '';
        $this->newCategoryId = null;
        $this->newUnitId = null;
        $this->newLocationId = null;
        $this->newInitialStock = 0;
        $this->resetValidation();
    }

    public function saveQuickRegisterIms(): void
    {
        $this->validate([
            'newPartNumber' => 'required|string|max:100|unique:ims_items,part_number',
            'newName' => 'required|string|max:255',
            'newCategoryId' => 'required|exists:ims_categories,id',
            'newUnitId' => 'required|exists:ims_units,id',
            'newLocationId' => 'nullable|exists:ims_locations,id',
            'newInitialStock' => 'nullable|integer|min:0',
        ], [
            'newPartNumber.required' => 'Part number wajib diisi.',
            'newPartNumber.unique' => 'Part number sudah terdaftar di IMS.',
            'newName.required' => 'Nama part wajib diisi.',
            'newCategoryId.required' => 'Pilih kategori IMS.',
            'newUnitId.required' => 'Pilih satuan (unit).',
        ]);

        $cleanPn = strtoupper(trim($this->newPartNumber));

        $item = Item::create([
            'part_number' => $cleanPn,
            'name' => trim($this->newName),
            'description' => trim($this->newDescription ?: $this->newName),
            'category_id' => $this->newCategoryId,
            'unit_id' => $this->newUnitId,
            'default_location_id' => $this->newLocationId,
            'is_active' => true,
            'tracking_type' => 'quantity',
            'created_by' => auth()->id(),
        ]);

        if ($this->newLocationId && (int) $this->newInitialStock > 0) {
            Stock::create([
                'item_id' => $item->id,
                'location_id' => $this->newLocationId,
                'qty_on_hand' => (int) $this->newInitialStock,
                'qty_reserved' => 0,
            ]);
        }

        $this->closeQuickRegisterModal();
        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "Part {$cleanPn} berhasil didaftarkan ke IMS!",
        ]);
    }

    public function render()
    {
        $openCount = NsrdiOverdue::whereRaw('LOWER(status) != ?', ['closed'])->count();
        $closedCount = NsrdiOverdue::whereRaw('LOWER(status) = ?', ['closed'])->count();
        $allCount = NsrdiOverdue::count();

        // Count yang lewat due date (overdue) dan masih status open
        $overduePassCount = NsrdiOverdue::whereRaw('LOWER(status) != ?', ['closed'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        $operators = NsrdiOverdue::whereNotNull('operator')
            ->where('operator', '!=', '')
            ->distinct()
            ->pluck('operator')
            ->sort()
            ->values();

        $query = NsrdiOverdue::query();

        if ($this->activeTab === 'open') {
            $query->whereRaw('LOWER(status) != ?', ['closed']);
        } elseif ($this->activeTab === 'closed') {
            $query->whereRaw('LOWER(status) = ?', ['closed']);
        }

        if ($this->filterOperator) {
            $query->where('operator', $this->filterOperator);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('mddr', 'like', '%'.$this->search.'%')
                    ->orWhere('part_number', 'like', '%'.$this->search.'%')
                    ->orWhere('status_final', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->sortBy === 'due_date_asc') {
            $query->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC');
        } else {
            $query->latest('id');
        }

        $paginatedLogs = $query->paginate(15);

        // Preload IMS items for part numbers in current page
        $partNumbers = $paginatedLogs->pluck('part_number')
            ->filter()
            ->map(fn ($p) => strtoupper(trim((string) $p)))
            ->unique()
            ->values();

        $imsItems = Item::with(['stocks.location', 'category', 'unit', 'location'])
            ->whereIn('part_number', $partNumbers)
            ->get()
            ->keyBy(fn ($item) => strtoupper(trim($item->part_number)));

        return view('livewire.modules.nsrdi-overdue.index', [
            'logs' => $paginatedLogs,
            'imsItems' => $imsItems,
            'imsCategories' => Category::where('is_active', true)->orderBy('name')->get(),
            'imsUnits' => Unit::where('is_active', true)->orderBy('name')->get(),
            'imsLocations' => Location::where('is_active', true)->orderBy('name')->get(),
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'allCount' => $allCount,
            'overduePassCount' => $overduePassCount,
            'operators' => $operators,
        ])->layout('components.layouts.app', ['title' => 'NSRDI Overdue']);
    }
}
