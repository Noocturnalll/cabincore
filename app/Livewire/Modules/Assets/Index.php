<?php

namespace App\Livewire\Modules\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Division;
use App\Models\Employee;
use App\Services\Master\MasterSettings;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Asset register: what exists, where it is kept, its condition, how many units are out and with whom (the history of
 * lending is the many-to-many between assets and people). Categories and conditions come from Data Master.
 * asset.view reads, asset.manage edits the register, asset.assign lends and takes back.
 */
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = '';

    public string $stationFilter = '';

    public bool $onlyAvailable = false;

    // asset form
    public bool $isOpen = false;

    public ?int $assetId = null;

    public string $code = '';

    public string $name = '';

    public string $asset_category = '';

    public string $condition = 'BAIK';

    public ?string $station = null;

    public $division_id = null;

    public int $qty_total = 1;

    public string $unit = 'unit';

    public ?string $serial_no = null;

    public ?string $acquired_at = null;

    public ?string $notes = null;

    // lending form
    public bool $lendOpen = false;

    public ?int $lendAssetId = null;

    public string $holderType = 'employee';

    public string $employeeSearch = '';

    public ?int $employeeId = null;

    public ?string $holderStation = null;

    public int $lendQty = 1;

    public ?string $dueBack = null;

    public ?string $lendNotes = null;

    // history panel
    public ?int $historyAssetId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('asset.view'), 403);
    }

    private function seesAll(): bool
    {
        return (bool) auth()->user()->can('registry.view_all');
    }

    private function scoped()
    {
        $user = auth()->user();

        return Asset::query()->when(! $this->seesAll(), fn ($q) => $q->where(function ($w) use ($user) {
            $w->when($user->division_id, fn ($x) => $x->orWhere('division_id', $user->division_id))
                ->when($user->station, fn ($x) => $x->orWhere('station', strtoupper($user->station)));
            if (! $user->division_id && ! $user->station) {
                $w->whereRaw('1 = 0');
            }
        }));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ── Register ────────────────────────────────────────────────

    public function create(): void
    {
        abort_unless(auth()->user()->can('asset.manage'), 403);
        $this->resetForm();
        $this->division_id = $this->seesAll() ? null : auth()->user()->division_id;
        $this->station = $this->seesAll() ? null : auth()->user()->station;
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('asset.manage'), 403);
        $a = $this->scoped()->findOrFail($id);

        $this->resetForm();
        $this->assetId = $a->id;
        $this->code = $a->code;
        $this->name = $a->name;
        $this->asset_category = $a->category;
        $this->condition = $a->condition;
        $this->station = $a->station;
        $this->division_id = $a->division_id;
        $this->qty_total = $a->qty_total;
        $this->unit = $a->unit;
        $this->serial_no = $a->serial_no;
        $this->acquired_at = $a->acquired_at?->format('Y-m-d');
        $this->notes = $a->notes;
        $this->isOpen = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('asset.manage'), 403);
        $master = app(MasterSettings::class);
        $this->code = strtoupper(trim($this->code));   // codes are stored upper case, so uniqueness must be checked that way

        $inUse = $this->assetId ? (int) AssetAssignment::where('asset_id', $this->assetId)->whereNull('returned_at')->sum('qty') : 0;
        $this->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('assets', 'code')->ignore($this->assetId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'asset_category' => ['required', Rule::in(array_keys($master->entries('asset_category')))],
            'condition' => ['required', Rule::in(array_keys($master->entries('asset_condition')))],
            'station' => ['nullable', 'string', 'max:10'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'qty_total' => ['required', 'integer', 'min:'.max(1, $inUse), 'max:100000'],
            'unit' => ['required', 'string', 'max:20'],
            'serial_no' => ['nullable', 'string', 'max:60'],
            'acquired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['qty_total.min' => "Jumlah tidak boleh kurang dari unit yang sedang dipinjam ({$inUse})."], ['asset_category' => 'kategori', 'qty_total' => 'jumlah']);

        // someone outside view_all may only register assets inside their own scope
        if (! $this->seesAll()) {
            $user = auth()->user();
            if ($this->division_id != $user->division_id && strtoupper((string) $this->station) !== strtoupper((string) $user->station)) {
                $this->addError('division_id', 'Asset harus berada di divisi atau station Anda.');

                return;
            }
        }

        $data = [
            'code' => strtoupper(trim($this->code)), 'name' => trim($this->name), 'category' => $this->asset_category, 'condition' => $this->condition,
            'station' => $this->station ? strtoupper($this->station) : null, 'division_id' => $this->division_id ?: null,
            'qty_total' => $this->qty_total, 'unit' => $this->unit, 'serial_no' => $this->serial_no ?: null,
            'acquired_at' => $this->acquired_at ?: null, 'notes' => $this->notes ?: null,
        ];
        $this->assetId ? $this->scoped()->findOrFail($this->assetId)->update($data) : Asset::create($data);

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Asset disimpan.']);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('asset.manage'), 403);
        $asset = $this->scoped()->findOrFail($id);
        if ($asset->unitsInUse() > 0) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Asset masih dipinjam, kembalikan dulu.', 'timer' => 6000]);

            return;
        }
        $asset->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Asset dihapus.']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['assetId', 'code', 'name', 'asset_category', 'station', 'division_id', 'serial_no', 'acquired_at', 'notes']);
        $this->condition = 'BAIK';
        $this->qty_total = 1;
        $this->unit = 'unit';
        $this->resetErrorBag();
    }

    // ── Lending ─────────────────────────────────────────────────

    public function openLend(int $id): void
    {
        abort_unless(auth()->user()->can('asset.assign'), 403);
        $asset = $this->scoped()->findOrFail($id);
        if ($asset->unitsAvailable() < 1) {
            $this->dispatch('notify', ['icon' => 'warning', 'message' => 'Tidak ada unit yang tersedia untuk dipinjamkan.']);

            return;
        }
        $this->reset(['employeeSearch', 'employeeId', 'holderStation', 'dueBack', 'lendNotes']);
        $this->holderType = 'employee';
        $this->lendQty = 1;
        $this->lendAssetId = $asset->id;
        $this->lendOpen = true;
        $this->resetErrorBag();
    }

    public function chooseEmployee(int $id): void
    {
        $e = Employee::findOrFail($id);
        $this->employeeId = $e->id;
        $this->employeeSearch = $e->name.' ('.$e->nik.')';
    }

    public function lend(): void
    {
        abort_unless(auth()->user()->can('asset.assign'), 403);
        $asset = $this->scoped()->findOrFail($this->lendAssetId);
        $available = $asset->unitsAvailable();

        $this->validate([
            'holderType' => ['required', Rule::in(['employee', 'station'])],
            'employeeId' => [$this->holderType === 'employee' ? 'required' : 'nullable', 'integer', 'exists:employees,id'],
            'holderStation' => [$this->holderType === 'station' ? 'required' : 'nullable', 'string', 'max:10'],
            'lendQty' => ['required', 'integer', 'min:1', 'max:'.max(1, $available)],
            'dueBack' => ['nullable', 'date', 'after_or_equal:today'],
            'lendNotes' => ['nullable', 'string', 'max:255'],
        ], ['employeeId.required' => 'Pilih karyawan dari daftar.', 'lendQty.max' => "Hanya {$available} unit yang tersedia."], ['lendQty' => 'jumlah']);

        $employee = $this->holderType === 'employee' ? Employee::find($this->employeeId) : null;
        AssetAssignment::create([
            'asset_id' => $asset->id, 'holder_type' => $this->holderType, 'employee_id' => $employee?->id,
            'holder_name' => $employee?->name ?? strtoupper((string) $this->holderStation),
            'qty' => $this->lendQty, 'assigned_at' => now()->toDateString(), 'due_back' => $this->dueBack ?: null,
            'assigned_by' => auth()->id(), 'notes' => $this->lendNotes ?: null,
        ]);

        $this->lendOpen = false;
        $this->historyAssetId = $asset->id;
        $this->dispatch('notify', ['icon' => 'success', 'message' => "{$this->lendQty} {$asset->unit} {$asset->name} dipinjamkan."]);
    }

    public function closeLend(): void
    {
        $this->lendOpen = false;
    }

    public function giveBack(int $assignmentId): void
    {
        abort_unless(auth()->user()->can('asset.assign'), 403);
        $a = AssetAssignment::whereNull('returned_at')->findOrFail($assignmentId);
        $this->scoped()->findOrFail($a->asset_id);   // only assets inside the user's scope

        $a->update(['returned_at' => now()->toDateString()]);
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Asset dikembalikan.']);
    }

    public function showHistory(?int $id): void
    {
        $this->historyAssetId = $this->historyAssetId === $id ? null : $id;
    }

    public function render()
    {
        $master = app(MasterSettings::class);
        $query = $this->scoped()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('serial_no', 'like', "%{$this->search}%")))
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($this->stationFilter !== '', fn ($q) => $q->where('station', $this->stationFilter))
            ->orderBy('name');

        $inUse = AssetAssignment::whereNull('returned_at')->selectRaw('asset_id, SUM(qty) as n')->groupBy('asset_id')->pluck('n', 'asset_id');
        $all = $this->scoped()->get();
        $lendable = $master->lendableConditions();
        $summary = [
            'types' => $all->count(),
            'units' => (int) $all->sum('qty_total'),
            'in_use' => (int) $all->sum(fn ($a) => (int) ($inUse[$a->id] ?? 0)),
            'available' => (int) $all->sum(fn ($a) => $a->unitsAvailable((int) ($inUse[$a->id] ?? 0))),
            'unfit' => (int) $all->filter(fn ($a) => ! in_array($a->condition, $lendable, true))->sum('qty_total'),
        ];

        $byStation = $all->groupBy(fn ($x) => $x->station ?: '-')->map(fn ($g, $sta) => [
            'station' => $sta, 'types' => $g->count(), 'units' => (int) $g->sum('qty_total'),
            'in_use' => (int) $g->sum(fn ($x) => (int) ($inUse[$x->id] ?? 0)),
            'available' => (int) $g->sum(fn ($x) => $x->unitsAvailable((int) ($inUse[$x->id] ?? 0))),
            'unfit' => (int) $g->filter(fn ($x) => ! in_array($x->condition, $lendable, true))->sum('qty_total'),
        ])->sortBy('station')->values();

        if ($this->onlyAvailable) {
            $ids = $all->filter(fn ($a) => $a->unitsAvailable((int) ($inUse[$a->id] ?? 0)) > 0)->pluck('id');
            $query->whereIn('id', $ids);
        }

        $employees = $this->lendOpen && $this->holderType === 'employee' && strlen($this->employeeSearch) >= 2 && ! $this->employeeId
            ? Employee::where('status', 'Aktif')->where(fn ($q) => $q->where('name', 'like', "%{$this->employeeSearch}%")->orWhere('nik', 'like', "%{$this->employeeSearch}%"))->orderBy('name')->limit(8)->get()
            : collect();

        $history = $this->historyAssetId ? AssetAssignment::with('employee')->where('asset_id', $this->historyAssetId)->orderByRaw('returned_at is not null')->orderByDesc('assigned_at')->limit(50)->get() : collect();

        return view('livewire.modules.assets.index', [
            'assets' => $query->paginate(15),
            'inUse' => $inUse,
            'summary' => $summary,
            'byStation' => $byStation,
            'categories' => $master->entries('asset_category'),
            'conditions' => $master->entries('asset_condition'),
            'lendable' => $lendable,
            'divisions' => Division::where('status', 'Aktif')->orderBy('name')->get(),
            'stations' => Asset::query()->whereNotNull('station')->distinct()->orderBy('station')->pluck('station'),
            'employees' => $employees,
            'history' => $history,
            'historyAsset' => $this->historyAssetId ? Asset::find($this->historyAssetId) : null,
            'lendAsset' => $this->lendAssetId ? Asset::find($this->lendAssetId) : null,
            'canManage' => auth()->user()->can('asset.manage'),
            'canAssign' => auth()->user()->can('asset.assign'),
            'seesAll' => $this->seesAll(),
        ])->layout('components.layouts.app', ['title' => 'Data Asset']);
    }
}
