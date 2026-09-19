<?php

namespace App\Livewire\Tools;

use Livewire\Component;

class Equipment extends Component
{
    public function render()
    {
        return view('livewire.tools.equipment')->layout('components.layouts.app');
    }
}
