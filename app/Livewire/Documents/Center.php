<?php

namespace App\Livewire\Documents;

use Livewire\Component;

class Center extends Component
{
    public $search = '';

    public $category = 'all';

    public function render()
    {
        $query = \App\Models\Document::query();

        if (!empty($this->search)) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return view('livewire.documents.center', [
            'documents' => $documents,
        ])->layout('components.layouts.app', ['title' => 'Document Center']);
    }
}
