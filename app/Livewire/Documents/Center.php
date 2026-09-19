<?php

namespace App\Livewire\Documents;

use Livewire\Component;

class Center extends Component
{
    public $search = '';

    public $category = 'all';

    public function render()
    {
        // Mock data for documents
        $allDocuments = collect([
            ['id' => 1, 'title' => 'Template Import Data Excel', 'category' => 'template', 'size' => '24 KB', 'date' => '10 Sep 2026', 'ext' => 'xlsx'],
            ['id' => 2, 'title' => 'SOP Perawatan Kabin', 'category' => 'sop', 'size' => '2.1 MB', 'date' => '01 Sep 2026', 'ext' => 'pdf'],
            ['id' => 3, 'title' => 'Regulasi K3 Area Apron', 'category' => 'regulasi', 'size' => '1.5 MB', 'date' => '15 Agu 2026', 'ext' => 'pdf'],
            ['id' => 4, 'title' => 'Panduan Penggunaan Sistem CBM', 'category' => 'sop', 'size' => '4.2 MB', 'date' => '12 Sep 2026', 'ext' => 'pdf'],
            ['id' => 5, 'title' => 'Form Checklist Harian', 'category' => 'template', 'size' => '18 KB', 'date' => '05 Sep 2026', 'ext' => 'xlsx'],
        ]);

        $documents = $allDocuments->filter(function ($doc) {
            $matchesSearch = empty($this->search) || stripos($doc['title'], $this->search) !== false;
            $matchesCategory = $this->category === 'all' || $doc['category'] === $this->category;

            return $matchesSearch && $matchesCategory;
        });

        return view('livewire.documents.center', [
            'documents' => $documents,
        ])->layout('components.layouts.app', ['title' => 'Document Center']);
    }
}
