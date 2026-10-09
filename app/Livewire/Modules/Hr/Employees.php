<?php

namespace App\Livewire\Modules\Hr;

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\RegistryRecord;
use App\Support\ExpiryStatus;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Employee database (Development & GA). Division admins only see and edit their own division;
 * roles with hr.view_all (Super Admin) see every division.
 */
class Employees extends Component
{
    use WithPagination;

    public string $search = '';

    public string $divisionFilter = '';

    public string $expiryFilter = '';   // '', contract, passport, any

    public bool $isOpen = false;

    public ?int $employeeId = null;

    public string $nik = '';

    public string $name = '';

    public $division_id = null;

    public $position_id = null;

    public ?string $phone = null;

    public ?string $join_date = null;

    public string $status = 'Aktif';

    public string $contract_type = 'PKWT';

    public ?string $contract_start = null;

    public ?string $contract_end = null;

    public ?string $passport_no = null;

    public ?string $passport_expiry = null;

    public ?string $notes = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('hr.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDivisionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingExpiryFilter(): void
    {
        $this->resetPage();
    }

    public function setDivision(string $id): void
    {
        $this->divisionFilter = $id;
        $this->resetPage();
    }

    private function canManage(): bool
    {
        return (bool) auth()->user()?->can('hr.manage');
    }

    private function seesAll(): bool
    {
        return (bool) auth()->user()?->can('hr.view_all');
    }

    /** Divisions the current user may work with. */
    private function allowedDivisionIds(): ?array
    {
        return $this->seesAll() ? null : array_filter([auth()->user()->division_id]);
    }

    private function scoped()
    {
        $ids = $this->allowedDivisionIds();

        return Employee::query()->when($ids !== null, fn ($q) => $q->whereIn('division_id', $ids));
    }

    private function findAllowed(int $id): Employee
    {
        return $this->scoped()->findOrFail($id);
    }

    public function create(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetForm();
        $this->division_id = $this->seesAll() ? null : auth()->user()->division_id;
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $e = $this->findAllowed($id);

        $this->employeeId = $e->id;
        $this->nik = $e->nik;
        $this->name = $e->name;
        $this->division_id = $e->division_id;
        $this->position_id = $e->position_id;
        $this->phone = $e->phone;
        $this->join_date = $e->join_date?->format('Y-m-d');
        $this->status = $e->status;
        $this->contract_type = $e->contract_type;
        $this->contract_start = $e->contract_start?->format('Y-m-d');
        $this->contract_end = $e->contract_end?->format('Y-m-d');
        $this->passport_no = $e->passport_no;
        $this->passport_expiry = $e->passport_expiry?->format('Y-m-d');
        $this->notes = $e->notes;
        $this->resetErrorBag();
        $this->isOpen = true;
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);

        $allowed = $this->allowedDivisionIds();
        $this->validate([
            'nik' => ['required', 'string', 'max:50', Rule::unique('employees', 'nik')->ignore($this->employeeId)->whereNull('deleted_at')],
            'name' => 'required|string|max:255',
            'division_id' => ['required', 'integer', 'exists:divisions,id', $allowed === null ? 'nullable' : Rule::in($allowed)],
            'position_id' => 'nullable|integer|exists:positions,id',
            'phone' => 'nullable|string|max:30',
            'join_date' => 'nullable|date',
            'status' => 'required|in:Aktif,Nonaktif',
            'contract_type' => 'required|in:PKWT,PKWTT',
            'contract_start' => 'nullable|date',
            'contract_end' => [$this->contract_type === 'PKWT' ? 'required' : 'nullable', 'date', 'after_or_equal:contract_start'],
            'passport_no' => 'nullable|string|max:50',
            'passport_expiry' => ['nullable', 'date', 'required_with:passport_no'],
            'notes' => 'nullable|string|max:1000',
        ], [
            'contract_end.required' => 'Tanggal akhir kontrak wajib diisi untuk karyawan PKWT.',
            'contract_end.after_or_equal' => 'Akhir kontrak tidak boleh sebelum awal kontrak.',
            'passport_expiry.required_with' => 'Tanggal habis paspor wajib diisi bila nomor paspor ada.',
            'division_id.in' => 'Anda hanya dapat mengelola karyawan di divisi Anda sendiri.',
        ]);

        $data = [
            'nik' => trim($this->nik),
            'name' => trim($this->name),
            'division_id' => $this->division_id,
            'position_id' => $this->position_id ?: null,
            'phone' => $this->phone ?: null,
            'join_date' => $this->join_date ?: null,
            'status' => $this->status,
            'contract_type' => $this->contract_type,
            'contract_start' => $this->contract_start ?: null,
            'contract_end' => $this->contract_type === 'PKWT' ? $this->contract_end : null,
            'passport_no' => $this->passport_no ?: null,
            'passport_expiry' => $this->passport_no ? $this->passport_expiry : null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->employeeId) {
            $this->findAllowed($this->employeeId)->update($data);
            $msg = 'Data karyawan diperbarui.';
        } else {
            Employee::create($data);
            $msg = 'Karyawan ditambahkan.';
        }

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => $msg]);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $this->findAllowed($id)->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Karyawan dihapus.']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['employeeId', 'nik', 'name', 'division_id', 'position_id', 'phone', 'join_date', 'contract_start', 'contract_end', 'passport_no', 'passport_expiry', 'notes']);
        $this->status = 'Aktif';
        $this->contract_type = 'PKWT';
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = $this->scoped()->with(['division', 'position'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('nik', 'like', "%{$this->search}%")
                ->orWhere('job_title', 'like', "%{$this->search}%")
                ->orWhere('station', 'like', "%{$this->search}%")
                ->orWhere('passport_no', 'like', "%{$this->search}%")))
            ->when($this->divisionFilter !== '', fn ($q) => $q->where('division_id', $this->divisionFilter))
            ->orderBy('name');

        // Expiry filters are evaluated against the same month windows used for the colour markers.
        $windows = fn (string $key) => now()->startOfDay()->addMonthsNoOverflow(config("hr.$key.yellow"))->toDateString();
        if ($this->expiryFilter === 'contract') {
            $query->where('contract_type', 'PKWT')->whereDate('contract_end', '<=', $windows('contract'));
        } elseif ($this->expiryFilter === 'passport') {
            $query->whereDate('passport_expiry', '<=', $windows('passport'));
        } elseif ($this->expiryFilter === 'any') {
            $query->where(fn ($w) => $w
                ->where(fn ($c) => $c->where('contract_type', 'PKWT')->whereDate('contract_end', '<=', $windows('contract')))
                ->orWhereDate('passport_expiry', '<=', $windows('passport')));
        }

        // Counters for the summary strip (whole scope, ignoring search)
        $all = $this->scoped()->get();

        // Airport pass (PAS Bandara) of the people on this page and of everyone in scope, for the colour marker
        $paged = $query->paginate(15);
        $pasRows = RegistryRecord::where('module', 'pas')->whereIn('data->nik', $all->pluck('nik')->all())->get();
        $pasByNik = $pasRows->keyBy(fn ($r) => (string) ($r->data['nik'] ?? ''));
        $pasStates = $pasRows->map->expiryStatus()->countBy();
        $tabCounts = $all->groupBy('division_id')->map->count();
        $count = fn (string $method, string $state) => $all->filter(fn (Employee $e) => $e->$method() === $state)->count();

        return view('livewire.modules.hr.employees', [
            'employees' => $paged,
            'pasByNik' => $pasByNik,
            'tabCounts' => $tabCounts,
            'divisions' => Division::where('status', 'Aktif')->orderBy('name')->get(),
            'positions' => Position::where('status', 'Aktif')->orderBy('name')->get(),
            'canManage' => $this->canManage(),
            'seesAll' => $this->seesAll(),
            'summary' => [
                'total' => $all->count(),
                'contract_yellow' => $count('contractStatus', ExpiryStatus::YELLOW),
                'contract_red' => $count('contractStatus', ExpiryStatus::RED) + $count('contractStatus', ExpiryStatus::EXPIRED),
                'passport_yellow' => $count('passportStatus', ExpiryStatus::YELLOW),
                'passport_red' => $count('passportStatus', ExpiryStatus::RED) + $count('passportStatus', ExpiryStatus::EXPIRED),
                'pas_yellow' => (int) ($pasStates[ExpiryStatus::YELLOW] ?? 0),
                'pas_red' => (int) (($pasStates[ExpiryStatus::RED] ?? 0) + ($pasStates[ExpiryStatus::EXPIRED] ?? 0)),
            ],
        ])->layout('components.layouts.app', ['title' => 'Database Karyawan']);
    }
}
