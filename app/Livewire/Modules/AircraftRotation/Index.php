<?php

namespace App\Livewire\Modules\AircraftRotation;

use App\Models\Rotation;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Index extends Component
{
    /** How many uploads the history list shows before "Muat lebih banyak". */
    public int $limit = 20;

    public function loadMore(): void
    {
        $this->limit += 20;
    }

    public function deleteRotation($id)
    {
        $rotation = Rotation::findOrFail($id);

        // Storage::delete ignores files that are already gone, so a stale record can still be removed
        Storage::delete(array_filter([$rotation->file_path, $rotation->html_path]));
        $rotation->delete();

        session()->flash('success', 'File rotasi "'.$rotation->title.'" berhasil dihapus.');
        $this->dispatch('rotation-deleted', id: (int) $id);
    }

    private function fileSize(?string $path): ?int
    {
        try {
            return $path && Storage::exists($path) ? Storage::size($path) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function render()
    {
        $total = Rotation::count();
        $rotations = Rotation::latest()->latest('id')->take($this->limit)->get()
            ->each(fn ($r) => $r->setAttribute('size_bytes', $this->fileSize($r->file_path)));

        return view('livewire.modules.aircraft-rotation.index', [
            'rotations' => $rotations,
            'total' => $total,
        ])->layout('components.layouts.app', ['title' => 'Aircraft Rotation']);
    }
}
