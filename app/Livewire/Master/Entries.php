<?php

namespace App\Livewire\Master;

use App\Models\MasterEntry;
use App\Services\Master\MasterSettings;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * One page for every list in config/master.php. Reading needs master.<type>.view, changing needs master.<type>.manage,
 * so each role only gets the lists it is meant to see or edit.
 */
class Entries extends Component
{
    public string $type = '';

    public string $search = '';

    public bool $isOpen = false;

    public ?int $entryId = null;

    public string $code = '';

    public string $label = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @var array<string, mixed> */
    public array $attrs = [];

    public function mount(string $type): void
    {
        abort_unless(array_key_exists($type, config('master.types')), 404);
        abort_unless(auth()->user()?->can("master.$type.view"), 403);

        $this->type = $type;
    }

    private function def(): array
    {
        return config("master.types.{$this->type}");
    }

    private function canManage(): bool
    {
        return (bool) auth()->user()?->can("master.{$this->type}.manage");
    }

    public function create(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetForm();
        $this->sort_order = (int) MasterEntry::where('type', $this->type)->max('sort_order') + 1;
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $entry = MasterEntry::where('type', $this->type)->findOrFail($id);

        $this->resetForm();
        $this->entryId = $entry->id;
        $this->code = $entry->code;
        $this->label = $entry->label;
        $this->sort_order = $entry->sort_order;
        $this->is_active = $entry->is_active;
        foreach ($this->def()['fields'] as $key => $field) {
            $this->attrs[$key] = $entry->attrs[$key] ?? null;
        }
        $this->isOpen = true;
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);

        $rules = [
            'code' => ['required', 'string', 'max:60', Rule::unique('master_entries', 'code')->where('type', $this->type)->ignore($this->entryId)],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0', 'max:65000'],
        ];
        $names = ['code' => 'kode', 'label' => 'nama'];
        foreach ($this->def()['fields'] as $key => [$label, $kind, $required]) {
            $r = [$required ? 'required' : 'nullable'];
            $r[] = match ($kind) {
                'number' => 'numeric',
                'select' => Rule::in($this->def()['fields'][$key][3] ?? []),
                default => 'string',
            };
            $rules["attrs.$key"] = $r;
            $names["attrs.$key"] = strtolower($label);
        }
        $this->validate($rules, [], $names);

        $attrs = [];
        foreach ($this->def()['fields'] as $key => [, $kind]) {
            $value = $this->attrs[$key] ?? null;
            $attrs[$key] = $kind === 'number' && $value !== null && $value !== '' ? $value + 0 : ($value === '' ? null : $value);
        }

        MasterEntry::updateOrCreate(
            ['id' => $this->entryId],
            ['type' => $this->type, 'code' => strtoupper(trim($this->code)), 'label' => trim($this->label), 'attrs' => $attrs, 'sort_order' => $this->sort_order, 'is_active' => $this->is_active]
        );
        MasterSettings::flush();

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data master disimpan.']);
    }

    public function toggle(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $entry = MasterEntry::where('type', $this->type)->findOrFail($id);
        $entry->update(['is_active' => ! $entry->is_active]);
        MasterSettings::flush();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canManage(), 403);
        MasterEntry::where('type', $this->type)->findOrFail($id)->delete();
        MasterSettings::flush();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data master dihapus.']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->entryId = null;
        $this->code = '';
        $this->label = '';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->attrs = [];
        foreach ($this->def()['fields'] as $key => $_) {
            $this->attrs[$key] = null;
        }
        $this->resetErrorBag();
    }

    public function render()
    {
        $entries = MasterEntry::where('type', $this->type)
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('code', 'like', "%{$this->search}%")->orWhere('label', 'like', "%{$this->search}%")))
            ->orderBy('sort_order')->orderBy('code')->get();

        return view('livewire.master.entries', [
            'def' => $this->def(),
            'entries' => $entries,
            'canManage' => $this->canManage(),
        ])->layout('components.layouts.app', ['title' => $this->def()['label']]);
    }
}
