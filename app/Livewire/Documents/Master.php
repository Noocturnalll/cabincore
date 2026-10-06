<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Master extends Component
{
    use WithFileUploads;

    public $documents;
    
    // Form attributes
    public $documentId;
    public $title;
    public $category = 'regulasi';
    public $file;
    public $isModalOpen = false;

    public function mount()
    {
        $this->loadDocuments();
    }

    public function loadDocuments()
    {
        $this->documents = Document::orderBy('created_at', 'desc')->get();
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

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'file' => $this->documentId ? 'nullable|file|max:51200' : 'required|file|max:51200', // 50MB max
        ]);

        $document = $this->documentId ? Document::findOrFail($this->documentId) : new Document();
        $document->title = $this->title;
        $document->category = $this->category;

        if ($this->file) {
            // Delete old file if exists
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            $path = $this->file->store('documents', 'public');
            $document->file_path = $path;
            
            // Calculate size and extension
            $size = $this->file->getSize();
            if ($size < 1024 * 1024) {
                $sizeFormatted = round($size / 1024, 1) . ' KB';
            } else {
                $sizeFormatted = round($size / (1024 * 1024), 1) . ' MB';
            }
            $document->file_size = $sizeFormatted;
            
            $ext = strtolower($this->file->getClientOriginalExtension());
            $document->file_extension = $ext;
        }

        $document->save();

        $this->closeModal();
        $this->loadDocuments();
        
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Dokumen berhasil disimpan.']);
    }

    public function delete($id)
    {
        $document = Document::findOrFail($id);
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
        
        $this->loadDocuments();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
    }

    public function resetForm()
    {
        $this->documentId = null;
        $this->title = '';
        $this->category = 'regulasi';
        $this->file = null;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.documents.master')->layout('components.layouts.app', ['title' => 'Master Data Dokumen']);
    }
}
