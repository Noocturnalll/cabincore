<?php

namespace App\Livewire\Modules\Ims\Repair;

use Livewire\Component;
use App\Models\Ims\RepairWaiting;
use App\Models\Ims\RepairInProgress;
use App\Models\Ims\RepairCompleted;

class Index extends Component
{
    public $activeTab = 'waiting'; // waiting | process | completed

    public function render()
    {
        $items = [];
        
        if ($this->activeTab == 'waiting') {
            $items = RepairWaiting::with(['item'])->paginate(15);
        } elseif ($this->activeTab == 'process') {
            $items = RepairInProgress::with(['item'])->paginate(15);
        } elseif ($this->activeTab == 'completed') {
            $items = RepairCompleted::with(['item'])->paginate(15);
        }

        return view('livewire.modules.ims.repair.index', [
            'items' => $items
        ])->layout('components.layouts.app', ['title' => 'Repair Rak - IMS']);
    }
}
