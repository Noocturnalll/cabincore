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
