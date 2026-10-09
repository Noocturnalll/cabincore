<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Livewire\Component;
use Livewire\WithPagination;

class Center extends Component
{
    use WithPagination;

    public $search = '';

    public $category = 'all';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategory()
    {
        $this->resetPage();
    }

    public function setCategory(string $category): void
    {
        $this->category = $category === 'all' || array_key_exists($category, Document::CATEGORIES) ? $category : 'all';
        $this->resetPage();
    }

    public function render()
    {
        $documents = Document::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->category !== 'all', fn ($q) => $q->where('category', $this->category))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(24);

        $counts = Document::query()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');

        return view('livewire.documents.center', [
            'documents' => $documents,
            'counts' => $counts,
            'categories' => Document::CATEGORIES,
        ])->layout('components.layouts.app', ['title' => 'Document Center']);
    }
}
