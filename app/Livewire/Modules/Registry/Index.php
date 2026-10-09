<?php

namespace App\Livewire\Modules\Registry;

use App\Models\Division;
use App\Models\RegistryRecord;
use App\Support\ExpiryStatus;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * One page for every module in config/registry.php. Access is per module (registry.<key>.view / .manage) and
 * per division: only roles with registry.view_all (Manager, Super Admin) see or write other divisions' records.
 */
class Index extends Component
{
    use WithPagination;

    public string $module = '';

    public string $search = '';

    public string $divisionFilter = '';

    public bool $onlyDue = false;

    public bool $isOpen = false;

    public ?int $recordId = null;

    public $division_id = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(string $module): void
    {
        abort_unless(array_key_exists($module, config('registry.modules')), 404);
        abort_unless(auth()->user()?->can("registry.$module.view"), 403);

        $this->module = $module;
    }

    private function cfg(): array
    {
        return config("registry.modules.{$this->module}");
    }

    private function canManage(): bool
    {
        return (bool) auth()->user()?->can("registry.{$this->module}.manage");
    }

    private function seesAll(): bool
    {
        return (bool) auth()->user()?->can('registry.view_all');
    }

    private function scoped()
    {
        return RegistryRecord::query()
            ->where('module', $this->module)
            ->when(! $this->seesAll(), fn ($q) => $q->where('division_id', auth()->user()->division_id));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDivisionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyDue(): void
    {
        $this->resetPage();
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
        $record = $this->scoped()->findOrFail($id);

        $this->resetForm();
        $this->recordId = $record->id;
        $this->division_id = $record->division_id;
        foreach ($this->cfg()['fields'] as $key => $def) {
            $this->form[$key] = $record->data[$key] ?? null;
        }
        $this->isOpen = true;
    }

    private function rules_(): array
    {
        $rules = [];
        foreach ($this->cfg()['fields'] as $key => $def) {
            [, $type, $required] = $def;
            $r = [$required ? 'required' : 'nullable'];
            $r = array_merge($r, match ($type) {
                'date' => ['date'],
                'number' => ['numeric', 'min:0', 'max:1000000'],
                'select' => [Rule::in($def[3] ?? [])],
                'textarea' => ['string', 'max:2000'],
                default => ['string', 'max:255'],
            });
            $rules["form.$key"] = $r;
        }

        return $rules;
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);

        $messages = [];
        $attributes = [];
        foreach ($this->cfg()['fields'] as $key => $def) {
            $attributes["form.$key"] = strtolower($def[0]);
        }

        $divisionRule = $this->seesAll()
            ? ['required', 'integer', 'exists:divisions,id']
            : ['required', 'integer', Rule::in([auth()->user()->division_id])];

        $this->validate(['division_id' => $divisionRule] + $this->rules_(), $messages, $attributes + ['division_id' => 'divisi']);

        $cfg = $this->cfg();
        $data = [];
        foreach ($cfg['fields'] as $key => $def) {
            $value = $this->form[$key] ?? null;
            $data[$key] = $value === '' ? null : $value;
        }

        $payload = [
            'module' => $this->module,
            'division_id' => $this->division_id,
            'title' => $data[$cfg['title']] ?? null,
            'due_date' => ! empty($cfg['due']) ? ($data[$cfg['due']] ?? null) : null,
            'data' => $data,
        ];

        if ($this->recordId) {
            $this->scoped()->findOrFail($this->recordId)->update($payload);
            $msg = 'Data diperbarui.';
        } else {
            RegistryRecord::create($payload + ['created_by' => auth()->id()]);
            $msg = 'Data ditambahkan.';
        }

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => $msg]);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $this->scoped()->findOrFail($id)->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data dihapus.']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->recordId = null;
        $this->division_id = null;
        $this->form = [];
        foreach ($this->cfg()['fields'] as $key => $def) {
            $this->form[$key] = null;
        }
        $this->resetErrorBag();
    }

    public function render()
    {
        $cfg = $this->cfg();
        $hasDue = ! empty($cfg['due']);

        $query = $this->scoped()->with('division')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('data', 'like', "%{$this->search}%")))
            ->when($this->divisionFilter !== '' && $this->seesAll(), fn ($q) => $q->where('division_id', $this->divisionFilter));

        if ($hasDue && $this->onlyDue) {
            $query->whereNotNull('due_date')
                ->whereDate('due_date', '<=', now()->startOfDay()->addMonthsNoOverflow((int) $cfg['yellow'])->toDateString());
        }

        $query = $hasDue ? $query->orderByRaw('due_date is null')->orderBy('due_date') : $query->latest('id');

        $summary = null;
        if ($hasDue) {
            $states = $this->scoped()->get()->map->expiryStatus()->countBy();
            $summary = [
                'yellow' => $states[ExpiryStatus::YELLOW] ?? 0,
                'red' => ($states[ExpiryStatus::RED] ?? 0) + ($states[ExpiryStatus::EXPIRED] ?? 0),
            ];
        }

        return view('livewire.modules.registry.index', [
            'cfg' => $cfg,
            'records' => $query->paginate(15),
            'divisions' => Division::where('status', 'Aktif')->orderBy('name')->get(),
            'canManage' => $this->canManage(),
            'seesAll' => $this->seesAll(),
            'hasDue' => $hasDue,
            'summary' => $summary,
            'groupLabel' => config('registry.groups.'.$cfg['group']),
        ])->layout('components.layouts.app', ['title' => $cfg['label']]);
    }
}
