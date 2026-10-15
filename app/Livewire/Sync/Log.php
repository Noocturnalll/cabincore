<?php

namespace App\Livewire\Sync;

use App\Helpers\RoleHelper;
use App\Models\SheetSyncLog;
use App\Models\SyncSetting;
use Livewire\Component;
use Livewire\WithPagination;

/** What the two-way Google Sheet sync did on its own: conflicts, failed writes, and what healed itself. */
class Log extends Component
{
    use WithPagination;

    public string $filter = 'attention';

    public function mount(): void
    {
        abort_unless(self::allowed(), 403);
    }

    public static function allowed(): bool
    {
        $u = auth()->user();

        return (bool) ($u && ($u->hasRole(RoleHelper::SUPER_ADMIN) || $u->hasAnyRole(RoleHelper::COD_DESK)));
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function resolve(int $id): void
    {
        SheetSyncLog::whereKey($id)->whereNull('resolved_at')->update(['resolved_at' => now()]);
    }

    public function render()
    {
        $q = SheetSyncLog::query()->latest('updated_at');
        if ($this->filter === 'attention') {
            $q->whereIn('event', ['failed', 'conflict'])->whereNull('resolved_at');
        } elseif ($this->filter === 'healed') {
            $q->where('event', 'healed');
        }

        $dja = SyncSetting::for(SyncSetting::Dja);

        return view('livewire.sync.log', [
            'rows' => $q->paginate(25),
            'open' => SheetSyncLog::whereIn('event', ['failed', 'conflict'])->whereNull('resolved_at')->count(),
            'lastStatus' => $dja->last_status ?? null,
            'lastMessage' => $dja->last_message ?? null,
            'lastAt' => $dja->last_synced_at ?? $dja->updated_at ?? null,
        ])->layout('components.layouts.app', ['title' => 'Log Sinkron GSS']);
    }
}
