<?php

namespace App\Livewire\Modules\AircraftCleaning;

use App\Livewire\Traits\WithLogTable;
use App\Models\AircraftCleaning;
use App\Models\Airport;
use App\Notifications\SystemNotification;
use App\Support\DashboardScope;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Shared CRUD for the General / Interior (DCI) / Exterior (DCE) cleaning logs.
 * Subclasses only describe which cleaning type they manage.
 */
abstract class CleaningLog extends Component
{
    use WithLogTable, WithPagination;

    public const STATUSES = ['Open', 'Closed'];

    public const SHIFTS = ['Morning', 'Afternoon', 'Night'];

    public $search = '';

    public $dateFilter = '';

    /** '' = semua, 'Open', 'Closed' */
    public $statusFilter = '';

    // dipakai oleh WithLogTable::setTab()
    public $activeTab = '';

    public $isModalOpen = false;

    public $deleteId = null;

    public $cleaningId;

    public $aircraft_registration = '';

    public $station = '';

    public $date = '';

    public $shift = '';

    public $status = 'Open';

    public $remarks = '';

    public $operator = '';

    /** Work start / finish as HH:MM; combined with the date into start_at / end_at (finish after midnight rolls to next day). */
    public $start_time = '';

    public $end_time = '';

    /** DB value of aircraft_cleanings.type, e.g. General, DCI, DCE */
    abstract protected function type(): string;

    /** Human title, e.g. "General Cleaning" */
    abstract protected function title(): string;

    abstract protected function subtitle(): string;

    /** Label of the "shift / team" column */
    protected function teamLabel(): string
    {
        return 'Shift / Regu';
    }

    protected function accent(): string
    {
        return 'green';
    }

    /** Stations the current user may see/edit; null = all stations. */
    protected function allowedStations(): ?array
    {
        return DashboardScope::for(auth()->user())->stations;
    }

    protected function scopedQuery()
    {
        return AircraftCleaning::query()
            ->where('type', $this->type())
            ->when($this->allowedStations(), fn ($q, $stations) => $q->whereIn('station', $stations));
    }

    protected function rules(): array
    {
        $stations = $this->allowedStations() ?? Airport::pluck('kode')->all();

        return [
            'aircraft_registration' => ['required', 'string', 'max:255'],
            'station' => ['required', 'string', Rule::in($stations)],
            'date' => ['required', 'date'],
            'shift' => ['required', Rule::in(self::SHIFTS)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'operator' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'aircraft_registration' => 'registrasi pesawat',
            'station' => 'station',
            'date' => 'tanggal',
            'shift' => 'shift',
            'status' => 'status',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
        ];
    }

    public function create(): void
    {
        $this->resetInputFields();
        $stations = $this->allowedStations();
        $this->station = $stations && count($stations) === 1 ? $stations[0] : '';
        $this->date = now()->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function edit($id): void
    {
        $this->resetInputFields();
        $record = $this->scopedQuery()->findOrFail($id);

        $this->cleaningId = $record->id;
        $this->aircraft_registration = $record->aircraft_registration;
        $this->station = (string) $record->station;
        $this->date = Carbon::parse($record->date)->format('Y-m-d');
        $this->shift = (string) $record->shift;
        $this->status = in_array($record->status, ['Closed', 'Selesai'], true) ? 'Closed' : 'Open';
        $this->remarks = (string) $record->remarks;
        $this->operator = (string) $record->operator;
        $this->start_time = $record->start_at ? Carbon::parse($record->start_at)->format('H:i') : '';
        $this->end_time = $record->end_at ? Carbon::parse($record->end_at)->format('H:i') : '';
        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->aircraft_registration = strtoupper(trim($this->aircraft_registration));
        $this->validate();

        // Same key the Excel import uses (registration + date + type) must stay unique
        $duplicate = AircraftCleaning::where('type', $this->type())
            ->where('aircraft_registration', $this->aircraft_registration)
            ->whereDate('date', $this->date)
            ->when($this->cleaningId, fn ($q) => $q->where('id', '!=', $this->cleaningId))
            ->exists();

        if ($duplicate) {
            $this->addError('aircraft_registration', 'Data untuk pesawat dan tanggal ini sudah ada.');

            return;
        }

        [$start, $end] = $this->workWindow();

        $record = $this->cleaningId
            ? $this->scopedQuery()->findOrFail($this->cleaningId)
            : new AircraftCleaning(['type' => $this->type()]);

        $record->fill([
            'aircraft_registration' => $this->aircraft_registration,
            'station' => $this->station,
            'date' => $this->date,
            'shift' => $this->shift,
            'status' => $this->status,
            'remarks' => $this->remarks ?: null,
            'operator' => $this->operator ?: null,
            'start_at' => $start,
            'end_at' => $end,
        ])->save();

        $this->notifyUser('success', 'Data '.$this->title().' berhasil disimpan!');
        $this->closeModal();
    }

    /** @return array{0: ?string, 1: ?string} start / end datetimes, or nulls when no times were entered */
    private function workWindow(): array
    {
        if (! $this->start_time || ! $this->end_time) {
            return [null, null];
        }
        $start = Carbon::parse($this->date.' '.$this->start_time);
        $end = Carbon::parse($this->date.' '.$this->end_time);
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start->toDateTimeString(), $end->toDateTimeString()];
    }

    public function deleteConfirm($id): void
    {
        $this->deleteId = $this->scopedQuery()->findOrFail($id)->id;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            $this->scopedQuery()->whereKey($this->deleteId)->delete();
            $this->deleteId = null;
            $this->notifyUser('success', 'Data berhasil dihapus!');
        }
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    protected function notifyUser(string $type, string $message): void
    {
        $this->dispatch('notify', ['icon' => $type, 'message' => $message]);
        auth()->user()->notify(new SystemNotification(['type' => $type, 'title' => 'Sistem', 'message' => $message]));
    }

    private function resetInputFields(): void
    {
        $this->cleaningId = null;
        $this->aircraft_registration = '';
        $this->station = '';
        $this->date = '';
        $this->shift = '';
        $this->status = 'Open';
        $this->remarks = '';
        $this->operator = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->resetValidation();
    }

    public function render()
    {
        $base = $this->scopedQuery();

        $counts = [
            '' => (clone $base)->count(),
            'Open' => (clone $base)->whereIn('status', ['Open', 'Aktif'])->count(),
            'Closed' => (clone $base)->whereIn('status', ['Closed', 'Selesai'])->count(),
        ];

        $query = (clone $base)
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($w) => $w->where('aircraft_registration', 'like', $term)
                    ->orWhere('operator', 'like', $term)
                    ->orWhere('station', 'like', $term)
                    ->orWhere('shift', 'like', $term)
                    ->orWhere('remarks', 'like', $term));
            })
            ->when($this->dateFilter, fn ($q) => $q->whereDate('date', $this->dateFilter))
            ->when($this->statusFilter === 'Open', fn ($q) => $q->whereIn('status', ['Open', 'Aktif']))
            ->when($this->statusFilter === 'Closed', fn ($q) => $q->whereIn('status', ['Closed', 'Selesai']))
            ->orderByDesc('date')
            ->orderByDesc('id');

        return view('livewire.modules.aircraft-cleaning.log', [
            'cleanings' => $query->paginate($this->perPage),
            'counts' => $counts,
            'stationOptions' => $this->allowedStations() ?? Airport::orderBy('kode')->pluck('kode')->all(),
            'meta' => [
                'title' => $this->title(),
                'subtitle' => $this->subtitle(),
                'teamLabel' => $this->teamLabel(),
                'accent' => $this->accent(),
            ],
            'shifts' => self::SHIFTS,
        ])->layout('components.layouts.app', ['title' => $this->title()]);
    }
}
