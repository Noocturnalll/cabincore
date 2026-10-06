<?php

namespace App\Livewire\Modules\AircraftRotation;

use App\Imports\AircraftRotationImport;
use App\Models\AircraftRotation;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads;

    public $importFile;

    public function import(\App\Services\ExcelHtmlRenderer $renderer)
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls|max:50240',
        ]);

        try {
            $filename = $this->importFile->getClientOriginalName();
            $path = $this->importFile->store('rotations');
            
            $html = $renderer->render(\Illuminate\Support\Facades\Storage::path($path));
            
            $htmlPath = 'rotations/' . pathinfo($path, PATHINFO_FILENAME) . '.html';
            \Illuminate\Support\Facades\Storage::put($htmlPath, $html);
            
            \App\Models\Rotation::create([
                'title' => pathinfo($filename, PATHINFO_FILENAME),
                'file_path' => $path,
                'html_path' => $htmlPath,
            ]);

            session()->flash('message', 'File Rotasi berhasil diunggah dan dirender.');
            $this->reset('importFile');
        } catch (\Exception $e) {
            Log::error('Import Aircraft Rotation gagal: '.$e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memproses data: '.$e->getMessage());
        }
    }

    public function deleteRotation($id)
    {
        $rotation = \App\Models\Rotation::findOrFail($id);
        \Illuminate\Support\Facades\Storage::delete([$rotation->file_path, $rotation->html_path]);
        $rotation->delete();
        session()->flash('message', 'File Rotasi berhasil dihapus.');
    }

    public function render()
    {
        $rotations = \App\Models\Rotation::latest()->get();

        return view('livewire.modules.aircraft-rotation.index', compact('rotations'))->layout('components.layouts.app');
    }
}
