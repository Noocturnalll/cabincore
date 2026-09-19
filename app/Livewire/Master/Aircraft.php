<?php

namespace App\Livewire\Master;

use Livewire\Component;

class Aircraft extends Component
{
    public function render()
    {
        return view('livewire.master.aircraft')->layout('components.layouts.app');
    }
}
