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

    public function import()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:50240',
        ]);

        try {
            Excel::import(new AircraftRotationImport, $this->importFile);

            session()->flash('message', 'Data Aircraft Rotation berhasil diimport.');
            $this->reset('importFile');
        } catch (\Exception $e) {
            Log::error('Import Aircraft Rotation gagal: '.$e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
        }
    }

    public function render()
    {
        $rotations = AircraftRotation::with('legs')->get();

        return view('livewire.modules.aircraft-rotation.index', compact('rotations'))->layout('components.layouts.app');
    }
}
