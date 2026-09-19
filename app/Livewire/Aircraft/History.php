<?php

namespace App\Livewire\Aircraft;

use Livewire\Component;

class History extends Component
{
    public function render()
    {
        return view('livewire.aircraft.history')->layout('components.layouts.app');
    }
}
