<?php

namespace App\Livewire\Modules\Sources;

use App\Services\Sources\DataSources;
use Livewire\Component;

/**
 * Sumber Data: every Google Sheet the system reads, in one place. The planner's sheets (DJA, AC Movement) are read only
 * and live on their own pages; the teams' result sheets and the reference lists are synced from here.
 */
class Index extends Component
{
    /** @var array<string, string> key => link being edited */
    public array $links = [];

    public ?string $running = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('sources.view'), 403);
    }

    public function save(string $key, DataSources $sources): void
    {
        abort_unless(auth()->user()->can('sources.sync'), 403);

        if (! $sources->setSpreadsheet($key, $this->links[$key] ?? '')) {
            $this->addError("links.$key", 'Bukan link atau ID Google Sheet yang valid.');

            return;
        }
        $this->links[$key] = '';
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Link disimpan.']);
    }

    public function sync(string $key, DataSources $sources): void
    {
        abort_unless(auth()->user()->can('sources.sync'), 403);

        $this->running = $key;
        $result = $sources->run($key);
        $this->running = null;

        $this->dispatch('notify', ['icon' => $result['ok'] ? 'success' : 'error', 'message' => $result['message'], 'timer' => 8000]);
    }

    public function render(DataSources $sources)
    {
        $all = $sources->all();

        return view('livewire.modules.sources.index', [
            'groups' => [
                'planner' => ['Dari planner (hanya baca)', collect($all)->where('kind', 'planner')],
                'result' => ['Hasil kerja harian', collect($all)->where('kind', 'result')],
                'reference' => ['Data acuan', collect($all)->where('kind', 'reference')],
            ],
            'canSync' => auth()->user()->can('sources.sync'),
        ])->layout('components.layouts.app', ['title' => 'Sumber Data']);
    }
}
