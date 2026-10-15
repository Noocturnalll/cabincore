<?php

namespace App\Livewire\Modules\Manual;

use App\Models\ManualReference;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Searchable index of the maintenance manuals. The PDFs stay in Google Drive; this page only lists and links them. */
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = '';

    #[Url]
    public string $search = '';

    public ?string $previewUrl = null;

    public ?string $previewTitle = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function pick(string $tab): void
    {
        $this->tab = $tab;
        $this->search = '';
        $this->resetPage();
    }

    public function preview(string $url, string $title = ''): void
    {
        $embed = ManualReference::previewUrl($url);
        if ($embed) {
            $this->previewUrl = $embed;
            $this->previewTitle = $title;
        }
    }

    public function closePreview(): void
    {
        $this->previewUrl = null;
        $this->previewTitle = null;
    }

    public function render()
    {
        $tabs = ManualReference::query()->select('tab')->selectRaw('COUNT(*) as n')->groupBy('tab')->orderBy('tab')->pluck('n', 'tab');
        $tab = $tabs->has($this->tab) ? $this->tab : (string) $tabs->keys()->first();

        $query = ManualReference::where('tab', $tab)->orderBy('row_no');
        foreach (preg_split('/\s+/', mb_strtolower(trim($this->search)), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $query->where('search', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $word).'%');
        }

        $rows = $query->paginate(25);
        $headers = array_keys($rows->first()?->cells ?? ManualReference::where('tab', $tab)->orderBy('row_no')->value('cells') ?? []);

        return view('livewire.modules.manual.index', [
            'tabs' => $tabs,
            'activeTab' => $tab,
            'rows' => $rows,
            'headers' => $headers,
            'syncedAt' => ManualReference::max('synced_at'),
        ])->layout('components.layouts.app', ['title' => 'Referensi Manual']);
    }
}
