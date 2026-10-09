<?php

namespace App\Livewire\Master;

use App\Models\Airport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Airports extends Component
{
    use WithPagination;

    public const STATUSES = ['Aktif', 'Tidak Aktif'];

    public $search = '';

    public $statusFilter = '';

    public $airport_id;

    public $kode;

    public $nama;

    public $kota;

    public $status = 'Aktif';

    public $isEditMode = false;

    public $isOpen = false;

    public $deleteId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $airport = Airport::findOrFail($id);
        $this->airport_id = $id;
        $this->kode = $airport->kode;
        $this->nama = $airport->nama;
        $this->kota = $airport->kota;
        $this->status = $airport->status;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    protected function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:10', Rule::unique('airports', 'kode')->ignore($this->airport_id)],
            'nama' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['kode' => 'kode IATA', 'nama' => 'nama bandara', 'kota' => 'kota'];
    }

    /** Tables/columns that store a station code as text, plus every table with an airport_id FK. */
    private function usageOf(Airport $airport): array
    {
        $checks = [
            'users' => 'station', 'capacity_stations' => 'station_code', 'daily_job_assignments' => 'station',
            'aircraft_cleanings' => 'station', 'wo_logs' => 'act_station', 'dmi_logs' => 'act_station',
            'nsrdi_logs' => 'act_station', 'cml_logs' => 'station',
        ];
        $labels = [
            'users' => 'pengguna', 'capacity_stations' => 'konfigurasi capacity', 'daily_job_assignments' => 'tugas DJA',
            'aircraft_cleanings' => 'data cleaning', 'wo_logs' => 'log WO', 'dmi_logs' => 'log DMI',
            'nsrdi_logs' => 'log NSRDI', 'cml_logs' => 'log CML',
        ];

        $used = [];
        foreach ($checks as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $airport->kode)->exists()) {
                $used[] = $labels[$table];
            }
        }

        return $used;
    }

    public function store()
    {
        $this->kode = strtoupper(trim((string) $this->kode));
        $this->validate();

        Airport::create($this->only(['kode', 'nama', 'kota', 'status']));

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Bandara berhasil ditambahkan.']);
    }

    public function update()
    {
        $this->kode = strtoupper(trim((string) $this->kode));
        $this->validate();

        $airport = Airport::findOrFail($this->airport_id);

        // The code is stored as plain text in many tables: renaming it would orphan that data
        if ($airport->kode !== $this->kode && ($used = $this->usageOf($airport))) {
            $this->addError('kode', 'Kode tidak dapat diubah karena sudah dipakai di: '.implode(', ', $used).'.');

            return;
        }

        $airport->update($this->only(['kode', 'nama', 'kota', 'status']));

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Bandara berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $airport = Airport::findOrFail($id);

        if ($used = $this->usageOf($airport)) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => "{$airport->kode} masih dipakai di: ".implode(', ', $used).'. Nonaktifkan saja.', 'timer' => 6000]);

            return;
        }

        $airport->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Bandara berhasil dihapus.']);
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->airport_id = null;
        $this->kode = '';
        $this->nama = '';
        $this->kota = '';
        $this->status = 'Aktif';
        $this->resetValidation();
    }

    public function render()
    {
        $airports = Airport::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'like', '%'.$this->search.'%')
                ->orWhere('nama', 'like', '%'.$this->search.'%')
                ->orWhere('kota', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('kode')
            ->paginate(15);

        return view('livewire.master.airports', [
            'airports' => $airports,
            'totals' => ['all' => Airport::count(), 'active' => Airport::where('status', 'Aktif')->count()],
        ])->layout('components.layouts.app', ['title' => 'Master Bandara']);
    }
}
