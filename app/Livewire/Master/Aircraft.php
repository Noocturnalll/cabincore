<?php

namespace App\Livewire\Master;

use App\Models\Aircraft as AircraftModel;
use App\Models\Aoc;
use App\Services\Audit\AircraftTypeNormalizer;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Aircraft extends Component
{
    use WithPagination;

    public $search = '';

    public $aircraft_id;

    public $registration;

    public $tipe;

    public $maskapai;

    public $wg = '';

    public $status = 'Aktif';

    public $isEditMode = false;

    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /** Airlines come from the AOC master; "Lainnya" stays for charters, and an old value on a row stays valid. */
    public function airlineOptions(): array
    {
        return Aoc::where('is_active', true)->orderBy('sort_order')->pluck('name')
            ->merge(AircraftModel::query()->distinct()->pluck('maskapai'))->push('Lainnya')->filter()->unique()->values()->all();
    }

    /** The form fields plus what follows from them: the airline's AOC and the fleet / variant of the type. */
    private function payload(): array
    {
        $norm = (new AircraftTypeNormalizer)->normalize($this->tipe);

        return [
            'registration' => $this->registration,
            'tipe' => $this->tipe,
            'type_raw' => $this->tipe,
            'fleet' => $norm['fleet'],
            'variant' => $norm['variant'],
            'maskapai' => $this->maskapai,
            'aoc_id' => Aoc::where('name', $this->maskapai)->value('id'),
            'wg' => trim((string) $this->wg) !== '' ? strtoupper(trim($this->wg)) : null,
            'status' => $this->status,
        ];
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $aircraft = AircraftModel::findOrFail($id);
        $this->aircraft_id = $id;
        $this->registration = $aircraft->registration;
        $this->tipe = $aircraft->tipe;
        $this->maskapai = $aircraft->maskapai;
        $this->wg = (string) $aircraft->wg;
        $this->status = $aircraft->status;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    public function store()
    {
        $this->registration = strtoupper(trim((string) $this->registration));
        $this->validate([
            'registration' => 'required|string|max:20|unique:aircrafts,registration',
            'tipe' => 'required|string|max:100',
            'maskapai' => ['required', 'string', Rule::in($this->airlineOptions())],
            'status' => 'required|in:Aktif,Tidak Aktif',
            'wg' => ['nullable', 'string', 'max:10'],
        ]);

        AircraftModel::create($this->payload());

        $this->isOpen = false;
        $this->resetInputFields();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Aircraft berhasil ditambahkan.']);
    }

    public function update()
    {
        $this->registration = strtoupper(trim((string) $this->registration));
        $this->validate([
            'registration' => 'required|string|max:20|unique:aircrafts,registration,'.$this->aircraft_id,
            'tipe' => 'required|string|max:100',
            'maskapai' => ['required', 'string', Rule::in($this->airlineOptions())],
            'status' => 'required|in:Aktif,Tidak Aktif',
            'wg' => ['nullable', 'string', 'max:10'],
        ]);

        $aircraft = AircraftModel::findOrFail($this->aircraft_id);
        $aircraft->update($this->payload());

        $this->isOpen = false;
        $this->resetInputFields();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Aircraft berhasil diperbarui.']);
    }

    public function delete($id)
    {
        AircraftModel::findOrFail($id)->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Aircraft berhasil dihapus.']);
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->aircraft_id = null;
        $this->registration = '';
        $this->tipe = '';
        $this->maskapai = '';
        $this->wg = '';
        $this->status = 'Aktif';
        $this->resetValidation();
    }

    public function render()
    {
        $aircrafts = AircraftModel::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('registration', 'like', '%'.$this->search.'%')
                ->orWhere('tipe', 'like', '%'.$this->search.'%')
                ->orWhere('maskapai', 'like', '%'.$this->search.'%')))
            ->orderBy('registration')
            ->paginate(15);

        return view('livewire.master.aircraft', [
            'aircrafts' => $aircrafts,
        ])->layout('components.layouts.app', ['title' => 'Master Pesawat']);
    }
}
