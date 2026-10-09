<?php

namespace App\Livewire\Documents;

use App\Helpers\RoleHelper;
use App\Livewire\Traits\WithLogTable;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Master extends Component
{
    use WithFileUploads, WithLogTable, WithPagination;

    public $search = '';

    public $categoryFilter = '';

    // dipakai oleh WithLogTable::setTab()
    public $activeTab = '';

    // Form attributes
    public $documentId;

    public $title;

    public $category = 'regulasi';

    public $file;

    public $isModalOpen = false;

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    /** The sidebar hides this page from non Super Admin, the actions must be guarded on the server too. */
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasRole(RoleHelper::SUPER_ADMIN), 403, 'Hanya Super Admin yang dapat mengelola dokumen.');
    }

    public function mount()
    {
        $this->authorizeManage();
    }

    public function hydrate()
    {
        $this->authorizeManage();
    }

    public function create()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $document = Document::findOrFail($id);
        $this->documentId = $document->id;
        $this->title = $document->title;
        $this->category = $document->category;

        $this->isModalOpen = true;
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Document::CATEGORIES))],
            'file' => [
                $this->documentId ? 'nullable' : 'required',
                'file',
                'max:'.Document::MAX_KB,
                'extensions:'.implode(',', Document::ALLOWED_EXTENSIONS),
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['title' => 'judul', 'category' => 'kategori', 'file' => 'file'];
    }

    protected function messages(): array
    {
        return [
            'file.extensions' => 'Format file harus: '.strtoupper(implode(', ', Document::ALLOWED_EXTENSIONS)).'.',
            'file.max' => 'Ukuran file maksimal '.(Document::MAX_KB / 1024).' MB.',
        ];
    }

    public function save()
    {
        $this->authorizeManage();
        $this->title = trim((string) $this->title);
        $this->validate();

        $document = $this->documentId ? Document::findOrFail($this->documentId) : new Document;
        $document->title = $this->title;
        $document->category = $this->category;
        $oldPath = $document->file_path;

        if ($this->file) {
            // Store the new file first so a failed upload never leaves the record without a file
            $document->file_path = $this->file->store('documents', 'public');

            $size = $this->file->getSize();
            $document->file_size = $size < 1024 * 1024
                ? round($size / 1024, 1).' KB'
                : round($size / (1024 * 1024), 1).' MB';
            $document->file_extension = strtolower($this->file->getClientOriginalExtension());
        }

        $document->save();

        if ($this->file && $oldPath && $oldPath !== $document->file_path) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->closeModal();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Dokumen berhasil disimpan.']);
    }

    public function delete($id)
    {
        $this->authorizeManage();

        $document = Document::findOrFail($id);
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
    }

    public function resetForm()
    {
        $this->documentId = null;
        $this->title = '';
        $this->category = 'regulasi';
        $this->file = null;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function render()
    {
        $documents = Document::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.documents.master', [
            'documents' => $documents,
            'categories' => Document::CATEGORIES,
            'allowed' => strtoupper(implode(', ', Document::ALLOWED_EXTENSIONS)),
            'maxMb' => Document::MAX_KB / 1024,
        ])->layout('components.layouts.app', ['title' => 'Master Data Dokumen']);
    }
}
