<?php

namespace App\Livewire\Modules\Registry;

use App\Exports\RegistryExport;
use App\Models\Division;
use App\Models\RegistryRecord;
use App\Support\ExpiryStatus;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * One page for every module in config/registry.php. Access is per module (registry.<key>.view / .manage) and
 * per division: only roles with registry.view_all (Manager, Super Admin) see or write other divisions' records.
 */
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $module = '';

    public string $search = '';

    public string $divisionFilter = '';

    public bool $onlyDue = false;

    public bool $isOpen = false;

    public ?int $recordId = null;

    public $division_id = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public $importFile = null;

    public bool $isImportModalOpen = false;

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

    public function exportExcel()
    {
        return Excel::download(
            new RegistryExport($this->module, $this->search, $this->divisionFilter, $this->onlyDue),
            'Registry-'.$this->module.'-'.date('Y-m-d').'.xlsx'
        );
    }

    public function openImportModal(): void
    {
        abort_unless($this->canManage(), 403);
        $this->importFile = null;
        $this->isImportModalOpen = true;
    }

    public function closeImportModal(): void
    {
        $this->isImportModalOpen = false;
        $this->importFile = null;
    }

    public function importExcel(): void
    {
        abort_unless($this->canManage(), 403);
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $spreadsheet = IOFactory::load($this->importFile->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                throw new \Exception('File Excel tidak memiliki baris data.');
            }

            $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
            $cfg = $this->cfg();
            $fields = $cfg['fields'];
            $defaultDivisionId = $this->seesAll() ? (Division::first()?->id ?? 1) : auth()->user()->division_id;

            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $data = [];
                foreach ($fields as $key => $def) {
                    $headerLabel = strtolower(trim($def[0]));
                    $idx = array_search($headerLabel, $headers, true);
                    if ($idx === false) {
                        $idx = array_search(strtolower($key), $headers, true);
                    }
                    $data[$key] = ($idx !== false && isset($row[$idx])) ? trim((string) $row[$idx]) : null;
                }

                // Division mapping: check if "divisi" column exists
                $divIdx = array_search('divisi', $headers, true);
                $rowDivisionId = $defaultDivisionId;
                if ($divIdx !== false && ! empty($row[$divIdx]) && $this->seesAll()) {
                    $divName = trim((string) $row[$divIdx]);
                    $matchedDiv = Division::where('name', 'like', "%{$divName}%")->first();
                    if ($matchedDiv) {
                        $rowDivisionId = $matchedDiv->id;
                    }
                }

                $title = $data[$cfg['title']] ?? 'Record '.($i);
                $dueDate = null;
                if (! empty($cfg['due']) && ! empty($data[$cfg['due']])) {
                    try {
                        $dueDate = Carbon::parse($data[$cfg['due']])->toDateString();
                    } catch (\Throwable $e) {
                        $dueDate = null;
                    }
                }

                RegistryRecord::create([
                    'module' => $this->module,
                    'division_id' => $rowDivisionId,
                    'title' => $title,
                    'due_date' => $dueDate,
                    'data' => $data,
                    'created_by' => auth()->id(),
                ]);

                $imported++;
            }

            $this->isImportModalOpen = false;
            $this->importFile = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => "Berhasil mengimpor {$imported} data."]);
            session()->flash('success', "Berhasil mengimpor {$imported} data ke modul {$cfg['label']}.");
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Gagal memproses file: '.$e->getMessage());
        }
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
