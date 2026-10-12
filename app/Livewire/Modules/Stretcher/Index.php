<?php

namespace App\Livewire\Modules\Stretcher;

use App\Exports\StretcherExport;
use App\Models\Stretcher;
use App\Models\StretcherTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithPagination;

    /**
     * Active tab: 'tracking' (overview & cards), 'transactions' (tabel CRUD), 'units' (master unit)
     */
    public string $activeTab = 'tracking';

    // Filters
    public string $search = '';

    public string $stretcherFilter = '';

    public string $stationFilter = '';

    public string $dateFilter = '';

    public int $perPage = 15;

    // Transaction Modal State
    public bool $isTransactionModalOpen = false;

    public ?int $editingTransactionId = null;

    public string $stretcher_id = '';

    public string $date = '';

    public string $aircraft_registration = '';

    public string $flight_number = '';

    public string $origin_station = '';

    public string $destination_station = '';

    public string $route = '';

    public string $passenger_name = '';

    public string $medical_notes = '';

    public string $status = 'COMPLETED';

    public string $pic_name = '';

    public string $notes = '';

    // Unit Modal State
    public bool $isUnitModalOpen = false;

    public ?int $editingUnitId = null;

    public string $unit_code = '';

    public string $unit_name = '';

    public string $unit_aircraft_type = 'B737';

    public string $unit_part_number = '';

    public string $unit_serial_number = '';

    public string $unit_current_station = 'CGK';

    public string $unit_status = 'AVAILABLE';

    public string $unit_condition = 'GOOD';

    public string $unit_notes = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStretcherFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /**
     * When stretcher is picked in transaction form, auto-fill origin station from current location.
     */
    public function updatedStretcherId($value): void
    {
        if ($value) {
            $stretcher = Stretcher::find($value);
            if ($stretcher) {
                $this->origin_station = $stretcher->current_station;
                $this->updateRoute();
            }
        }
    }

    public function updatedOriginStation(): void
    {
        $this->updateRoute();
    }

    public function updatedDestinationStation(): void
    {
        $this->updateRoute();
    }

    private function updateRoute(): void
    {
        $orig = strtoupper(trim($this->origin_station));
        $dest = strtoupper(trim($this->destination_station));
        if ($orig && $dest) {
            $this->route = "{$orig} - {$dest}";
        } elseif ($orig) {
            $this->route = $orig;
        }
    }

    // ── Transaction CRUD ──────────────────────────────────────────────

    public function openCreateTransaction(?int $preSelectedStretcherId = null): void
    {
        $this->resetTransactionForm();
        $this->date = now()->format('Y-m-d');
        $this->pic_name = auth()->user()?->name ?? '';

        if ($preSelectedStretcherId) {
            $this->stretcher_id = (string) $preSelectedStretcherId;
            $this->updatedStretcherId($preSelectedStretcherId);
        }

        $this->isTransactionModalOpen = true;
    }

    public function openEditTransaction(int $id): void
    {
        $t = StretcherTransaction::findOrFail($id);
        $this->editingTransactionId = $t->id;
        $this->stretcher_id = (string) $t->stretcher_id;
        $this->date = $t->date ? $t->date->format('Y-m-d') : '';
        $this->aircraft_registration = $t->aircraft_registration;
        $this->flight_number = $t->flight_number;
        $this->origin_station = $t->origin_station;
        $this->destination_station = $t->destination_station;
        $this->route = $t->route;
        $this->passenger_name = $t->passenger_name;
        $this->medical_notes = (string) $t->medical_notes;
        $this->status = $t->status;
        $this->pic_name = (string) $t->pic_name;
        $this->notes = (string) $t->notes;

        $this->isTransactionModalOpen = true;
    }

    public function closeTransactionModal(): void
    {
        $this->isTransactionModalOpen = false;
        $this->resetTransactionForm();
    }

    public function resetTransactionForm(): void
    {
        $this->editingTransactionId = null;
        $this->stretcher_id = '';
        $this->date = now()->format('Y-m-d');
        $this->aircraft_registration = '';
        $this->flight_number = '';
        $this->origin_station = '';
        $this->destination_station = '';
        $this->route = '';
        $this->passenger_name = '';
        $this->medical_notes = '';
        $this->status = 'COMPLETED';
        $this->pic_name = '';
        $this->notes = '';
        $this->resetValidation();
    }

    public function saveTransaction(): void
    {
        try {
            $this->validate([
                'stretcher_id' => 'required|exists:stretchers,id',
                'date' => 'required|date',
                'aircraft_registration' => 'required|string|max:20',
                'flight_number' => 'required|string|max:30',
                'origin_station' => 'required|string|max:10',
                'destination_station' => 'required|string|max:10',
                'route' => 'required|string|max:40',
                'passenger_name' => 'required|string|max:255',
                'status' => 'required|in:COMPLETED,IN_TRANSIT,CANCELLED',
            ], [
                'stretcher_id.required' => 'Pilih unit stretcher terlebih dahulu.',
                'date.required' => 'Tanggal penerbangan wajib diisi.',
                'aircraft_registration.required' => 'Registrasi pesawat (A/C Reg) wajib diisi.',
                'flight_number.required' => 'Nomor penerbangan wajib diisi.',
                'origin_station.required' => 'Titik asal wajib diisi.',
                'destination_station.required' => 'Titik akhir / tujuan (End Point) wajib diisi.',
                'passenger_name.required' => 'Nama penumpang / pasien yang naik wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('swal:error', [
                'title' => 'Validasi Gagal',
                'text' => 'data wajib diisi semua',
            ]);
            throw $e;
        }

        $destStation = strtoupper(trim($this->destination_station));
        $origStation = strtoupper(trim($this->origin_station));
        $acReg = strtoupper(trim($this->aircraft_registration));
        $flightNo = strtoupper(trim($this->flight_number));
        $routeStr = strtoupper(trim($this->route ?: "{$origStation} - {$destStation}"));

        DB::transaction(function () use ($destStation, $origStation, $acReg, $flightNo, $routeStr) {
            $data = [
                'stretcher_id' => $this->stretcher_id,
                'date' => $this->date,
                'aircraft_registration' => $acReg,
                'flight_number' => $flightNo,
                'origin_station' => $origStation,
                'destination_station' => $destStation,
                'route' => $routeStr,
                'passenger_name' => trim($this->passenger_name),
                'medical_notes' => trim($this->medical_notes) ?: null,
                'status' => $this->status,
                'pic_name' => trim($this->pic_name) ?: null,
                'notes' => trim($this->notes) ?: null,
                'created_by' => auth()->id(),
            ];

            if ($this->editingTransactionId) {
                $trx = StretcherTransaction::findOrFail($this->editingTransactionId);
                $oldStretcherId = $trx->stretcher_id;
                $trx->update($data);

                // Update current station of stretcher
                $stretcher = Stretcher::find($this->stretcher_id);
                $stretcher?->syncCurrentStation();

                if ($oldStretcherId != $this->stretcher_id) {
                    Stretcher::find($oldStretcherId)?->syncCurrentStation();
                }
            } else {
                StretcherTransaction::create($data);

                // Update current location/station of the stretcher to the new destination (End Point)
                $stretcher = Stretcher::find($this->stretcher_id);
                if ($stretcher) {
                    $stretcher->update([
                        'current_station' => $destStation,
                        'status' => $this->status === 'IN_TRANSIT' ? 'IN_USE' : 'AVAILABLE',
                    ]);
                }
            }
        });

        $this->closeTransactionModal();
        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "Transaksi berhasil disimpan. Titik posisi stretcher kini berada di {$destStation}.",
        ]);
    }

    public function deleteTransaction(int $id): void
    {
        DB::transaction(function () use ($id) {
            $t = StretcherTransaction::findOrFail($id);
            $stretcher = $t->stretcher;
            $t->delete();

            // Re-sync stretcher's location based on the remaining latest transaction
            $stretcher?->syncCurrentStation();
        });

        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => 'Transaksi berhasil dihapus dan titik posisi stretcher telah diselaraskan.',
        ]);
    }

    // ── Master Unit Stretcher CRUD ────────────────────────────────────

    public function openCreateUnit(): void
    {
        $this->resetUnitForm();
        $this->isUnitModalOpen = true;
    }

    public function openEditUnit(int $id): void
    {
        $u = Stretcher::findOrFail($id);
        $this->editingUnitId = $u->id;
        $this->unit_code = $u->code;
        $this->unit_name = $u->name;
        $this->unit_aircraft_type = (string) ($u->aircraft_type ?? 'B737');
        $this->unit_part_number = (string) $u->part_number;
        $this->unit_serial_number = (string) $u->serial_number;
        $this->unit_current_station = $u->current_station;
        $this->unit_status = $u->status;
        $this->unit_condition = $u->condition;
        $this->unit_notes = (string) $u->notes;

        $this->isUnitModalOpen = true;
    }

    public function closeUnitModal(): void
    {
        $this->isUnitModalOpen = false;
        $this->resetUnitForm();
    }

    public function resetUnitForm(): void
    {
        $this->editingUnitId = null;
        $this->unit_code = '';
        $this->unit_name = '';
        $this->unit_aircraft_type = 'B737';
        $this->unit_part_number = '';
        $this->unit_serial_number = '';
        $this->unit_current_station = 'CGK';
        $this->unit_status = 'AVAILABLE';
        $this->unit_condition = 'GOOD';
        $this->unit_notes = '';
        $this->resetValidation();
    }

    public function saveUnit(): void
    {
        try {
            $this->validate([
                'unit_code' => [
                    'required', 'string', 'max:30',
                    Rule::unique('stretchers', 'code')->ignore($this->editingUnitId)->whereNull('deleted_at'),
                ],
                'unit_name' => 'required|string|max:255',
                'unit_current_station' => 'required|string|max:10',
                'unit_status' => 'required|in:AVAILABLE,IN_USE,MAINTENANCE',
                'unit_condition' => 'required|in:GOOD,FAIR,DAMAGED',
            ], [
                'unit_code.required' => 'Kode stretcher wajib diisi (misal STR-01).',
                'unit_code.unique' => 'Kode stretcher sudah digunakan.',
                'unit_name.required' => 'Nama stretcher wajib diisi.',
                'unit_current_station.required' => 'Lokasi / stasiun saat ini wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('swal:error', [
                'title' => 'Validasi Gagal',
                'text' => 'data wajib diisi semua',
            ]);
            throw $e;
        }

        $data = [
            'code' => strtoupper(trim($this->unit_code)),
            'name' => trim($this->unit_name),
            'aircraft_type' => trim($this->unit_aircraft_type) ?: null,
            'part_number' => trim($this->unit_part_number) ?: null,
            'serial_number' => trim($this->unit_serial_number) ?: null,
            'current_station' => strtoupper(trim($this->unit_current_station)),
            'status' => $this->unit_status,
            'condition' => $this->unit_condition,
            'notes' => trim($this->unit_notes) ?: null,
        ];

        if ($this->editingUnitId) {
            Stretcher::findOrFail($this->editingUnitId)->update($data);
            $msg = 'Data unit stretcher berhasil diperbarui.';
        } else {
            Stretcher::create($data);
            $msg = 'Unit stretcher baru berhasil ditambahkan.';
        }

        $this->closeUnitModal();
        $this->dispatch('notify', ['icon' => 'success', 'message' => $msg]);
    }

    public function deleteUnit(int $id): void
    {
        $s = Stretcher::withCount('transactions')->findOrFail($id);
        if ($s->transactions_count > 0) {
            $this->dispatch('notify', [
                'icon' => 'error',
                'message' => "Stretcher {$s->code} memiliki riwayat penerbangan dan tidak dapat dihapus.",
            ]);

            return;
        }

        $s->delete();
        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "Unit stretcher {$s->code} berhasil dihapus.",
        ]);
    }

    // ── Queries & Rendering ───────────────────────────────────────────

    public function render()
    {
        $allStretchers = Stretcher::with('latestTransaction')->orderBy('code')->get();

        // Stats
        $stats = [
            'total' => $allStretchers->count(),
            'available' => $allStretchers->where('status', 'AVAILABLE')->count(),
            'in_use' => $allStretchers->where('status', 'IN_USE')->count(),
            'maintenance' => $allStretchers->where('status', 'MAINTENANCE')->count(),
            'stations' => $allStretchers->pluck('current_station')->unique()->values()->all(),
            'station_counts' => $allStretchers->groupBy('current_station')->map->count()->all(),
        ];

        // Transactions Query
        $transactionsQuery = StretcherTransaction::with(['stretcher', 'creator'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($w) use ($term) {
                    $w->where('aircraft_registration', 'like', $term)
                        ->orWhere('flight_number', 'like', $term)
                        ->orWhere('passenger_name', 'like', $term)
                        ->orWhere('route', 'like', $term)
                        ->orWhere('origin_station', 'like', $term)
                        ->orWhere('destination_station', 'like', $term)
                        ->orWhere('medical_notes', 'like', $term);
                });
            })
            ->when($this->stretcherFilter !== '', fn ($q) => $q->where('stretcher_id', $this->stretcherFilter))
            ->when($this->stationFilter !== '', fn ($q) => $q->where(fn ($w) => $w->where('origin_station', $this->stationFilter)->orWhere('destination_station', $this->stationFilter)))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('date', $this->dateFilter))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $transactions = $transactionsQuery->paginate($this->perPage);

        // Common Stations for Quick Select
        $commonStations = ['CGK', 'KNO', 'SUB', 'DPS', 'UPG', 'BTH', 'PNK', 'BPN', 'PLM', 'SRG', 'JOG', 'MDC', 'KOE'];

        return view('livewire.modules.stretcher.index', [
            'stretchers' => $allStretchers,
            'stats' => $stats,
            'transactions' => $transactions,
            'commonStations' => $commonStations,
        ])->layout('components.layouts.app', ['title' => 'Stretcher Tracking & Management']);
    }

    public function exportExcel()
    {
        return Excel::download(
            new StretcherExport($this->search, $this->stretcherFilter, $this->stationFilter, $this->dateFilter),
            'Stretcher-Tracking-'.date('Y-m-d').'.xlsx'
        );
    }
}
