<?php

namespace App\Livewire\Master;

use Livewire\Component;

class Airports extends Component
{
    public function render()
    {
        return view('livewire.master.airports')->layout('components.layouts.app');
    }
}
