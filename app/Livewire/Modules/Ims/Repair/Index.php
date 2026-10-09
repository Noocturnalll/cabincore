<?php

namespace App\Livewire\Modules\Ims\Repair;

use App\Livewire\Traits\WithItemPicker;
use App\Models\Ims\Location;
use App\Models\Ims\RepairCompleted;
use App\Models\Ims\RepairInProgress;
use App\Models\Ims\RepairWaiting;
use App\Models\Ims\Supplier;
use App\Models\User;
use App\Services\Ims\RepairService;
use App\Services\Ims\StockService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Repair rack. Permissions: ims.repair.request (receive a faulty part), ims.repair.manage (start / complete),
 * ims.repair.back_stage (return a serviceable part to stock).
 */
class Index extends Component
{
    use WithItemPicker, WithPagination;

    /** intake = filed by COD, waiting for ACC | waiting = Rak Repair 1 | process = Rak Repair 2 | completed = Rak Repair 3 */
    public $activeTab = 'intake';

    public $search = '';

    /** receive | start | complete | return | null */
    public $modal = null;

    public $code = null;

    // receive
    public $itemId = '';

    public $qty = 1;

    public $location_id = '';

    public $fault_description = '';

    public $priority = 'normal';

    public $source = 'aircraft';

    public $aircraft_registration = '';

    public $notes = '';

    // start
    public $technician_id = '';

    public $vendor_id = '';

    public $work_order_no = '';

    public $estimated_completion_date = '';

    public $progress_notes = '';

    // complete
    public $result = 'serviceable';

    public $findings = '';

    public $action_taken = '';

    public $certificate_no = '';

    public $repaired_by_name = '';

    // return
    public $return_location_id = '';

    public function updatingActiveTab()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['intake', 'waiting', 'process', 'completed'], true) ? $tab : 'intake';
        $this->resetPage();
    }

    // ── modals ───────────────────────────────────────────────────────────

    public function openReceive(): void
    {
        $this->authorizeStockAction('ims.repair.request');
        $this->resetForm();
        $this->modal = 'receive';
    }

    public function openStart(string $code): void
    {
        $this->authorizeStockAction('ims.repair.manage');
        $this->resetForm();
        $this->code = RepairWaiting::where('repair_code', $code)->whereNotNull('accepted_at')->firstOrFail()->repair_code;
        $this->modal = 'start';
    }

    public function openComplete(string $code): void
    {
        $this->authorizeStockAction('ims.repair.manage');
        $this->resetForm();
        $this->code = RepairInProgress::where('repair_code', $code)->firstOrFail()->repair_code;
        $this->repaired_by_name = (string) auth()->user()?->name;
        $this->modal = 'complete';
    }

    public function openReturn(string $code): void
    {
        $this->authorizeStockAction('ims.repair.back_stage');
        $this->resetForm();
        $this->code = RepairCompleted::where('repair_code', $code)->where('result', 'serviceable')->whereNull('returned_to_stock_at')->firstOrFail()->repair_code;
        $this->modal = 'return';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'code', 'itemId', 'searchItem', 'qty', 'location_id', 'fault_description', 'priority', 'source', 'aircraft_registration', 'notes',
            'technician_id', 'vendor_id', 'work_order_no', 'estimated_completion_date', 'progress_notes',
            'result', 'findings', 'action_taken', 'certificate_no', 'repaired_by_name', 'return_location_id',
        ]);
        $this->resetValidation();
    }

    // ── actions ──────────────────────────────────────────────────────────

    public function receive(RepairService $repairs)
    {
        $this->authorizeStockAction('ims.repair.request');

        $data = $this->validate([
            'itemId' => ['required', Rule::exists('ims_items', 'id')],
            'qty' => ['required', 'integer', 'min:1', 'max:100000'],
            'location_id' => ['required', Rule::exists('ims_locations', 'id')],
            'fault_description' => ['required', 'string', 'min:5', 'max:1000'],
            'priority' => ['required', Rule::in(array_keys(RepairService::PRIORITIES))],
            'source' => ['required', Rule::in(array_keys(RepairService::SOURCES))],
            'aircraft_registration' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['itemId' => 'barang', 'fault_description' => 'deskripsi kerusakan', 'location_id' => 'lokasi penyimpanan']);

        $repair = $repairs->receive([
            'item_id' => $data['itemId'],
            'qty' => $data['qty'],
            'location_id' => $data['location_id'],
            'fault_description' => trim($data['fault_description']),
            'priority' => $data['priority'],
            'source' => $data['source'],
            'aircraft_registration' => $data['aircraft_registration'] ? strtoupper(trim($data['aircraft_registration'])) : null,
            'notes' => $data['notes'],
        ]);

        $this->closeModal();
        $this->activeTab = 'intake';
        $this->dispatch('notify', ['icon' => 'success', 'message' => "Form masuk repair dibuat: {$repair->repair_code}. Menunggu ACC tim repair."]);
    }

    public function accept(string $code, RepairService $repairs): void
    {
        $this->authorizeStockAction('ims.repair.manage');

        $repairs->accept($code);
        $this->activeTab = 'waiting';
        $this->dispatch('notify', ['icon' => 'success', 'message' => "{$code} di-ACC dan masuk Rak Repair 1."]);
    }

    public function start(RepairService $repairs)
    {
        $this->authorizeStockAction('ims.repair.manage');

        $data = $this->validate([
            'technician_id' => ['nullable', Rule::exists('users', 'id')],
            'vendor_id' => ['nullable', Rule::exists('ims_suppliers', 'id')],
            'work_order_no' => ['nullable', 'string', 'max:50'],
            'estimated_completion_date' => ['nullable', 'date', 'after_or_equal:today'],
            'progress_notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['estimated_completion_date' => 'estimasi selesai']);

        $repairs->start($this->code, $data);

        $code = $this->code;
        $this->closeModal();
        $this->activeTab = 'process';
        $this->dispatch('notify', ['icon' => 'success', 'message' => "{$code} mulai diproses."]);
    }

    public function complete(RepairService $repairs)
    {
        $this->authorizeStockAction('ims.repair.manage');

        $data = $this->validate([
            'result' => ['required', Rule::in(array_keys(RepairService::RESULTS))],
            'findings' => ['required', 'string', 'min:3', 'max:2000'],
            'action_taken' => ['required_if:result,serviceable', 'nullable', 'string', 'max:2000'],
            'certificate_no' => ['nullable', 'string', 'max:100'],
            'repaired_by_name' => ['required', 'string', 'max:100'],
        ], [
            'action_taken.required_if' => 'Tindakan perbaikan wajib diisi untuk hasil serviceable.',
        ], ['findings' => 'temuan', 'repaired_by_name' => 'nama teknisi']);

        $repairs->complete($this->code, $data);

        $code = $this->code;
        $this->closeModal();
        $this->activeTab = 'completed';
        $this->dispatch('notify', ['icon' => 'success', 'message' => "{$code} selesai ({$data['result']})."]);
    }

    public function returnToStock(RepairService $repairs, StockService $stock)
    {
        $this->authorizeStockAction('ims.repair.back_stage');

        $this->validate([
            'return_location_id' => ['required', Rule::exists('ims_locations', 'id')],
        ], [], ['return_location_id' => 'lokasi stok']);

        try {
            $repairs->returnToStock($this->code, (int) $this->return_location_id, $stock);
        } catch (\DomainException $e) {
            $this->addError('return_location_id', $e->getMessage());

            return;
        }

        $code = $this->code;
        $this->closeModal();
        $this->dispatch('notify', ['icon' => 'success', 'message' => "{$code} dikembalikan ke stok."]);
    }

    public function render()
    {
        $term = '%'.$this->search.'%';
        $searchable = fn ($q) => $q->when($this->search !== '', fn ($q) => $q
            ->where(fn ($w) => $w->where('repair_code', 'like', $term)
                ->orWhere('fault_description', 'like', $term)
                ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $term)->orWhere('part_number', 'like', $term))));

        $items = match ($this->activeTab) {
            'process' => $searchable(RepairInProgress::with(['item', 'vendor', 'technician']))->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")->orderBy('started_at')->paginate(15),
            'completed' => $searchable(RepairCompleted::with(['item', 'location']))->orderByRaw('returned_to_stock_at IS NOT NULL')->orderByDesc('completed_at')->paginate(15),
            'waiting' => $searchable(RepairWaiting::with(['item'])->whereNotNull('accepted_at'))->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")->orderBy('received_at')->paginate(15),
            default => $searchable(RepairWaiting::with(['item'])->whereNull('accepted_at'))->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")->orderBy('received_at')->paginate(15),
        };

        return view('livewire.modules.ims.repair.index', [
            'items' => $items,
            'counts' => [
                'intake' => RepairWaiting::whereNull('accepted_at')->count(),
                'waiting' => RepairWaiting::whereNotNull('accepted_at')->count(),
                'process' => RepairInProgress::count(),
                'completed' => RepairCompleted::whereNull('returned_to_stock_at')->where('result', 'serviceable')->count(),
            ],
            'pickerItems' => $this->modal === 'receive' ? $this->pickerItems() : collect(),
            'locations' => in_array($this->modal, ['return', 'receive'], true) ? Location::where('is_active', true)->orderBy('name')->get() : collect(),
            'vendors' => $this->modal === 'start' ? Supplier::where('is_active', true)->orderBy('name')->get() : collect(),
            'technicians' => $this->modal === 'start' ? User::where('status', 'active')->orderBy('name')->get(['id', 'name']) : collect(),
            'priorities' => RepairService::PRIORITIES,
            'results' => RepairService::RESULTS,
            'sources' => RepairService::SOURCES,
        ])->layout('components.layouts.app', ['title' => 'Repair Rak - IMS']);
    }
}
